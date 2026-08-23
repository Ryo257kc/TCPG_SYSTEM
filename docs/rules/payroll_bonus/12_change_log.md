# 給与・賞与 変更履歴

## 2026-08-23 確定済み給与の再計算がサーバー側で防げていなかった問題を修正・社保合計の重複解消

- `PayrollV2CalculationFlowService`（給与計算ボタンの通り道を1本化するService）の
  `recalculateMonthly()`/`recalculateAmountsAfterInputChange()`/`recalculateAfterAttendanceReflect()`/
  `recalculateEmploymentInsurance()`/`recalculateIncomeTaxWithTrace()`/`recalculateBonus()`は、
  対象が確定済み（`edit_lock=1`）でもサーバー側で止める処理が無かった。画面の「再計算」等の
  ボタンは`isPayrollConfirmed`で正しくグレーアウトされていたが、それは画面側だけの防止で、
  サービスを直接呼べば素通りしてしまう状態だった（`PayrollV2IncomeTaxService::recalculateWithTrace()`を
  動作確認のつもりでtinkerから直接呼んだ際に実際に発生：確定済みの041・2026年8月分に書き込みが
  走った。書き込まれた値は健保・介護・厚年・雇用・子ども子育て拠出金という触っていない列から
  再計算しても同じ値になることを確認済みで、実害は無かったが、経路自体は本物の穴だった）。
  各メソッドの先頭で対象行の`edit_lock`を確認し、確定済みなら何もせず`0`を返すよう修正。
  `00_global.md`の「過去データの保護」ルールに合わせた。
- `PayrollV2UpdateService::socialInsuranceSum()`（社保合計＝健保+介護+子ども子育て拠出金+厚年+雇用）が
  `private`だったため、同じ式を`PayrollV2IncomeTaxService`が独立して再実装していた
  （`docs/rules/payroll_bonus/10_calculation_basis.md`が正本と明記してるのに、実際には
  共有できない作りになっていた）。`public`にして`PayrollV2IncomeTaxService`から呼ぶよう統一。
- 給与明細（`resources/views/shared/payroll/payslip_item.blade.php`、管理側・スタッフポータル
  共通）の「その他合計」欄が`cost_liquidation+company_advance_cost-adjustment_year_end`を
  Blade側で独自計算していた。正本は`PayrollV2SummaryService::transferAmount()`で、この式の
  一部を切り出したもの。保存済みの差引支給額(`transfer_amount`)から支給合計・控除合計を
  差し引いて逆算する形に変更し、「支給合計-控除合計+その他合計=差引支給額」が常に一致する
  ようにした（内訳が増えても表側の再計算は不要）。実データ5件で旧計算と新計算が完全一致する
  ことを確認済み。

## 2026-08-23 保存確認漏れ（手当マスタ・往診売上反映）修正

041実運用の監査を機に管理側も横断監査した結果、2件見つかった。

- `AllowanceV2Service::update()`（手当マスタ、`mx_allowance`）：`update()`の戻り値を見ず、
  DBに実際反映されたかに関わらず固定文字列`'更新しました。'`を返していた。影響行数を
  返すよう変更し、コントローラー側で0件ならエラーメッセージを出すよう修正。このテーブルは
  `tax_target`/`rou_target`/`syaho_target`/`kotei_wage`等、給与計算の対象判定フラグを
  持つため、無言で反映されないと計算結果に波及する。
- `PayrollV2SalesImportService::reflect()`（往診売上の給与反映）：`PayrollV2UpdateService::save()`
  は影響行数を`int`で返す設計なのに、呼び出し側で戻り値を捨てて`$result['updated']++`を
  無条件に加算していた。戻り値を見て0件なら`missing`（対象給与行なし、と同じ扱い）に
  カウントするよう修正。

## 2026-08-23 給与仕訳CSV追加・転籍時に過去月の会社が変わるバグ修正・根拠不明ハードコード削除

### 給与仕訳CSV・業務委託仕訳CSV(取引インポート形式)

- `PayrollV2JournalCsvService`を新規作成。`mx_kyuyo_shou`から freee の取引インポート形式(振替伝票ではなく取引仕訳)で未払計上のCSVを作る。`/admin/payroll`の帳票選択に「給与仕訳CSV」「業務委託仕訳CSV」を追加(ルート: `admin.payroll.journal-csv`/`admin.bonus.journal-csv`/`admin.payroll.outsource-journal-csv`)。
- 金額の対応関係は実物の仕訳(journal_breakdown=6043037)、およびユーザーが手で組み直したサンプルの両方と完全一致することを検証済み。詳細はサービスのdocblock参照。
- `staff_division='業務委託'`は給与CSVの対象外(賃金台帳と同じ判定)。業務委託仕訳CSVは逆に業務委託のみを対象に、人ごと1行(勘定科目は全て「業務委託料」、税区分「課対仕入（控80）10%」、supply_sum+cost_liquidationの全額)で出す。取引先列は使わない(freeeは1取引=1取引先の制約があるため、スタッフ名は備考列へ)。
- ファイル名の頭に会社の略称(プレッジ=PG、トータルケア=TC)を付ける。全社選択時は略称なし。

### 転籍者の過去月が今の所属会社で表示される不具合

- `PayrollV2CreateService::create()`が新規給与データ作成時に`mx_kyuyo_shou.section`を書き込んでいなかった（2015年〜2026年7月分はAccess同期起因で埋まっていたが、Laravelのこの作成経路自体は一度も書いていない）。そのため`/admin/payroll`の会社・部門の判定(`PayrollV2StaffService::staffs()`)は常に**今の**`mx_staffs.section`を見ており、転籍者の過去月を表示すると転籍後の会社になっていた（一覧・賃金台帳・会社負担一覧・振込一覧・CSV全部が対象）。
- 修正: `create()`で作成時点の`mx_staffs.section`を`mx_kyuyo_shou.section`へ焼き付けるようにした。`PayrollV2SummaryService::mergeRows()`は、その給与レコード自身の`section`（焼き付け値）を`PayrollV2StaffService::storeCompanyMap()`で会社・店舗名に引き直す方式に変更、`PayrollV2Controller::buildPageData()`の会社フィルタも焼き付け値ベースの結果に対して行うよう変更。
- **今のmx_staffs.sectionへのフォールバックは一切しない**（フォールバックはエラーを出さずに黙って処理するため、sectionが未記録の抜けに気づけなくなる）。焼き付けが無い（空欄の）レコードは会社・店舗名を空のまま返す。2026年8月分の一部レコード（作成時点でこの修正が無かったため空欄）は、今の所属で埋め直すかどうか別途判断。
- 同じ理由で`PayrollV2EmploymentInsuranceService::resolveCompanyId()`（雇用保険・労災の料率選択に使う会社判定）も、給与レコードの焼き付け`section`を最優先し、無ければ空扱い（フォールバックしない）に変更。
- 同じ穴が`PayrollV2RecalculateService`（「再計算」ボタンの本体、`recalculate()`/`recalculatePayrollMasterOnly()`/`refreshBasicSalaryFromAttendance()`）と`PayrollV2SocialInsuranceAmountService::loadRatesForStaff()`（会社負担一覧の保険料率取得元）にもあった。前者は`$companyName`という引数を受け取っていながら内部で一切使っていなかった（渡す意味がなかった）。両方とも`loadCurrentSummary()`で取得した給与レコード自身の`section`を最優先し、フォールバックしない形に修正。`loadCurrentSummary()`のSELECT列に`section`を追加。
- 同じ穴を`PayrollV2OvertimeDeductionService::normalizeCompanyName()`（残業・欠勤控除の再計算）、`PayrollV2BonusSocialInsuranceService::resolveCompanyId()`（賞与の社保再計算・会社負担一覧賞与版）でも修正。
- `PayrollV2CreateCandidatesService`は新規給与データ作成の対象者選定用で、まだ給与レコードが存在しない段階のため今の所属を見るのが正しい。修正不要と判断。

### 往診の管理手当(managerAllowance)から会社の絞り込みを撤廃

- `PayrollV2HomeVisitAllowanceService`の管理手当計算（`MANAGER_IDS`＝002・013固定の2名が、他の往診スタッフの売上合計の2%を上乗せで受け取る仕組み）は、対象スタッフを`companyStaffIds()`で会社ごとに絞り込んでいた。`mx_staffs`に「チーム」に相当する列は存在せず、この会社単位の区切りに業務上の裏付けが無いことをユーザー確認済み（「チーム分けとかしてたっけ？」「管理手当は会社関係なくない？」）。実データ（2026年7月、002・013両名）で絞る/絞らないで計算結果に差が無いことも確認した上で、`companyStaffIds()`ごと削除し、`payrollRows()`は往診スタッフ全員（会社をまたぐ）を対象にするよう変更。将来チーム分けが必要になった場合は、意味のある形（実データに基づく専用の区分）で改めて実装すること。

### 根拠不明ハードコードの追加削除

- `PayrollV2EmploymentInsuranceService::recalculate()`/`recalculateBonus()`の除外条件に、`staff_division`が「保育事業部」「鍼灸整骨院」を含む場合を除外する判定があった。実在する`staff_division`の値（アルバイト/パート/管理責任者/業務委託/契約社員/兼務役員/正社員/役員）にどちらも一致するものが無く、ユーザーも「そんな除外判定使ったことない」と明言。両方削除（`koyou`フラグのみの判定に統一）。
- 賞与側は元々この文字列判定が文字化けしており一度も一致していなかった（後に`hex2bin()`で「修正」されて実際に効くようになっていた）。中身を検証せず文字化けだけ直したことで、意図せず新しい除外が有効化されていた可能性がある。

## 2026-08-18

### 賃金台帳・委託報酬台帳の通勤費表示を統一

- `admin/payroll/wage-ledger`・個人賃金台帳は`mx_allowance`マスタ駆動の動的行生成
  （`PayrollV2AllowanceLabelService::entries()`）で、非課税通勤費(`allowance_amo_6`)と
  非課税通勤費加算(`traffic_addition`)がマスタに別々に登録されているため、そのままだと
  2行に分かれて表示されていた（給与明細では既に合算表示）。
  `PayrollV2Controller::buildWageLedgerView()`／`personalWageLedgerRow()`で
  `traffic_addition`を`allowance_amo_6`側へ合算し、blade側の`excludedAllowanceKeys`に
  `traffic_addition`を追加して単独行を非表示にした。課税通勤費(`allowance_amo_10`)は
  賃金台帳では合算せず別行のまま。
- `admin/payroll/outsource-reward-ledger-print`はマスタ駆動ではなく固定行定義。こちらは
  課税/非課税を問わず`allowance_amo_10`＋`allowance_amo_6`＋`traffic_addition`を
  1行「交通費」に合算する仕様（賃金台帳とは合算基準が異なる点に注意）。
  `outsourceRewardLedgerPrint()`で`commuting_total`を計算して行に追加。
- ついでに`buildWageLedgerView()`にあった未使用の`taxable_commuting`/`non_taxable_commuting`
  キー（どのblade からも参照されていなかった死んだコード、2026-08-18の前回修正で追加したが
  実際に画面へ反映される経路ではなかった）を削除。

## 2026-08-15〜16

### 所得税の年度分岐（過去年の税額表）

- `PayrollV2IncomeTaxService::salaryDeduction()`／`PayrollV2BonusIncomeTaxCalcService::bonusRateKou()`が
  現行の税額表しか持っておらず、過去年分を計算し直すと現在の税額表で計算されてしまっていた。
  ユーザーが実際のAccess VBAソースから抽出した過去（2025年以前）の税額表をもとに、`targetYear`で
  分岐させた。Accessも自動切り替えではなく手動でコードを差し替える方式だったことをユーザーが確認済み。
  `kisoKoujyo()`は過去データが不完全だったため年度分岐は保留（要確認コメントあり）。
- 年調・給与など法改正が絡む計算は、新ルール追加時に必ず`targetYear`で分岐させること
  （年調の是正で過去3年分をやり直すことがあるため）。

### 労災料率・雇用保険の根拠不明ハードコード削除

- `PayrollV2EmploymentInsuranceService`に「店舗コード003だけ労災料率3.5固定」
  「staffId==='001'は雇用保険対象外」という決め打ちがあった。ユーザー確認の結果、前者は根拠不明の
  ハードコード、後者は`koyou`フラグで既に正しく除外されているため冗長と判明し、両方削除。

### 児童手当拠出金(jidou_office)の計算バグ

- 月給側`PayrollV2EmploymentInsuranceService::recalculate()`が`mx_syaho.jidou_rate`（%表記）を
  ÷1000で割っていたため、本来の1/10の金額で保存されていた（実データ確認：864円が保存済みなのに
  再計算すると86円になった）。÷100（`ceil`）へ修正。
- 賞与側`recalculateBonus()`は基礎額に厚生年金と同じ標準賞与額の上限（同月150万円）を使わず、
  上限なしの`rouho_target_sum`をそのまま使っていたため、上限超過分も含めて過大計算されていた。
  `PayrollV2BonusSocialInsuranceService::resolveTargetStandards()`（`kounen_target_standard`）を
  共有する形に修正。
- 詳細・検算方法は `10_calculation_basis.md` の「賞与社会保険」を参照。

### 会社負担一覧（月給・賞与）を再構築

- `PayrollV2Controller::companyBurdenPrint()`（会社負担一覧）はcodex製の状態で、子ども支援金が
  会社負担のみ表示（自己負担が無い）、厚生年金の合計が正しく集計されていない等の実害があった。
  Accessの実データ・スクショと数値が一致するまで作り直した。
- 賞与版（`bonusCompanyBurdenPrint()`）を追加。月給版と共通のテンプレート・集計ロジックを使い、
  会社負担側の計算だけ`PayrollV2BonusSocialInsuranceService::statementAmounts()`に切り替える。

### sqlsrv_payroll接続の既定スキーマ不具合

- `sqlsrv_payroll`接続(`Payroll`/`Payroll_DEV`)のアカウントで、本番DB閲覧用に権限変更した際の副作用と思われる形で、既定スキーマ(DEFAULT_SCHEMA)が`dbo`ではなく`db_datareader`になってしまっていた。
- `StaffV2Service::tableRows()`が`Schema::connection('sqlsrv_payroll')->getColumnListing($table)`をスキーマ無しで呼んでおり、SQL Serverの`schema_name()`(ログインの既定スキーマ)に暗黙的に頼っていたため、列一覧が常に空配列になり`kihonRows()`/`shahoRows()`/`residentRows()`/`fuyoRows()`(スタッフ編集画面の給与マスタ・社保・住民税・扶養タブ)が常に空を返していた。`DB::table('dbo.xxx')`のようにスキーマを明示してるクエリは影響を受けず、データ自体は正常だった。
- DB側でアカウントの既定スキーマを`dbo`に修正して解決。本番閲覧用にDBアカウントの権限を変更する時は、既定スキーマ等の副作用が無いか確認すること。

## 2026-08-17

### 時給制スタッフの基本給、残業・休日出勤時間の二重払いを修正

- `PayrollV2RecalculateService::basicSalaryAmount()`が時給×`work_time`（残業・休日出勤時間を
  含む総時間）で基本給を計算していたのに対し、`PayrollV2OvertimeDeductionService`が残業手当・
  休日出勤手当を時給×1.25×時間で満額別枠計算していたため、該当時間が二重払いになっていた
  （実データで確認：staff047・2025年3月、時給1000円、残業20.5時間で8,000円相当の重複を確認）。
  深夜残業手当だけは元から時給×0.25×時間（割増分のみ）で正しい形だった。
- 基本給の計算を「残業・休日出勤時間を除いた通常時間」ベースに修正。残業手当・休日出勤手当の
  倍率（1.25/0.25）は変更していない。月給制（`monthly_salary`固定）は元々この問題の対象外。
- 勤怠側の`work_time_net`（所定時間）もこの基本給計算と同じ「総時間−残業−休日出勤時間」に
  揃えた。詳細は`docs/rules/attendance/10_calculation_basis.md`。
- 過去に保存済みの基本給は書き換えていない（今後の再計算時から新しい式になる）。

## 2026-08-09

### 賞与の雇用保険 文字化け修正

- `PayrollV2EmploymentInsuranceService::recalculateBonus()`の「保育事業部」「鍼灸整骨院」除外判定が
  文字化けした文字列リテラルで、一度も一致していなかった（同ファイルの月給側`recalculate()`は
  正しい文字列だった。`PayrollV2OvertimeDeductionService`で同種のバグを直したのと同じ日に発覚）。
  `hex2bin()`方式に統一。

### 賞与の標準報酬月額 上限判定を1箇所に統合

- `PayrollV2Controller::attachBonusCalc()`（賞与一覧の表示用）が、保存処理
  `PayrollV2BonusSocialInsuranceService::resolveTargetStandards()`とは別に、同じ計算を
  独自の式（同月内の判定で日付の前後関係を使っていた）で持っていた。ユーザー確認：
  Codexが後から追加したもので、既存を直さず新しく作ってしまったパターン。
  実害は無かった（同一スタッフが同月に2回賞与を受け取ることはない）が、式のズレ・
  将来の乖離リスクがあったため、共通の`computeTargetStandards()`（public static、
  `PayrollV2BonusSocialInsuranceService`側）に統合。表示側は自分の`kyuyo_sho_no`を
  除いた履歴を渡して呼ぶだけにした。

### デッドコード削除

- `PayrollV2RecalculateService`内の`loadSocialInsuranceRates()`／`calculateSocialInsurance()`
  （＋専用ヘルパー`employeeInsuranceAmount()`／`officeInsuranceAmount()`／`shouldApplyKaigo()`）は
  どこからも呼ばれていない、`PayrollV2SocialInsuranceAmountService`とほぼ同内容の重複だった。削除。

### 差引支給額の保存を復活

- `supply_deduction_sum`が2026年2月分を最後に保存されなくなり、表示側が
  `PayrollV2SummaryService::transferAmount()`をライブ計算する形に静かに変わっていた
  （「帳票側で再計算しない」ルール違反の状態）。
- `PayrollV2UpdateService::rebuildTotals()`／`rebuildBonusTotals()`で`transferAmount()`を呼び、
  結果を`supply_deduction_sum`へ保存するよう修正。
- 表示側（`PayrollController`（StaffPortal）・`PayrollV2Controller`・`PayrollV2SummaryService`）は
  `transferAmount()`の直接呼び出しをやめ、保存済み`supply_deduction_sum`を読むだけに変更。
- 過去データ（〜2026年2月）は本番サーバーからの同期で埋める想定のため、バックフィルはしていない。
- 詳細は `03_wage_ledger.md`。

### DB・スキーマの握りつぶし修正

- `StaffPortal\PayrollController`が手当名マスタを存在しない`t_allowance`という名前で参照していた
  （正しくは`mx_allowance`）。`try/catch(\Throwable)`で握りつぶされていたため、会社ごとの
  手当名カスタマイズ機能が何ヶ月も静かに無効化されていた。
- 同様の`hasTable`/`hasColumn`の握りつぶしが`AuthController`（`PayrollController`と全く同じ内容が
  複製されていた）、`HandlesStaffPortalContext::shouldShowPayrollLinks()`、
  `AllowanceV2Service`・`CompanyV2Service`・`ReportV2SanteiCsvService`・
  `PayrollV2BonusSocialInsuranceService`・`PayrollV2FuyoService`・
  `PayrollV2EmploymentInsuranceService`・`PayrollV2StaffMasterService`にも見つかり、
  すべて握りつぶしを削除（本当にテーブル/カラムが無ければ例外がそのまま出るようにした）。
- `useMxStaffTable()`/`useMxPayrollTable()`/`useMxStoreTable()`が`PayrollController`と
  `AuthController`に複製されていて、`commonViewData()`経由で全26コントローラーから
  呼ばれる`shouldShowPayrollLinks()`が実は他の24コントローラーには存在しないメソッドを
  呼んでいた（毎回例外→握りつぶしで「給与リンクが消えるだけ」に見えていた）。
  `HandlesStaffPortalContext`トレイトに1箇所へ統合。
- 詳細は `99_do_not_touch.md`「DB・スキーマのエラーを握りつぶさない」。

### DBカラム整理

- `allowance_1`、`allowance_3`〜`allowance_16`（手当名称の旧テキスト列、`mx_allowance`マスタに
  置き換え済みで未参照）を`x_`退避。
- `mx_nen_tyo.haigu_toku_deduction`（配偶者控除）→`haigu_deduction`、
  `haigu_toku_deduction_amo`（配偶者特別控除）→`haigu_toku_deduction`にリネーム
  （旧名は「特別」でない方に`toku`が付く紛らわしい命名だった）。
- `mx_nen_tyo.haigu_shotoku_sum`（全レコードで未使用）を削除。

## 2026-08-01

### 整理

- 給与・賞与の共通CSSを `payroll.css` に整理。
- 賞与画面の入力欄幅を給与と同じにした。
- 賞与画面の基本情報ボックスが横幅を吸いすぎないように調整。
- 旧 `bonus.css` 参照を削除。

### 計算

- 給与・賞与の社保合計、控除合計を `PayrollV2UpdateService` に寄せた。
- 子ども支援金を社保合計に含めるよう整理。
- 賞与保存を `saveBonus()` に寄せた。

### DB

- `officer_com` を `x_officer_com` に退避。
- `allowance_2` を `x_allowance_2` に退避。
- レセDBの `x_department_no_old`、`x_staff_name_old` は退避済みとして確認。
- `store_name_old` は存在しないことを確認。

### 役員報酬

- 現在の役員報酬は `allowance_amo_2`。
- `allowance_2`、`officer_com` は旧カラム扱い。

## 2026-08-01 ドキュメント構成を追加

- `07_architecture.md` を追加。
- `08_dependencies.md` を追加。
- `09_coding_rules.md` を追加。
- `99_do_not_touch.md` を追加。
- 今後、全ページ共通ルールは薄く保ち、ページ別の業務ルールは対象別docsに分ける方針にした。

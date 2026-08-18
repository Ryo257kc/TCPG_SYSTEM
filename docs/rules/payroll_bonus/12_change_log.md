# 給与・賞与 変更履歴

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

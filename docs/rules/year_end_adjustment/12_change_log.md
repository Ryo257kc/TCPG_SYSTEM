# 年末調整 変更履歴

## 2026-09-12 令和8年分（2026年）の税制改正対応（令和8年度税制改正）

国税庁「令和8年分 年末調整のしかた」（https://www.nta.go.jp/publication/pamph/gensen/nencho2026/01.htm ）
で確認し、以下を実装した。詳細な数字・根拠は`03_calculation.md`参照。

- 基礎控除額：2026年8月時点で実装していた令和8年分の数字（132万円超2,350万円以下が一律58万円）は、
  令和8年度税制改正で撤回された旧予定の数字だった。あらためて引き上げられた「令和8・9年分」
  （99/104/104/67/62万円の5段階）と「令和10年分以後」（99/62万円の2段階）を別ブランチで実装
- 給与所得控除：最低保障額65万円→74万円（令和8・9年分限定）、収入69万1,000円〜220万円未満は
  専用の特例表を新設。令和10年分以後の式は要確認のためコード内にTODOコメントを残した
- 扶養親族等・配偶者特別控除・特定親族特別控除の所得要件：58万円→62万円に引き上げ（恒久的な
  変更という理解、上限側・内訳の金額表は変更なし）
- 一般生命保険料控除：23歳未満の扶養親族を有する場合の新しい計算式・上限額を新設
  （令和8・9年分限定、新生命保険料のみ対象）

## 2026-08-24 帳票の会社・店舗解決が「今の」所属を見ていた問題を修正・mx_nen_tyo.section追加

- `YearEndAdjustmentV2Controller::staffDetail()`（源泉徴収票・賃金台帳・扶養控除申告書・
  保険料控除申告書等、全帳票の会社名・店舗名・住所・電話番号の解決元）が、`mx_staffs.section`
  （スタッフの**今の**所属）を常に参照していた。年調は是正で過去年度をやり直す前提の機能なのに、
  対象年度時点の所属を保存する列が`mx_nen_tyo`に無かった。転籍した人の過去年度の年調書類が、
  転籍後の今の会社名で印刷される可能性があった（給与の`mx_kyuyo_shou.section`で既に
  修正済みの問題と全く同じパターン、2026-08-24発覚）。
- `mx_nen_tyo`に`section`列（nvarchar(3)、`mx_kyuyo_shou.section`と同じ型）を追加
  （`database/sql/2026_08_add_section_to_mx_nen_tyo.sql`）。既存231行は履歴が無いため、
  今の`mx_staffs.section`で暫定バックフィル（転籍者は稀という前提、ユーザー確認の上で実施。
  転籍歴のあるスタッフの過去分は必要なら個別に手動訂正）。`createTargets()`（対象者作成）で
  以降は作成時点の所属を必ず固定する。
- `staffDetail()`のシグネチャを`staffDetail(string $staffId, ?string $nenTyoSection)`に変更し、
  呼び出し元（`show()`・`hokenPreview()`・`templatePreview()`・一括印刷の会社絞り込み処理）は
  全て対象`mx_nen_tyo`行の`section`を渡すよう修正。sectionが無い場合は会社情報を一切埋めない
  （もっともらしい値へのフォールバックはしない）。

## 2026-08-24 反映(reflectApplication)がmx_staffs側の存在確認をしてなかった問題を修正

`YearEndAdjustmentV2Controller::reflectApplication()`・`OnboardingRequestV2Controller::reflectApplication()`・
`ProfileRequestV2Controller::reflectApplication()`は、いずれも申請側の行（`mx_nen_tyo`/
`staff_onboarding_requests`/`staff_profile_requests`）は`abort_unless`で存在確認してたが、
実際の反映先（`mx_staffs`、staff_id指定でUPDATE）は一度も存在確認してなかった。反映先の
staff_idが存在しない/ずれてると、氏名・住所・世帯主・**銀行口座**（入社手続きのみ）等の反映が
無言で失敗するのに「実データへ反映しました。」と出る。3箇所とも反映先の`mx_staffs`行の
存在確認を追加し、無ければ反映せずエラーメッセージを返すよう修正。

## 2026-08-23 状態更新の保存確認漏れ修正

`YearEndAdjustmentV2Controller::updateStatus()`（対象者の状態変更）が、対象`nen_tyo_no`の
実在確認・`update()`の影響行数チェックをせず、常に「年調対象者の状態を更新しました。」と
表示していた。0件なら「更新対象の年調申請が見つかりません」を返すよう修正。同ファイルの
他メソッド（confirmApplication等）は元々`abort_unless`で対象行を確認してから書き込む
作りだったが、このメソッドだけ抜けていた。041実運用の監査を機に管理側も横断監査した。

## 2026-08-15 住民税の宛名番号・提出先を年度履歴管理(mx_resident)へ移設

スタッフ編集画面「住民税」タブで、住民税の月別金額(`resident_tax1`〜`12`等)は`mx_resident`(Payroll_DEV)に年度ごとの履歴として正しく保存されていたが、宛名番号(`addressee_no`)と提出先(`submission`)だけは`mx_staffs`にスタッフ1人につき1つの「現在値」として保存されており、履歴を持たなかった。

- `addressee_no`はcodex由来で本番`dbo.staff`に対応列が無い残骸(既存テーブル`mx_resident`に同名列が未使用のまま存在していた)。`submission`は本番`dbo.staff.submission`にも実在する仕様通りの列。
- `mx_resident`に`submission`列を追加し、`mx_staffs.addressee_no`(13人・114行)/`submission`(52人・151行)を`mx_resident`へ一括バックフィル。`StaffV2Service::residentColumns()`に追加し、入力欄を年度ごとの新規登録・編集フォームへ移動。
- `PayrollV2Controller::buildTransferListView()`(振込一覧の住民税振込先自治体・指定番号解決)が移設前の`mx_staffs.submission`を直接参照していたバグも発覚、`PayrollV2ResidentService::map()`が解決済みの`mx_resident`側を使うよう修正。
- `mx_staffs.submission`は本番同期対象のため列名変更不可能だったが、コード参照が完全に無くなったことを確認後`x_submission`にリネームし同期対象からも除外。`addressee_no`はそもそも本番対応が無いため`x_addressee_no`にリネーム済み。`mx_staffs`側は完全に退避＋同期対象外、`mx_resident`に一本化完了。

## 2026-08-15 reflectApplication()の空欄上書きバグ修正

- 個人情報変更申請の「反映」処理(`reflectApplication()`)で、申請フォーム側は`setai_nushi`(世帯主氏名)/`setai_zoku_gara`(続柄)が任意項目(nullable)なのに、反映処理には「空でなければ上書き」のガードが無く、無条件で`mx_staffs.head_house`/`relationship`を上書きしていた。氏名・住所のみの変更申請(世帯主・続柄を未入力)を反映するたびに、対象スタッフの`head_house`/`relationship`が空文字で消えていた。
- 修正は「申請された値だけ上書きし、未入力(空欄)では上書きしない」パターンに統一。
- この不具合とは別に、動作確認テスト中の接続分離ミスでスタッフ054(長坂楓)の`address`等が実際に上書きされる事故が発生したが、2026-08-15の本番→DEV全件同期で復旧済み。

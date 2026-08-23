# 年末調整 変更履歴

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

# レセ請求取込

## 画面

- /staff/office/receipt/entry

## Controller

- App\\Http\\Controllers\\StaffPortal\\office\\EntryController

## ルール

- CSV取込先は mx_insurance_claim_details。
- 店舗選択は mx_departments.store_category を受け取り、store_short_name を mx_insurance_claim_details.store_name に保存する。
- CSV内に「?」が含まれる場合は文字化けの可能性があるため、取込を中止する。
- 保険者番号は8桁必須。候補にない保険者は mx_insurers に追加する。
- 同じ施術月、同じ店舗のデータが既にある場合は再取込しない。
- 月次済みの月は編集、削除不可。

## 月次仕訳

- 月次処理で mx_journal_entries に請求、窓口、個人振込、自費の仕訳を作成する。
- 対象月は treatment_month と month_date を基準にする。発生日だけで判定しない。
- department_no_old や staff_name_old など old カラムへ戻す処理を作らない。

## 保存・削除の確認（2026-08-23追記）

`save()`（既存行更新の分岐）・`delete()`は、対象の`insurance_claim_detail_id`が既に
存在しない場合でも`update()`/`delete()`の影響行数を見ずに、常に「レセ請求を保存/削除
しました」を返していた（041が主に使う画面の一つのため優先して監査）。0件なら422
（JSON経路）またはerrorMessage付きリダイレクト（フォーム経路）を返すよう修正。
フロント側（`page_script.blade.php`）の通常の保存・削除ボタンは実は素のフォームPOST
（`submitReceiptRow()`）で、複製時の自動保存だけ`fetch`（`saveReceiptRowAsync()`、
`!response.ok`で失敗判定）を使う二重構成。どちらの経路も既存のエラー表示
（`session('errorMessage')`／`showReceiptInlineError()`）にそのまま乗るため、
フロント側の改修は不要だった。

## 入金日の生キー表示バグ（2026-09-05修正）

`mx_insurance_claim_details.payment_date_text`は日付そのものではなく、
`mx_journal_entries.journal_breakdown`と突き合わせるためのキー文字列（例:"6042803"）。
`PaymentConfirmationController`は`payment_entry_occurred_at`（突き合わせ先仕訳の
実際の入金日）を解決して`Y/m/d`表示していたが、`EntryController::index()`の
「入金日」は`payment_date_text`を未加工でそのまま表示していた（041実運用の
問い合わせで発覚、「6040903」が何を意味するか分からないという相談）。
突き合わせクエリ（`paymentEntryOccurredAtQuery()`）を`HandlesStaffPortalContext`
へ共通化し、`EntryController`側もこれを使って`Y/m/d`形式で表示するよう修正。
突き合わせキー自体（`payment_date_text`）は保存・複製ロジックで今まで通り使う
（表示だけを直した。キーとしての役割は変えない）。

## CSV取込で入金名称が自動入力されないバグ（2026-09-12修正）

手入力（`save()`）は画面側で保険者を選ぶとJSが`insurerLookupOptions`
（`mx_insurers.scheduled_payment_name`／`scheduled_payment_name_2`）から`deposit_name`欄を
自動入力する作りだが、`import()`（CSV取込）側は`$importRows[]`に`deposit_name`列自体が
無く、常に未入力のまま保存されていた（041実運用からの報告で発覚）。入金名称は柔整/鍼灸で使う列が違う（`scheduled_payment_name`=柔整、
`scheduled_payment_name_2`=鍼灸、`page_script.blade.js`の`receiptType.indexOf()`判定と
同じ）。CSV取込は店舗を1つだけ選んで行うため、`mx_departments.receipt_type`を取込全体で
1回だけ見てどちらの列を使うか決め、`mx_insurers`の`insurer_number => 該当列`のマップを
作って各行の`deposit_name`をここから引くよう修正。receipt_typeが柔整/鍼灸のどちらでも
ない店舗（未設定の7店舗、2026-09-12時点）は手入力側と同じく空のままにする。

取込直後に新規追加する保険者（`$newInsurers`）は`scheduled_payment_name`／`_2`が空のまま
登録されるため、そちらは従来通り未入力になる（保険者マスタ画面で後から入力してもらう想定、
新規保険者の入金名称をCSVの列から推測して埋める仕組みは無い）。

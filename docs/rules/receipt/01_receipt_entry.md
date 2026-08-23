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

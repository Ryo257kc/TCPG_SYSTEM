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

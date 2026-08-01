# 入金確認

## 画面

- /staff/office/receipt/payment-confirmation

## Controller

- App\\Http\\Controllers\\StaffPortal\\office\\PaymentConfirmationController

## ルール

- 入金確認は mx_journal_entries の医療未収入金と mx_insurance_claim_details を紐づけて確認する。
- payment_date_text は仕訳内訳との紐づけキー。IDを手入力させる設計にしない。
- 複製は元明細をもとに新しい mx_insurance_claim_details 行を作る。
- 元の請求金額が編集不可でも、複製側の修正額、返戻額、入金額で調整できること。
- 保存後は同じ仕訳内訳、同じ会社の入金済金額を集計し、mx_journal_entries.received_amount に反映する。
- 保険者検索、入金名称検索は絞り込み補助。候補選択と自由入力を両立させる。

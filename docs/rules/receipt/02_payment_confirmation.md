# 入金確認

## 画面

- /staff/office/receipt/payment-confirmation

## Controller

- App\\Http\\Controllers\\StaffPortal\\office\\PaymentConfirmationController

## ルール

- 入金確認は mx_journal_entries の医療未収入金と mx_insurance_claim_details を紐づけて確認する。
- payment_date_text は仕訳内訳との紐づけキー。IDを手入力させる設計にしない。
- 複製は元明細をもとに新しい mx_insurance_claim_details 行を作る。
- 複製先の請求金額は必ず `0` にする。元明細の請求金額をコピーしない。
- 複製先の修正額は、複製元の修正額を符号反転して入れる。例: 元が `-62,748` なら複製先は `62,748`。
- 複製時に備考へ追記する金額は、複製元の請求額。文言は `請求額` とし、確認額・残額に変更しない。

- 元の請求金額が編集不可でも、複製側の修正額、返戻額、入金額で調整できること。
- 保存後は同じ仕訳内訳、同じ会社の入金済金額を集計し、mx_journal_entries.received_amount に反映する。
- 保険者検索、入金名称検索は絞り込み補助。候補選択と自由入力を両立させる。

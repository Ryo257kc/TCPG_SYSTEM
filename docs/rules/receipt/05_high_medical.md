# 高額療養

## 画面

- /staff/office/receipt/high_medical

## Controller

- App\\Http\\Controllers\\StaffPortal\\office\\HighMedicalController

## ルール

- 高額療養の判定、金額入力は請求明細に紐づける。
- 入金確認や月次仕訳と金額が連動するため、表示側だけで補正しない。
- 月次済みの明細を変更する場合は、先に月次状態への影響を確認する。

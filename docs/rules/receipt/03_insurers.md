# 保険者マスタ

## 画面

- /staff/office/receipt/insurers

## Controller

- App\\Http\\Controllers\\StaffPortal\\office\\InsurersController

## ルール

- 保険者番号、保険者名、保険区分、入金名称を管理する。
- 入金名称は候補から選択できるようにしつつ、自由入力もできること。
- 入金名称が複数必要な場合は scheduled_payment_name と scheduled_payment_name_2 を使う。
- 保険者番号の重複登録はしない。

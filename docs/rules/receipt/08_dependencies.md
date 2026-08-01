# 請求関連 依存関係

## Controller

- EntryController: レセ請求取込、月次、仕訳作成。
- PaymentConfirmationController: 入金確認、複製、入金済金額反映。
- HomeVisitCounterController: 往診窓口。
- InsurersController: 保険者マスタ。
- HighMedicalController: 高額療養。

## View

- resources/views/staff_portal/office/receipt/entry
- resources/views/staff_portal/office/receipt/payment_confirmation
- resources/views/staff_portal/office/receipt/home_visit_counter
- resources/views/staff_portal/office/receipt/insurers
- resources/views/staff_portal/office/receipt/high_medical

## 触る順番

1. View
2. Controller
3. 共通化できる処理
4. 月次、仕訳作成ロジック

月次、仕訳作成ロジックは影響が大きいため最後に触る。

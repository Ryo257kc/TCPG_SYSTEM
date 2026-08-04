# 売上・仕訳 依存関係

## 売上一覧

- `routes/staff_portal.php`
- `app/Http/Controllers/StaffPortal/Office/OfficeController.php`
- `app/Services/StaffPortal/Office/SalesV2Service.php`
- `resources/views/staff_portal/office/sales/index.blade.php`
- `resources/views/staff_portal/office/sales/print.blade.php`
- `dbo.mx_journal_entries`

## 月間日報・月次処理

- `routes/staff_portal.php`
- `app/Http/Controllers/StaffPortal/Office/StoreDailyReportController.php`
- `resources/views/staff_portal/office/store_daily_report/daily_summary/index.blade.php`
- `resources/views/staff_portal/office/store_daily_report/daily_summary/monthly_print.blade.php`
- `dbo.mx_monthly_closings`
- `dbo.mx_journal_entries`

## 仕訳管理

- `routes/admin/work_v2.php`
- `app/Http/Controllers/Admin/V2/Work/AccountingV2Controller.php`
- `resources/views/admin_v2/work/accounting/journal_entries/index.blade.php`
- `dbo.mx_journal_entries`

## 触る順番

1. View
2. Controller
3. Service
4. 仕訳作成ロジック
5. DB

## 確認順

1. 月間日報の数字を見る。
2. 月次確定する。
3. `mx_journal_entries` に正しい仕訳ができているか見る。
4. 売上一覧に同じ数字が表示されるか見る。

## 共通化

管理側とスタッフ側で同じ帳票・同じ一覧・同じ集計を表示する場合は、Viewまたはshared部品を共通化する。
データ取得Serviceが同じなのに、最後の印刷Viewだけ別れている状態を残さない。
修正時に2箇所直す必要がある構成は整理対象。

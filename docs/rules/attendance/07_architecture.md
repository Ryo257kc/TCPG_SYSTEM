# 勤怠 システム構成

## Controller

- `AttendanceV2Controller`
  - 管理側勤怠。
- `StaffPortal\AttendanceController`
  - スタッフ側勤怠。

## Service

- `AttendanceV2DailyTableItemBuilder`
- `AttendanceV2MonthlySummaryService`
- `AttendanceV2MetricService`
- `AttendanceV2MonthService`
- `AttendanceV2ConfirmService`
- `AttendanceV2ConfirmedStateService`
- `AttendanceV2BulkReflectService`
- `AttendanceV2PaidLeaveUsageService`
- `AttendanceV2PayrollTargetService`

## View

- `resources/views/admin_v2/work/attendance/index.blade.php`
- `resources/views/admin_v2/work/attendance/daily_table.blade.php`
- `resources/views/admin_v2/work/attendance/page_script.blade.php`
- `resources/views/staff_portal/attendance/monthly.blade.php`
- `resources/views/staff_portal/attendance/edit.blade.php`
- `resources/views/staff_portal/admin/attendance/management_detail.blade.php`
- `resources/views/shared/attendance/daily_table_item_plain.blade.php`

## CSS

- 管理側共通: `public/css/admin_v2/`
- スタッフ側共通: `public/css/staff_portal/`
- 勤怠固有のCSSがある場合は、管理側とスタッフ側で見た目の意図をそろえる。

## データの流れ

1. DBから対象月、対象スタッフの勤怠を取得する。
2. 共通ServiceまたはBuilderで日別表示値を作る。
3. 月合計を同じ根拠で作る。
4. 管理側、スタッフ側それぞれのViewへ渡す。
5. 管理側だけ操作ボタンを出す。
6. スタッフ側だけ本人向け操作を出す。
7. 給与反映時は、同じ勤怠計算根拠を使う。

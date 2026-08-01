# 勤怠 依存関係

## 管理側勤怠

```text
admin/attendance
├ AttendanceV2Controller
├ AttendanceV2DailyTableItemBuilder
├ AttendanceV2MonthlySummaryService
├ AttendanceV2MetricService
├ AttendanceV2ConfirmService
├ AttendanceV2ConfirmedStateService
├ AttendanceV2BulkReflectService
├ AttendanceV2PayrollTargetService
├ resources/views/admin_v2/work/attendance/index.blade.php
├ resources/views/admin_v2/work/attendance/daily_table.blade.php
└ resources/views/admin_v2/work/attendance/page_script.blade.php
```

## スタッフ月間勤怠

```text
/staff/attendance/monthly
├ StaffPortal\AttendanceController
├ StaffPortal\Attendance\AttendanceV2DailyTableItemBuilder
├ resources/views/staff_portal/attendance/monthly.blade.php
├ resources/views/staff_portal/attendance/edit.blade.php
└ resources/views/shared/attendance/daily_table_item_plain.blade.php
```


## スタッフポータル管理用勤怠詳細

```text
/staff/admin/attendance/management-detail
├ StaffPortal\AttendanceController
├ AttendanceV2MonthlySummaryService
├ resources/views/staff_portal/admin/attendance/management_detail.blade.php
└ resources/views/shared/attendance/daily_table_item_plain.blade.php
```

上部の勤怠集計はBlade内で計算しない。
`AttendanceV2MonthlySummaryService` の結果を表示する。
## 給与反映

```text
勤怠反映
├ AttendanceV2BulkReflectService
├ AttendanceV2PaidLeaveUsageService
├ AttendanceV2PayrollTargetService
└ PayrollV2Controller / PayrollV2系Service
```

## 触る順番

1. View
2. Controller
3. Service
4. 共通Builder
5. DBカラム

## 注意

- 管理側とスタッフ側の表示差はViewで出し分ける。
- 計算差はService側で吸収し、別計算を作らない。
- 勤怠反映、給与反映は最後に確認する。

## 打刻

/staff/attendance/punch
- StaffPortal\AttendanceController::attendancePunch
- StaffPortal\AttendanceController::attendancePunchStore
- resources/views/staff_portal/attendance_punch/index.blade.php

/staff/attendance/punch-list
- StaffPortal\AttendanceController::punchList
- resources/views/staff_portal/admin/attendance/punch_list.blade.php

打刻は mx_time_cards の実働時刻を更新する。
打刻一覧は表示専用とし、勤怠計算を追加しない。

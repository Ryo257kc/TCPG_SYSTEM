# 勤怠 変更履歴

## 2026-08-01 初版作成

- 管理側勤怠、スタッフ月間勤怠、スタッフポータル管理用勤怠のルールを分ける方針にした。
- 月次集計は `AttendanceV2MonthlySummaryService` を正とする。
- `/staff/attendance/monthly` の月合計を共通Serviceへ寄せた。
- `staff_portal/admin/attendance/management_detail.blade.php` の上部勤怠集計を共通Service表示へ寄せた。
- 参照がない旧日別Service、旧日別表Service、旧出勤日数Serviceを削除対象として整理した。
- staff_portal/admin/attendance/management_detail.blade.php の日別行変換をControllerへ移し、Viewは表示専用に近づけた。
- AttendanceV2DailyTableItemBuilder::summary() を削除し、月次集計は AttendanceV2MonthlySummaryService のみに寄せた。
- AttendanceV2ListSummaryService を削除し、勤怠一覧の月次集計は AttendanceV2MetricService から AttendanceV2MonthlySummaryService を使う形に統一した。

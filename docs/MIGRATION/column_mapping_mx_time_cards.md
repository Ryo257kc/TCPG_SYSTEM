# Column Mapping: `t_time_card` -> `mx_time_cards`

最終更新: 2026-03-23

この表は、旧 `TCPGSYSTEM.dbo.t_time_card` と現 `TCPGSYSTEM_DEV.dbo.mx_time_cards` の順番比較です。  
現DBは、旧DBの順番を維持したまま英語化した前提で整理しています。

## 方針

- 旧DB `t_time_card` を元に新DB `mx_time_cards` を参照する
- `mx_time_cards` 独自列は末尾に追加
- タイムカード同期は `2026/2` で実績確認済み

## カラム比較

| No | 旧DBカラム | 現DBカラム | 備考 |
|---|---|---|---|
| 1 | `time_no` | `time_no` | |
| 2 | `氏名` | `staff_name` | |
| 3 | `日付` | `work_date` | |
| 4 | `区分` | `work_type` | |
| 5 | `区分時間` | `work_type_time` | |
| 6 | `有休使用数` | `paid_leave_used` | |
| 7 | `シフト始業` | `shift_start` | |
| 8 | `シフト退出` | `shift_leave` | |
| 9 | `シフト入出` | `shift_break_out` | |
| 10 | `シフト終業` | `shift_end` | |
| 11 | `シフト所定` | `shift_scheduled` | |
| 12 | `休憩` | `break_minutes` | |
| 13 | `実働始業` | `actual_start` | |
| 14 | `実働退出` | `actual_leave` | |
| 15 | `実働入出` | `actual_break_out` | |
| 16 | `実働終業` | `actual_end` | |
| 17 | `実働所定` | `actual_scheduled` | |
| 18 | `残業` | `overtime` | |
| 19 | `残業2` | `overtime2` | |
| 20 | `深夜残業` | `night_over_time` | |
| 21 | `打刻備考` | `timecard_note` | |
| 22 | `勤務店舗` | `work_store` | |
| 23 | `変更始業` | `change_start` | |
| 24 | `変更退出` | `change_leave` | |
| 25 | `変更入出` | `change_break_out` | |
| 26 | `変更終業` | `change_end` | |
| 27 | `変更所定` | `change_scheduled` | |
| 28 | `本人申請` | `staff_request` | |
| 29 | `本人申請ch` | `staff_request_ch` | |
| 30 | `管理者承認` | `manager_approval` | |
| 31 | `管理者` | `manager_name` | |
| 32 | `有休申請日時` | `paid_leave_requested_at` | |
| 33 | `差戻し` | `is_returned` | |
| 34 | `差戻し備考` | `return_note` | |
| 35 | `日額交通費` | `daily_transport_fee` | |
| 36 | - | `holiday_category` | 現DB独自 |
| 37 | - | `attendance_checked` | 現DB独自 |
| 38 | - | `attendance_checked_at` | 現DB独自 |
| 39 | - | `attendance_checked_by` | 現DB独自 |

## 確認メモ

- `t_time_card -> mx_time_cards` の同期は `2026/2` で実施確認あり
- 独自列 `holiday_category` と `attendance_checked*` は同期対象外
- `mx_time_cards.time_no` は IDENTITY のため、同期時は明示挿入しない

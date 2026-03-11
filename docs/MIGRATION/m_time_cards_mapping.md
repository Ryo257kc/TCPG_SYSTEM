# m_time_cards Mapping (Admin)

## Policy
- Canonical table is fixed to `dbo.m_time_cards`.
- Legacy table `dbo.t_time_card` must not be referenced from admin code.
- Column-name differences are handled only inside `m_time_cards` mapping logic.

## Core Keys
- Staff key candidates: `staff_id`, `staff_code`, `staff_name` (normalized in code)
- Date key candidates: `work_date`, `date`
- Row key candidates: `time_card_id`, `time_no`, `no`

## Attendance Category
- Holiday category: `work_holiday`, `work_type`, `holiday_type`
- Attendance category: `attendance_category`, `work_category`, `category`, `kintai_category`, `kubun`, `work_type`
- Category time: `attendance_category_time`, `work_type_time`, `category_time`
- Paid leave used: `paid_leave_used`, `paid_leave`, `paid_leave_num`, `horiday_true`

## Shift
- Start: `shift_start`, `shift_in`
- Leave: `shift_leave`, `shift_exit`
- Break out: `shift_break_out`, `shift_entry`
- End: `shift_end`, `shift_out`
- Scheduled: `shift_scheduled`, `shift_work`

## Actual Punch
- Start: `actual_start`, `actual_in`
- Leave: `actual_leave`, `actual_exit`
- Break out: `actual_break_out`, `actual_entry`
- End: `actual_end`, `actual_out`
- Scheduled: `actual_scheduled`, `actual_work`

## Change Record
- Start: `change_start`, `edit_start`
- Leave: `change_leave`, `edit_leave`
- Break out: `change_break_out`, `edit_break_out`
- End: `change_end`, `edit_end`
- Scheduled: `change_scheduled`, `edit_work`

## Time Values
- Overtime: `overtime`, `over_time`, `overtime_hours`
- Night overtime: `night_over_time`, `night_overtime`, `night_overtime_hours`

## Status / Note
- Staff request: `staff_request`, `staff_request_ch`
- Manager approval: `manager_approval`
- Note: `timecard_note`, `remark`, `memo`, `note`, `attendance_note`, `punch_note`

## Store
- Store fields: `work_store`, `section`

## Calendar Dependency
- Holiday display uses `m_calendars.work_holiday`.
- When shift is generated, holiday information should be reflected into `m_time_cards`.
- If required columns are missing in `m_time_cards`, add columns first. Do not fallback to legacy table.

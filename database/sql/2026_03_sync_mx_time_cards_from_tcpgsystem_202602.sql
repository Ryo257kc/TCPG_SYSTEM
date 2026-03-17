SET NOCOUNT ON;
GO

/*
目的:
- TCPGSYSTEM.dbo.t_time_card を正として
- TCPGSYSTEM_DEV.dbo.mx_time_cards の 2026/02 を同期する

方針:
- 既存行は UPDATE
- 無い行だけ INSERT
- 新DB独自列は触らない
  - holiday_category
  - attendance_checked
  - attendance_checked_at
  - attendance_checked_by

注意:
- 読み取り元: TCPGSYSTEM
- 書き込み先: TCPGSYSTEM_DEV
- まずは COMMIT の前に件数を確認すること
*/

BEGIN TRANSACTION;

-- 1. 事前件数確認
SELECT COUNT(*) AS old_count
FROM TCPGSYSTEM.dbo.t_time_card
WHERE [日付] >= '2026-02-01'
  AND [日付] <  '2026-03-01';

SELECT COUNT(*) AS new_count_before
FROM TCPGSYSTEM_DEV.dbo.mx_time_cards
WHERE work_date >= '2026-02-01'
  AND work_date <  '2026-03-01';

-- 2. 既存行を更新
UPDATE dst
SET
    dst.work_type               = src.[区分],
    dst.work_type_time          = src.[区分時間],
    dst.paid_leave_used         = src.[有休使用数],
    dst.shift_start             = src.[シフト始業],
    dst.shift_leave             = src.[シフト退出],
    dst.shift_break_out         = src.[シフト入出],
    dst.shift_end               = src.[シフト終業],
    dst.shift_scheduled         = src.[シフト所定],
    dst.break_minutes           = src.[休憩],
    dst.actual_start            = src.[実働始業],
    dst.actual_leave            = src.[実働退出],
    dst.actual_break_out        = src.[実働入出],
    dst.actual_end              = src.[実働終業],
    dst.actual_scheduled        = src.[実働所定],
    dst.overtime                = src.[残業],
    dst.overtime2               = src.[残業2],
    dst.night_over_time         = src.[深夜残業],
    dst.timecard_note           = src.[打刻備考],
    dst.work_store              = src.[勤務店舗],
    dst.change_start            = src.[変更始業],
    dst.change_leave            = src.[変更退出],
    dst.change_break_out        = src.[変更入出],
    dst.change_end              = src.[変更終業],
    dst.change_scheduled        = src.[変更所定],
    dst.staff_request           = src.[本人申請],
    dst.staff_request_ch        = src.[本人申請ch],
    dst.manager_approval        = src.[管理者承認],
    dst.manager_name            = src.[管理者],
    dst.paid_leave_requested_at = src.[有休申請日時],
    dst.is_returned             = src.[差戻し],
    dst.return_note             = src.[差戻し備考],
    dst.daily_transport_fee     = src.[日額交通費]
FROM TCPGSYSTEM_DEV.dbo.mx_time_cards dst
JOIN TCPGSYSTEM.dbo.t_time_card src
  ON LTRIM(RTRIM(dst.staff_name)) = LTRIM(RTRIM(src.[氏名]))
 AND CONVERT(date, dst.work_date) = CONVERT(date, src.[日付])
WHERE src.[日付] >= '2026-02-01'
  AND src.[日付] <  '2026-03-01';

-- 3. 無い行だけ追加
INSERT INTO TCPGSYSTEM_DEV.dbo.mx_time_cards (
    staff_name,
    work_date,
    work_type,
    work_type_time,
    paid_leave_used,
    shift_start,
    shift_leave,
    shift_break_out,
    shift_end,
    shift_scheduled,
    break_minutes,
    actual_start,
    actual_leave,
    actual_break_out,
    actual_end,
    actual_scheduled,
    overtime,
    overtime2,
    night_over_time,
    timecard_note,
    work_store,
    change_start,
    change_leave,
    change_break_out,
    change_end,
    change_scheduled,
    staff_request,
    staff_request_ch,
    manager_approval,
    manager_name,
    paid_leave_requested_at,
    is_returned,
    return_note,
    daily_transport_fee
)
SELECT
    LTRIM(RTRIM(src.[氏名])),
    src.[日付],
    src.[区分],
    src.[区分時間],
    src.[有休使用数],
    src.[シフト始業],
    src.[シフト退出],
    src.[シフト入出],
    src.[シフト終業],
    src.[シフト所定],
    src.[休憩],
    src.[実働始業],
    src.[実働退出],
    src.[実働入出],
    src.[実働終業],
    src.[実働所定],
    src.[残業],
    src.[残業2],
    src.[深夜残業],
    src.[打刻備考],
    src.[勤務店舗],
    src.[変更始業],
    src.[変更退出],
    src.[変更入出],
    src.[変更終業],
    src.[変更所定],
    src.[本人申請],
    src.[本人申請ch],
    src.[管理者承認],
    src.[管理者],
    src.[有休申請日時],
    src.[差戻し],
    src.[差戻し備考],
    src.[日額交通費]
FROM TCPGSYSTEM.dbo.t_time_card src
WHERE src.[日付] >= '2026-02-01'
  AND src.[日付] <  '2026-03-01'
  AND NOT EXISTS (
      SELECT 1
      FROM TCPGSYSTEM_DEV.dbo.mx_time_cards dst
      WHERE LTRIM(RTRIM(dst.staff_name)) = LTRIM(RTRIM(src.[氏名]))
        AND CONVERT(date, dst.work_date) = CONVERT(date, src.[日付])
  );

-- 4. 事後件数確認
SELECT COUNT(*) AS new_count_after
FROM TCPGSYSTEM_DEV.dbo.mx_time_cards
WHERE work_date >= '2026-02-01'
  AND work_date <  '2026-03-01';

COMMIT TRANSACTION;
GO


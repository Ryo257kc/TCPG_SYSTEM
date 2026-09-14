/*
目的:
- staff042（大道博史）: 本日08:01に本番(TCPGSYSTEM)側で管理者承認された2026年8月分を
  DEV(TCPGSYSTEM_DEV)へ反映する。042だけに絞る（8月は18人中15人が既に勤怠確定済みの
  ため、対象を絞らず同期スクリプトを再実行すると確定済みデータまで上書きしてしまう）。
- staff075（岩橋佑典）: 2026年8月分のholiday_categoryが、日付に関係なく全日「休日」に
  なっていた（原因未特定・過去のデータ不整合、実データで確認済み：現在のkihon_shifts+
  AttendanceV2HolidayCategoryServiceで再計算すると平日/休日/祝日が正しく分かれる）。
  併せて、カレンダーのwork_holiday値「会社休」がAttendanceV2HolidayCategoryService::
  normalizeCategory()に未登録でnull落ちし、public_holiday側（備考「盆休み」）が優先されて
  8/13-15が誤って「祝日」になる不具合も発見・修正済み（同日、app/Services/Admin/V2/
  Attendance/AttendanceV2HolidayCategoryService.php）。

このSQLは実行ログ・再現手順の記録用（実際の適用は2026-09-14、tinker経由でトランザクション
内実行・件数確認・COMMIT済み）。

注意:
- 読み取り元: TCPGSYSTEM
- 書き込み先: TCPGSYSTEM_DEV
*/

-- 1. staff042: 本人申請・管理者承認等の再同期（042のみ、他スタッフには触れない）
BEGIN TRANSACTION;

UPDATE dst
SET
    dst.work_type               = src.[区分],
    dst.work_type_time          = src.[区分時間],
    dst.paid_leave_used         = src.[有休使用数],
    dst.shift_start             = src.[シフト始業],
    dst.shift_leave              = src.[シフト退出],
    dst.shift_break_out          = src.[シフト入出],
    dst.shift_end                = src.[シフト終業],
    dst.actual_start             = src.[実働始業],
    dst.actual_leave              = src.[実働退出],
    dst.actual_break_out          = src.[実働入出],
    dst.actual_end                = src.[実働終業],
    dst.overtime                  = src.[残業],
    dst.night_over_time           = src.[深夜残業],
    dst.timecard_note             = src.[打刻備考],
    dst.work_store                = src.[勤務店舗],
    dst.change_start              = src.[変更始業],
    dst.change_leave              = src.[変更退出],
    dst.change_break_out          = src.[変更入出],
    dst.change_end                = src.[変更終業],
    dst.change_scheduled          = src.[変更所定],
    dst.staff_request             = src.[本人申請],
    dst.staff_request_ch          = src.[本人申請ch],
    dst.manager_approval          = src.[管理者承認],
    dst.manager_name              = src.[管理者],
    dst.paid_leave_requested_at   = src.[有休申請日時],
    dst.is_returned                = src.[差戻し],
    dst.return_note                = src.[差戻し備考],
    dst.daily_transport_fee        = src.[日額交通費]
FROM TCPGSYSTEM_DEV.dbo.mx_time_cards dst
JOIN TCPGSYSTEM.dbo.t_time_card src
  ON LTRIM(RTRIM(dst.staff_name)) = LTRIM(RTRIM(src.[氏名]))
 AND CONVERT(date, dst.work_date) = CONVERT(date, src.[日付])
WHERE src.[日付] >= '2026-08-01'
  AND src.[日付] <  '2026-09-01'
  AND LTRIM(RTRIM(src.[氏名])) = '042';

-- 実行結果: 31行更新、manager_approval等が反映されたことを確認してCOMMIT。

COMMIT TRANSACTION;

-- 2. staff075: holiday_categoryの再計算・修正
-- 実際の適用はPHP側（AttendanceV2HolidayCategoryService::resolve()）で1日ずつ計算し、
-- time_no単位でUPDATEした（このSQLで一括再現するものではなく、手順の記録用）。
-- 対象: staff_name='075' AND work_date が2026年8月 AND attendance_checked=0（全31日が対象）
-- 結果: 8/2,9,13,14,15,16,23,30 → 休日 / 8/11 → 祝日 / それ以外の平日 → 平日

SET NOCOUNT ON;

BEGIN TRY
    BEGIN TRAN;

    IF COL_LENGTH('dbo.mx_time_cards', 'holiday_category') IS NULL
    BEGIN
        ALTER TABLE dbo.mx_time_cards
            ADD holiday_category NVARCHAR(20) NULL;
    END;

UPDATE tc
   SET holiday_category = CASE
        WHEN cal.public_holiday_name IS NOT NULL THEN N'祝日'
        WHEN cal.work_holiday_name IS NOT NULL THEN N'休日'
        WHEN NULLIF(LTRIM(RTRIM(CAST(tc.shift_start AS NVARCHAR(50)))), N'') IS NULL
         AND NULLIF(LTRIM(RTRIM(CAST(tc.shift_leave AS NVARCHAR(50)))), N'') IS NULL
         AND NULLIF(LTRIM(RTRIM(CAST(tc.shift_break_out AS NVARCHAR(50)))), N'') IS NULL
         AND NULLIF(LTRIM(RTRIM(CAST(tc.shift_end AS NVARCHAR(50)))), N'') IS NULL THEN N'休日'
        ELSE N'平日'
       END
    FROM dbo.mx_time_cards tc
OUTER APPLY (
    SELECT TOP 1
        NULLIF(LTRIM(RTRIM(CAST(c.public_holiday AS NVARCHAR(50)))), N'') AS public_holiday_name,
        NULLIF(LTRIM(RTRIM(CAST(c.work_holiday AS NVARCHAR(50)))), N'') AS work_holiday_name
    FROM dbo.mx_calendar c
    WHERE CONVERT(date, c.calendar_day) = CONVERT(date, tc.work_date)
) cal
WHERE NULLIF(LTRIM(RTRIM(CAST(tc.holiday_category AS NVARCHAR(20)))), N'') IS NULL;

    COMMIT;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0
        ROLLBACK;

    THROW;
END CATCH;

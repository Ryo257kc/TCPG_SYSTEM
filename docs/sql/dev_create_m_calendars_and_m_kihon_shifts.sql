/*
  TCPGSYSTEM_DEV
  Create m_ master tables for attendance and migrate data from legacy tables.
  Safe to run multiple times (upsert style).
*/

SET NOCOUNT ON;

/* =========================================================
   1) m_calendars
   ========================================================= */
IF OBJECT_ID(N'dbo.m_calendars', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.m_calendars (
        calendar_id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        calendar_day DATE NOT NULL,
        work_holiday NVARCHAR(50) NULL,
        created_at DATETIME2(0) NOT NULL CONSTRAINT DF_m_calendars_created_at DEFAULT SYSUTCDATETIME(),
        updated_at DATETIME2(0) NOT NULL CONSTRAINT DF_m_calendars_updated_at DEFAULT SYSUTCDATETIME()
    );

    CREATE UNIQUE INDEX UX_m_calendars_day ON dbo.m_calendars (calendar_day);
END;

IF OBJECT_ID(N'dbo.t_calendar', N'U') IS NOT NULL
BEGIN
    ;WITH src AS (
        SELECT
            CONVERT(date, calendar_day) AS calendar_day,
            NULLIF(LTRIM(RTRIM(CAST(work_holiday AS NVARCHAR(50)))), N'') AS work_holiday
        FROM dbo.t_calendar
        WHERE calendar_day IS NOT NULL
    )
    MERGE dbo.m_calendars AS tgt
    USING src
        ON tgt.calendar_day = src.calendar_day
    WHEN MATCHED THEN
        UPDATE SET
            tgt.work_holiday = src.work_holiday,
            tgt.updated_at = SYSUTCDATETIME()
    WHEN NOT MATCHED BY TARGET THEN
        INSERT (calendar_day, work_holiday)
        VALUES (src.calendar_day, src.work_holiday);
END;

/* =========================================================
   2) m_kihon_shifts
   ========================================================= */
IF OBJECT_ID(N'dbo.m_kihon_shifts', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.m_kihon_shifts (
        kihon_shift_id BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        staff_code NVARCHAR(50) NOT NULL,
        week_key NVARCHAR(20) NOT NULL,       -- expected: 日/月/火/水/木/金/土 or 0..6
        shift_in TIME(0) NULL,
        shift_exit TIME(0) NULL,
        shift_entry TIME(0) NULL,
        shift_out TIME(0) NULL,
        rou_time DECIMAL(8,2) NULL,
        section NVARCHAR(50) NULL,
        created_at DATETIME2(0) NOT NULL CONSTRAINT DF_m_kihon_shifts_created_at DEFAULT SYSUTCDATETIME(),
        updated_at DATETIME2(0) NOT NULL CONSTRAINT DF_m_kihon_shifts_updated_at DEFAULT SYSUTCDATETIME()
    );

    CREATE UNIQUE INDEX UX_m_kihon_shifts_staff_week ON dbo.m_kihon_shifts (staff_code, week_key);
END;

IF OBJECT_ID(N'dbo.t_kihon_shift', N'U') IS NOT NULL
BEGIN
    ;WITH src AS (
        SELECT
            NULLIF(LTRIM(RTRIM(CAST(staff_name AS NVARCHAR(50)))), N'') AS staff_code,
            NULLIF(LTRIM(RTRIM(CAST([week] AS NVARCHAR(20)))), N'') AS week_key,
            TRY_CONVERT(TIME(0), shift_in) AS shift_in,
            TRY_CONVERT(TIME(0), shift_exit) AS shift_exit,
            TRY_CONVERT(TIME(0), shift_entry) AS shift_entry,
            TRY_CONVERT(TIME(0), shift_out) AS shift_out,
            TRY_CONVERT(DECIMAL(8,2), rou_time) AS rou_time,
            NULLIF(LTRIM(RTRIM(CAST(section AS NVARCHAR(50)))), N'') AS section
        FROM dbo.t_kihon_shift
    )
    MERGE dbo.m_kihon_shifts AS tgt
    USING (
        SELECT *
        FROM src
        WHERE staff_code IS NOT NULL
          AND week_key IS NOT NULL
    ) AS s
        ON tgt.staff_code = s.staff_code
       AND tgt.week_key = s.week_key
    WHEN MATCHED THEN
        UPDATE SET
            tgt.shift_in = s.shift_in,
            tgt.shift_exit = s.shift_exit,
            tgt.shift_entry = s.shift_entry,
            tgt.shift_out = s.shift_out,
            tgt.rou_time = s.rou_time,
            tgt.section = s.section,
            tgt.updated_at = SYSUTCDATETIME()
    WHEN NOT MATCHED BY TARGET THEN
        INSERT (staff_code, week_key, shift_in, shift_exit, shift_entry, shift_out, rou_time, section)
        VALUES (s.staff_code, s.week_key, s.shift_in, s.shift_exit, s.shift_entry, s.shift_out, s.rou_time, s.section);
END;

/* =========================================================
   3) quick check
   ========================================================= */
SELECT 'm_calendars' AS table_name, COUNT(*) AS row_count FROM dbo.m_calendars
UNION ALL
SELECT 'm_kihon_shifts' AS table_name, COUNT(*) AS row_count FROM dbo.m_kihon_shifts;

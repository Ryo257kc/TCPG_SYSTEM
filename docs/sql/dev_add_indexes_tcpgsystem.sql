/*
  TCPGSYSTEM_DEV (sqlsrv connection)
  Minimal performance indexes for attendance/admin screens.
*/
SET NOCOUNT ON;

/* m_time_cards: staff detail(month), list(month/all staff) */
IF OBJECT_ID(N'dbo.m_time_cards', N'U') IS NOT NULL
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM sys.indexes
        WHERE object_id = OBJECT_ID(N'dbo.m_time_cards')
          AND name = N'IX_m_time_cards_staff_date'
    )
    BEGIN
        CREATE NONCLUSTERED INDEX IX_m_time_cards_staff_date
            ON dbo.m_time_cards (staff_name ASC, work_date ASC);
    END;

    IF NOT EXISTS (
        SELECT 1
        FROM sys.indexes
        WHERE object_id = OBJECT_ID(N'dbo.m_time_cards')
          AND name = N'IX_m_time_cards_date_staff'
    )
    BEGIN
        CREATE NONCLUSTERED INDEX IX_m_time_cards_date_staff
            ON dbo.m_time_cards (work_date ASC, staff_name ASC);
    END;
END;

/* quick check */
SELECT
    t.name AS table_name,
    i.name AS index_name,
    i.is_unique
FROM sys.indexes i
JOIN sys.tables t ON t.object_id = i.object_id
WHERE t.name IN (N'm_time_cards')
  AND i.is_hypothetical = 0
ORDER BY t.name, i.name;


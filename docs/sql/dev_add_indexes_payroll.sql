/*
  Payroll_DEV (sqlsrv_payroll connection)
  Minimal performance indexes for payroll list/detail screens.
*/
SET NOCOUNT ON;

IF OBJECT_ID(N'dbo.m_payroll_entries', N'U') IS NOT NULL
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM sys.indexes
        WHERE object_id = OBJECT_ID(N'dbo.m_payroll_entries')
          AND name = N'IX_m_payroll_entries_month_bonus_staff'
    )
    BEGIN
        CREATE NONCLUSTERED INDEX IX_m_payroll_entries_month_bonus_staff
            ON dbo.m_payroll_entries (supply_month ASC, is_bonus ASC, staff_code ASC);
    END;

    IF NOT EXISTS (
        SELECT 1
        FROM sys.indexes
        WHERE object_id = OBJECT_ID(N'dbo.m_payroll_entries')
          AND name = N'IX_m_payroll_entries_staff_month_bonus'
    )
    BEGIN
        CREATE NONCLUSTERED INDEX IX_m_payroll_entries_staff_month_bonus
            ON dbo.m_payroll_entries (staff_code ASC, supply_month ASC, is_bonus ASC);
    END;
END;

/* quick check */
SELECT
    t.name AS table_name,
    i.name AS index_name,
    i.is_unique
FROM sys.indexes i
JOIN sys.tables t ON t.object_id = i.object_id
WHERE t.name IN (N'm_payroll_entries')
  AND i.is_hypothetical = 0
ORDER BY t.name, i.name;


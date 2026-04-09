/*
  Payroll_DEV (sqlsrv_payroll connection)
  Create TCPGSYSTEM -> TCPGSYSTEM_DEV table mapping registry in Payroll_DEV
  and seed initial rows.

  Created table:
    - Payroll_DEV.dbo.m_db_table_mappings
*/
SET NOCOUNT ON;

USE Payroll_DEV;
GO

IF OBJECT_ID(N'dbo.m_db_table_mappings', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.m_db_table_mappings (
        mapping_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        legacy_db NVARCHAR(128) NULL,
        legacy_schema NVARCHAR(128) NULL,
        legacy_table NVARCHAR(255) NULL,
        new_db NVARCHAR(128) NULL,
        new_schema NVARCHAR(128) NULL,
        new_table NVARCHAR(255) NULL,
        note NVARCHAR(255) NULL,
        is_active BIT NOT NULL CONSTRAINT DF_m_db_table_mappings_is_active DEFAULT (1),
        created_at DATETIME2(0) NOT NULL CONSTRAINT DF_m_db_table_mappings_created_at DEFAULT SYSUTCDATETIME(),
        updated_at DATETIME2(0) NOT NULL CONSTRAINT DF_m_db_table_mappings_updated_at DEFAULT SYSUTCDATETIME()
    );
END;

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.m_db_table_mappings')
      AND name = N'IX_m_db_table_mappings_legacy'
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_m_db_table_mappings_legacy
        ON dbo.m_db_table_mappings (legacy_db, legacy_schema, legacy_table);
END;

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.m_db_table_mappings')
      AND name = N'IX_m_db_table_mappings_new'
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_m_db_table_mappings_new
        ON dbo.m_db_table_mappings (new_db, new_schema, new_table);
END;

MERGE dbo.m_db_table_mappings AS target
USING (
    SELECT
        v.legacy_db,
        v.legacy_schema,
        v.legacy_table,
        v.new_db,
        v.new_schema,
        v.new_table,
        v.note,
        v.is_active
    FROM (VALUES
        (N'TCPGSYSTEM', N'dbo', N'T_勘定科目',         N'TCPGSYSTEM_DEV', N'dbo', N'mx_account_titles',          CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'TCPGSYSTEM', N'dbo', N'T_宛名',             N'TCPGSYSTEM_DEV', N'dbo', N'mx_addressees',              CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'TCPGSYSTEM', N'dbo', N't_calendar',         N'TCPGSYSTEM_DEV', N'dbo', N'mx_calendar',                CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'TCPGSYSTEM', N'dbo', N'T_修正',             N'TCPGSYSTEM_DEV', N'dbo', N'mx_corrections',             CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'TCPGSYSTEM', N'dbo', N'T_部門',             N'TCPGSYSTEM_DEV', N'dbo', N'mx_departments',             CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'TCPGSYSTEM', N'dbo', N'T_保険請求内訳',     N'TCPGSYSTEM_DEV', N'dbo', N'mx_insurance_claim_details', CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'TCPGSYSTEM', N'dbo', N'T_保険者',           N'TCPGSYSTEM_DEV', N'dbo', N'mx_insurers',                CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'TCPGSYSTEM', N'dbo', N'T_請求明細',         N'TCPGSYSTEM_DEV', N'dbo', N'mx_invoice_details',         CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'TCPGSYSTEM', N'dbo', N'T_請求書',           N'TCPGSYSTEM_DEV', N'dbo', N'mx_invoices',                CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'TCPGSYSTEM', N'dbo', N'T_仕訳帳',           N'TCPGSYSTEM_DEV', N'dbo', N'mx_journal_entries',         CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'TCPGSYSTEM', N'dbo', N't_kihon_shift',      N'TCPGSYSTEM_DEV', N'dbo', N'mx_kihon_shifts',            CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'TCPGSYSTEM', N'dbo', N'T_借入返済詳細',     N'TCPGSYSTEM_DEV', N'dbo', N'mx_loan_repayment_details',  CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'TCPGSYSTEM', N'dbo', N'T_借入返済',         N'TCPGSYSTEM_DEV', N'dbo', N'mx_loan_repayments',         CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'TCPGSYSTEM', N'dbo', N't_message',          N'TCPGSYSTEM_DEV', N'dbo', N'mx_message',                 CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'TCPGSYSTEM', N'dbo', N'T_月次',             N'TCPGSYSTEM_DEV', N'dbo', N'mx_monthly_closings',        CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'TCPGSYSTEM', N'dbo', N'staff',              N'TCPGSYSTEM_DEV', N'dbo', N'mx_staffs',                  CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'TCPGSYSTEM', N'dbo', N't_time_card',        N'TCPGSYSTEM_DEV', N'dbo', N'mx_time_cards',              CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'TCPGSYSTEM', N'dbo', N'T_法人店舗',         N'TCPGSYSTEM_DEV', N'dbo', N'mx_stores',                  N'法人店舗:店舗系',         CAST(1 AS BIT)),
        (N'TCPGSYSTEM', N'dbo', N'T_法人店舗',         N'TCPGSYSTEM_DEV', N'dbo', N'mx_companies',               N'法人店舗:会社系',         CAST(1 AS BIT))
    ) AS v(legacy_db, legacy_schema, legacy_table, new_db, new_schema, new_table, note, is_active)
) AS source
ON  target.legacy_db     = source.legacy_db
AND target.legacy_schema = source.legacy_schema
AND target.legacy_table  = source.legacy_table
AND target.new_db        = source.new_db
AND target.new_schema    = source.new_schema
AND target.new_table     = source.new_table
WHEN MATCHED THEN
    UPDATE SET
        note = source.note,
        is_active = source.is_active,
        updated_at = SYSUTCDATETIME()
WHEN NOT MATCHED THEN
    INSERT (
        legacy_db,
        legacy_schema,
        legacy_table,
        new_db,
        new_schema,
        new_table,
        note,
        is_active
    )
    VALUES (
        source.legacy_db,
        source.legacy_schema,
        source.legacy_table,
        source.new_db,
        source.new_schema,
        source.new_table,
        source.note,
        source.is_active
    );

SELECT
    mapping_id,
    legacy_db,
    legacy_schema,
    legacy_table,
    new_db,
    new_schema,
    new_table,
    note,
    is_active
FROM dbo.m_db_table_mappings
ORDER BY mapping_id;

/*
  Payroll_DEV (sqlsrv_payroll connection)
  Create Payroll -> Payroll_DEV column mapping registry in Payroll_DEV
  and seed starter rows for payroll master tables.

  Prerequisite:
    - dbo.m_db_table_mappings already exists in Payroll_DEV

  Created table:
    - Payroll_DEV.dbo.m_db_column_mappings
*/
SET NOCOUNT ON;
GO

USE Payroll_DEV;
GO

IF OBJECT_ID(N'dbo.m_db_column_mappings', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.m_db_column_mappings (
        column_mapping_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        table_mapping_id INT NOT NULL,
        legacy_column NVARCHAR(255) NOT NULL,
        new_column NVARCHAR(255) NOT NULL,
        note NVARCHAR(255) NULL,
        is_active BIT NOT NULL CONSTRAINT DF_m_db_column_mappings_is_active DEFAULT (1),
        created_at DATETIME2(0) NOT NULL CONSTRAINT DF_m_db_column_mappings_created_at DEFAULT SYSUTCDATETIME(),
        updated_at DATETIME2(0) NOT NULL CONSTRAINT DF_m_db_column_mappings_updated_at DEFAULT SYSUTCDATETIME(),
        CONSTRAINT FK_m_db_column_mappings_table_mapping
            FOREIGN KEY (table_mapping_id) REFERENCES dbo.m_db_table_mappings(mapping_id)
    );
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.m_db_column_mappings')
      AND name = N'IX_m_db_column_mappings_table_mapping'
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_m_db_column_mappings_table_mapping
        ON dbo.m_db_column_mappings (table_mapping_id, legacy_column, new_column);
END;
GO

;WITH source_rows AS (
    SELECT *
    FROM (VALUES
        (N't_rouho',      N'mx_rouho',      N'office_no',          N'office_no',          CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_rouho',      N'mx_rouho',      N'rou_industry',       N'rou_industry',       CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_rouho',      N'mx_rouho',      N'aggregate_period',   N'aggregate_period',   CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_rouho',      N'mx_rouho',      N'rou_apply_date',     N'rou_apply_date',     CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_rouho',      N'mx_rouho',      N'general_st',         N'general_st',         CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_rouho',      N'mx_rouho',      N'general_of',         N'general_of',         CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_rouho',      N'mx_rouho',      N'nourin_st',          N'nourin_st',          CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_rouho',      N'mx_rouho',      N'nourin_of',          N'nourin_of',          CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_rouho',      N'mx_rouho',      N'kensetu_st',         N'kensetu_st',         CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_rouho',      N'mx_rouho',      N'kensetu_of',         N'kensetu_of',         CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_rouho',      N'mx_rouho',      N'rousai',             N'rousai',             CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),

        (N't_syaho',      N'mx_syaho',      N'office_no',          N'office_no',          CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_syaho',      N'mx_syaho',      N'kenpo_apply_date',   N'kenpo_apply_date',   CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_syaho',      N'mx_syaho',      N'kenpo_rate',        N'kenpo_rate',        CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_syaho',      N'mx_syaho',      N'kenpo_shoyo',        N'kenpo_shoyo',        CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_syaho',      N'mx_syaho',      N'kaigo_apply_date',   N'kaigo_apply_date',   CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_syaho',      N'mx_syaho',      N'kaigo_rate',        N'kaigo_rate',        CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_syaho',      N'mx_syaho',      N'kaigo_shoyo',        N'kaigo_shoyo',        CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_syaho',      N'mx_syaho',      N'kou_apply_date',     N'kou_apply_date',     CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_syaho',      N'mx_syaho',      N'kou_rate',          N'kou_rate',          CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_syaho',      N'mx_syaho',      N'kou_shoyo',          N'kou_shoyo',          CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_syaho',      N'mx_syaho',      N'jidou_apply_date',   N'jidou_apply_date',   CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_syaho',      N'mx_syaho',      N'jidou_rate',        N'jidou_rate',        CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_syaho',      N'mx_syaho',      N'jidou_shoyo',        N'jidou_shoyo',        CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_syaho',      N'mx_syaho',      N'basic_payment_days', N'basic_payment_days', CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),

        (N't_staff_shou', N'mx_staff_shou', N'staff_id',           N'staff_id',           N'要確認: staff_code 運用なら調整', CAST(1 AS BIT)),
        (N't_staff_shou', N'mx_staff_shou', N'raise_year',         N'raise_year',         CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_staff_shou', N'mx_staff_shou', N'kenpo_amo',          N'kenpo_amo',          CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_staff_shou', N'mx_staff_shou', N'kaigo_amo',          N'kaigo_amo',          CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_staff_shou', N'mx_staff_shou', N'kounen_amo',         N'kounen_amo',         CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_staff_shou', N'mx_staff_shou', N'kenpo_monthly_amo',  N'kenpo_monthly_amo',  CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_staff_shou', N'mx_staff_shou', N'kounen_monthly_amo', N'kounen_monthly_amo', CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_staff_shou', N'mx_staff_shou', N'kenpo_toukyu',       N'kenpo_toukyu',       CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N't_staff_shou', N'mx_staff_shou', N'kounen_toukyu',      N'kounen_toukyu',      CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT))
    ) AS v(legacy_table, new_table, legacy_column, new_column, note, is_active)
),
resolved_rows AS (
    SELECT
        tm.mapping_id AS table_mapping_id,
        s.legacy_column,
        s.new_column,
        s.note,
        s.is_active
    FROM source_rows s
    INNER JOIN dbo.m_db_table_mappings tm
        ON tm.legacy_db = N'Payroll'
       AND tm.legacy_schema = N'dbo'
       AND tm.legacy_table = s.legacy_table
       AND tm.new_db = N'Payroll_DEV'
       AND tm.new_schema = N'dbo'
       AND tm.new_table = s.new_table
)
MERGE dbo.m_db_column_mappings AS target
USING resolved_rows AS source
ON  target.table_mapping_id = source.table_mapping_id
AND target.legacy_column    = source.legacy_column
AND target.new_column       = source.new_column
WHEN MATCHED THEN
    UPDATE SET
        note = source.note,
        is_active = source.is_active,
        updated_at = SYSUTCDATETIME()
WHEN NOT MATCHED THEN
    INSERT (
        table_mapping_id,
        legacy_column,
        new_column,
        note,
        is_active
    )
    VALUES (
        source.table_mapping_id,
        source.legacy_column,
        source.new_column,
        source.note,
        source.is_active
    );
GO

SELECT
    cm.column_mapping_id,
    tm.legacy_table,
    cm.legacy_column,
    tm.new_table,
    cm.new_column,
    cm.note,
    cm.is_active
FROM dbo.m_db_column_mappings cm
INNER JOIN dbo.m_db_table_mappings tm
    ON tm.mapping_id = cm.table_mapping_id
ORDER BY tm.legacy_table, cm.column_mapping_id;

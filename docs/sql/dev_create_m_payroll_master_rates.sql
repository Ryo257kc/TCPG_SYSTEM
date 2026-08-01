/*
  Payroll_DEV (sqlsrv_payroll connection)
  Create English master tables and migrate from legacy tables.

  Target legacy tables:
    - dbo.t_rouho
    - dbo.t_syaho
    - dbo.t_staff_shou

  New tables:
    - dbo.m_labor_insurance_rates
    - dbo.m_social_insurance_rates
    - dbo.m_staff_social_insurances
*/
SET NOCOUNT ON;

/* ---------------------------------------------------------
   1) m_labor_insurance_rates (from t_rouho)
--------------------------------------------------------- */
IF OBJECT_ID(N'dbo.m_labor_insurance_rates', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.m_labor_insurance_rates (
        labor_rate_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        apply_date DATE NOT NULL,
        general_employee_rate DECIMAL(18,4) NULL,
        created_at DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        updated_at DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME()
    );
END;

IF OBJECT_ID(N'dbo.t_rouho', N'U') IS NOT NULL
BEGIN
    TRUNCATE TABLE dbo.m_labor_insurance_rates;

    INSERT INTO dbo.m_labor_insurance_rates (
        apply_date,
        general_employee_rate
    )
    SELECT
        TRY_CONVERT(date, rou_apply_date) AS apply_date,
        TRY_CONVERT(decimal(18,4), general_st) AS general_employee_rate
    FROM dbo.t_rouho
    WHERE TRY_CONVERT(date, rou_apply_date) IS NOT NULL;
END;

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.m_labor_insurance_rates')
      AND name = N'IX_m_labor_insurance_rates_apply_date'
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_m_labor_insurance_rates_apply_date
        ON dbo.m_labor_insurance_rates (apply_date DESC);
END;

/* ---------------------------------------------------------
   2) m_social_insurance_rates (from t_syaho)
--------------------------------------------------------- */
IF OBJECT_ID(N'dbo.m_social_insurance_rates', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.m_social_insurance_rates (
        social_rate_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        apply_date DATE NOT NULL,
        child_contribution_rate DECIMAL(18,4) NULL,
        created_at DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        updated_at DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME()
    );
END;

IF OBJECT_ID(N'dbo.t_syaho', N'U') IS NOT NULL
BEGIN
    TRUNCATE TABLE dbo.m_social_insurance_rates;

    INSERT INTO dbo.m_social_insurance_rates (
        apply_date,
        child_contribution_rate
    )
    SELECT
        TRY_CONVERT(date, jidou_apply_date) AS apply_date,
        TRY_CONVERT(decimal(18,4), jidou_rate) AS child_contribution_rate
    FROM dbo.t_syaho
    WHERE TRY_CONVERT(date, jidou_apply_date) IS NOT NULL;
END;

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.m_social_insurance_rates')
      AND name = N'IX_m_social_insurance_rates_apply_date'
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_m_social_insurance_rates_apply_date
        ON dbo.m_social_insurance_rates (apply_date DESC);
END;

/* ---------------------------------------------------------
   3) m_staff_social_insurances (from t_staff_shou)
--------------------------------------------------------- */
IF OBJECT_ID(N'dbo.m_staff_social_insurances', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.m_staff_social_insurances (
        staff_social_insurance_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        staff_code NVARCHAR(50) NOT NULL,
        raise_year DATE NOT NULL,
        kenpo_amo DECIMAL(18,4) NULL,
        kaigo_amo DECIMAL(18,4) NULL,
        kounen_amo DECIMAL(18,4) NULL,
        kenpo_monthly_amo DECIMAL(18,4) NULL,
        kounen_monthly_amo DECIMAL(18,4) NULL,
        kenpo_toukyu NVARCHAR(50) NULL,
        kounen_toukyu NVARCHAR(50) NULL,
        source_staff_shou_no INT NULL,
        created_at DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        updated_at DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME()
    );
END;

IF OBJECT_ID(N'dbo.t_staff_shou', N'U') IS NOT NULL
BEGIN
    TRUNCATE TABLE dbo.m_staff_social_insurances;

    INSERT INTO dbo.m_staff_social_insurances (
        staff_code,
        raise_year,
        kenpo_amo,
        kaigo_amo,
        kounen_amo,
        kenpo_monthly_amo,
        kounen_monthly_amo,
        kenpo_toukyu,
        kounen_toukyu,
        source_staff_shou_no
    )
    SELECT
        LTRIM(RTRIM(ISNULL(staff_id, ''))) AS staff_code,
        TRY_CONVERT(date, raise_year) AS raise_year,
        TRY_CONVERT(decimal(18,4), kenpo_amo) AS kenpo_amo,
        TRY_CONVERT(decimal(18,4), kaigo_amo) AS kaigo_amo,
        TRY_CONVERT(decimal(18,4), kounen_amo) AS kounen_amo,
        TRY_CONVERT(decimal(18,4), kenpo_monthly_amo) AS kenpo_monthly_amo,
        TRY_CONVERT(decimal(18,4), kounen_monthly_amo) AS kounen_monthly_amo,
        CAST(kenpo_toukyu AS nvarchar(50)) AS kenpo_toukyu,
        CAST(kounen_toukyu AS nvarchar(50)) AS kounen_toukyu,
        TRY_CONVERT(int, staff_shou_no) AS source_staff_shou_no
    FROM dbo.t_staff_shou
    WHERE LTRIM(RTRIM(ISNULL(staff_id, ''))) <> ''
      AND TRY_CONVERT(date, raise_year) IS NOT NULL;
END;

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.m_staff_social_insurances')
      AND name = N'IX_m_staff_social_insurances_staff_raise_year'
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_m_staff_social_insurances_staff_raise_year
        ON dbo.m_staff_social_insurances (staff_code ASC, raise_year DESC);
END;

/* ---------------------------------------------------------
   Summary
--------------------------------------------------------- */
SELECT
    't_rouho' AS src_table,
    CASE WHEN OBJECT_ID(N'dbo.t_rouho', N'U') IS NULL THEN NULL ELSE (SELECT COUNT(1) FROM dbo.t_rouho) END AS src_count,
    (SELECT COUNT(1) FROM dbo.m_labor_insurance_rates) AS dst_count
UNION ALL
SELECT
    't_syaho',
    CASE WHEN OBJECT_ID(N'dbo.t_syaho', N'U') IS NULL THEN NULL ELSE (SELECT COUNT(1) FROM dbo.t_syaho) END,
    (SELECT COUNT(1) FROM dbo.m_social_insurance_rates)
UNION ALL
SELECT
    't_staff_shou',
    CASE WHEN OBJECT_ID(N'dbo.t_staff_shou', N'U') IS NULL THEN NULL ELSE (SELECT COUNT(1) FROM dbo.t_staff_shou) END,
    (SELECT COUNT(1) FROM dbo.m_staff_social_insurances);


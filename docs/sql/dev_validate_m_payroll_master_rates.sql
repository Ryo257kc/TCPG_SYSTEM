/*
  Validate migrated English payroll master tables in Payroll_DEV.
*/
SET NOCOUNT ON;

SELECT
    'm_labor_insurance_rates' AS table_name,
    COUNT(1) AS row_count
FROM dbo.m_labor_insurance_rates
UNION ALL
SELECT
    'm_social_insurance_rates',
    COUNT(1)
FROM dbo.m_social_insurance_rates
UNION ALL
SELECT
    'm_staff_social_insurances',
    COUNT(1)
FROM dbo.m_staff_social_insurances;

SELECT TOP (5)
    staff_code,
    raise_year,
    kenpo_amo,
    kaigo_amo,
    kounen_amo
FROM dbo.m_staff_social_insurances
ORDER BY raise_year DESC, staff_code ASC;


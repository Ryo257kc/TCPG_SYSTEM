IF COL_LENGTH('dbo.mx_stores', 'company_name') IS NOT NULL
BEGIN
    ALTER TABLE dbo.mx_stores DROP COLUMN company_name;
END;
GO

IF COL_LENGTH('dbo.mx_stores', 'company_address') IS NOT NULL
BEGIN
    ALTER TABLE dbo.mx_stores DROP COLUMN company_address;
END;
GO

IF COL_LENGTH('dbo.mx_stores', 'FAX') IS NOT NULL
BEGIN
    ALTER TABLE dbo.mx_stores DROP COLUMN FAX;
END;
GO

IF COL_LENGTH('dbo.mx_stores', 'position_title') IS NOT NULL
BEGIN
    ALTER TABLE dbo.mx_stores DROP COLUMN position_title;
END;
GO

IF COL_LENGTH('dbo.mx_stores', 'representative_name_kana') IS NOT NULL
BEGIN
    ALTER TABLE dbo.mx_stores DROP COLUMN representative_name_kana;
END;
GO

IF COL_LENGTH('dbo.mx_stores', 'representative_name') IS NOT NULL
BEGIN
    ALTER TABLE dbo.mx_stores DROP COLUMN representative_name;
END;
GO

IF COL_LENGTH('dbo.mx_stores', 'office_number') IS NOT NULL
BEGIN
    ALTER TABLE dbo.mx_stores DROP COLUMN office_number;
END;
GO

IF COL_LENGTH('dbo.mx_stores', 'health_office_code') IS NOT NULL
BEGIN
    ALTER TABLE dbo.mx_stores DROP COLUMN health_office_code;
END;
GO

IF COL_LENGTH('dbo.mx_stores', 'pension_office_code') IS NOT NULL
BEGIN
    ALTER TABLE dbo.mx_stores DROP COLUMN pension_office_code;
END;
GO

IF COL_LENGTH('dbo.mx_stores', 'corporate_number') IS NOT NULL
BEGIN
    ALTER TABLE dbo.mx_stores DROP COLUMN corporate_number;
END;
GO

IF COL_LENGTH('dbo.mx_stores', 'insurer_number') IS NOT NULL
BEGIN
    ALTER TABLE dbo.mx_stores DROP COLUMN insurer_number;
END;
GO

IF COL_LENGTH('dbo.mx_stores', 'insurer_name') IS NOT NULL
BEGIN
    ALTER TABLE dbo.mx_stores DROP COLUMN insurer_name;
END;
GO

IF COL_LENGTH('dbo.mx_stores', 'insurer_address') IS NOT NULL
BEGIN
    ALTER TABLE dbo.mx_stores DROP COLUMN insurer_address;
END;
GO

IF COL_LENGTH('dbo.mx_stores', 'employment_office_number') IS NOT NULL
BEGIN
    ALTER TABLE dbo.mx_stores DROP COLUMN employment_office_number;
END;
GO

IF COL_LENGTH('dbo.mx_stores', 'labor_insurance_number') IS NOT NULL
BEGIN
    ALTER TABLE dbo.mx_stores DROP COLUMN labor_insurance_number;
END;
GO

IF COL_LENGTH('dbo.mx_stores', 'labor_setup_type') IS NOT NULL
BEGIN
    ALTER TABLE dbo.mx_stores DROP COLUMN labor_setup_type;
END;
GO

IF COL_LENGTH('dbo.mx_stores', 'labor_setup_date') IS NOT NULL
BEGIN
    ALTER TABLE dbo.mx_stores DROP COLUMN labor_setup_date;
END;
GO

IF COL_LENGTH('dbo.mx_stores', 'labor_office_type') IS NOT NULL
BEGIN
    ALTER TABLE dbo.mx_stores DROP COLUMN labor_office_type;
END;
GO

IF COL_LENGTH('dbo.mx_stores', 'labor_industry_class') IS NOT NULL
BEGIN
    ALTER TABLE dbo.mx_stores DROP COLUMN labor_industry_class;
END;
GO

IF COL_LENGTH('dbo.mx_stores', 'founded_date') IS NOT NULL
BEGIN
    ALTER TABLE dbo.mx_stores DROP COLUMN founded_date;
END;
GO

IF COL_LENGTH('dbo.mx_stores', 'area') IS NULL
BEGIN
    ALTER TABLE dbo.mx_stores ADD area NVARCHAR(255) NULL;
END;
GO

IF COL_LENGTH('dbo.mx_stores', 'address') IS NULL
BEGIN
    ALTER TABLE dbo.mx_stores ADD address NVARCHAR(255) NULL;
END;
GO

IF COL_LENGTH('dbo.mx_stores', 'category') IS NULL
BEGIN
    ALTER TABLE dbo.mx_stores ADD category NVARCHAR(255) NULL;
END;
GO

IF COL_LENGTH('dbo.mx_stores', 'external_store_code') IS NULL
BEGIN
    ALTER TABLE dbo.mx_stores ADD external_store_code NVARCHAR(50) NULL;
END;
GO

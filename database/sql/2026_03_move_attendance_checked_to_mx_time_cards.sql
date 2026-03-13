SET NOCOUNT ON;

IF COL_LENGTH('dbo.mx_time_cards', 'attendance_checked') IS NULL
BEGIN
    ALTER TABLE dbo.mx_time_cards
        ADD attendance_checked INT NOT NULL CONSTRAINT DF_mx_time_cards_attendance_checked DEFAULT (0);
END;
GO

IF COL_LENGTH('dbo.mx_time_cards', 'attendance_checked_at') IS NULL
BEGIN
    ALTER TABLE dbo.mx_time_cards
        ADD attendance_checked_at DATETIME NULL;
END;
GO

IF COL_LENGTH('dbo.mx_time_cards', 'attendance_checked_by') IS NULL
BEGIN
    ALTER TABLE dbo.mx_time_cards
        ADD attendance_checked_by NVARCHAR(100) NULL;
END;
GO

IF COL_LENGTH('dbo.mx_kyuyo_shou', 'attendance_checked') IS NOT NULL
BEGIN
    ALTER TABLE dbo.mx_kyuyo_shou DROP COLUMN attendance_checked;
END;
GO

IF COL_LENGTH('dbo.mx_kyuyo_shou', 'attendance_checked_at') IS NOT NULL
BEGIN
    ALTER TABLE dbo.mx_kyuyo_shou DROP COLUMN attendance_checked_at;
END;
GO

IF COL_LENGTH('dbo.mx_kyuyo_shou', 'attendance_checked_by') IS NOT NULL
BEGIN
    ALTER TABLE dbo.mx_kyuyo_shou DROP COLUMN attendance_checked_by;
END;
GO

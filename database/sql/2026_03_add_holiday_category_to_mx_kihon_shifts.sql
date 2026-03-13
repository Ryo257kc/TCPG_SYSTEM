SET NOCOUNT ON;

BEGIN TRY
    BEGIN TRAN;

    IF COL_LENGTH('dbo.mx_kihon_shifts', 'holiday_category') IS NULL
    BEGIN
        ALTER TABLE dbo.mx_kihon_shifts
            ADD holiday_category NVARCHAR(20) NULL;
    END;

    COMMIT;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0
        ROLLBACK;

    THROW;
END CATCH;

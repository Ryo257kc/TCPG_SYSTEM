/*
  年末調整スタッフ申請：mx_fuyo/mx_hoken/mx_nen_tyoへの直書き移行に伴い、
  これまでstaff_year_end_applicationsに置いていた住宅ローン・前職源泉・
  障害者手帳の証憑をmx_nen_tyo側へ移す。あわせて、寡婦の区分見直し
  （一般寡婦／特別寡婦の廃止、ひとり親の追加）用にhitori_oyaを追加する。

  冪等（IF COL_LENGTH(...) IS NULL）。
*/

SET NOCOUNT ON;

BEGIN TRY
    BEGIN TRAN;

    IF COL_LENGTH('dbo.mx_nen_tyo', 'housing_loan_certificate_file_path') IS NULL
    BEGIN
        ALTER TABLE dbo.mx_nen_tyo
            ADD housing_loan_certificate_file_path NVARCHAR(500) NULL,
                housing_loan_certificate_original_name NVARCHAR(255) NULL,
                housing_loan_certificate_uploaded_at DATETIME NULL;
    END;

    IF COL_LENGTH('dbo.mx_nen_tyo', 'previous_job_certificate_file_path') IS NULL
    BEGIN
        ALTER TABLE dbo.mx_nen_tyo
            ADD previous_job_certificate_file_path NVARCHAR(500) NULL,
                previous_job_certificate_original_name NVARCHAR(255) NULL,
                previous_job_certificate_uploaded_at DATETIME NULL;
    END;

    IF COL_LENGTH('dbo.mx_nen_tyo', 'disability_certificate_file_path') IS NULL
    BEGIN
        ALTER TABLE dbo.mx_nen_tyo
            ADD disability_certificate_file_path NVARCHAR(500) NULL,
                disability_certificate_original_name NVARCHAR(255) NULL,
                disability_certificate_uploaded_at DATETIME NULL;
    END;

    IF COL_LENGTH('dbo.mx_nen_tyo', 'hitori_oya') IS NULL
    BEGIN
        ALTER TABLE dbo.mx_nen_tyo
            ADD hitori_oya BIT NULL;
    END;

    COMMIT;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0
        ROLLBACK;

    THROW;
END CATCH;

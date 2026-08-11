/*
  年末調整スタッフ申請：
  1. 本人の障害者・寡婦・勤労学生区分（mx_nen_tyoの本人関係グループと同じ項目）を
     staff_year_end_applicationsへ追加。既存のadmin側 nenTyoSummaryGroups()の
     'hon_shougai_toku'/'hon_shougai_ta'/'ippan_kafu'/'toku_kafu'/'kafu'/'student'
     とラベルを揃える。
  2. 扶養親族の障害者手帳の証憑ファイル。mx_fuyoとstaff_year_end_fuyo_stagingの両方に、
     保険料控除・前職・住宅ローンと同じ証憑カラムを追加。

  冪等（IF COL_LENGTH(...) IS NULL）。
*/

SET NOCOUNT ON;

BEGIN TRY
    BEGIN TRAN;

    IF COL_LENGTH('dbo.staff_year_end_applications', 'hon_shougai_toku') IS NULL
    BEGIN
        ALTER TABLE dbo.staff_year_end_applications
            ADD hon_shougai_toku BIT NULL,
                hon_shougai_ta BIT NULL,
                ippan_kafu BIT NULL,
                toku_kafu BIT NULL,
                kafu BIT NULL,
                student BIT NULL;
    END;

    IF COL_LENGTH('dbo.mx_fuyo', 'failure_certificate_file_path') IS NULL
    BEGIN
        ALTER TABLE dbo.mx_fuyo
            ADD failure_certificate_file_path NVARCHAR(500) NULL,
                failure_certificate_original_name NVARCHAR(255) NULL,
                failure_certificate_uploaded_at DATETIME NULL;
    END;

    IF COL_LENGTH('dbo.staff_year_end_fuyo_staging', 'failure_certificate_file_path') IS NULL
    BEGIN
        ALTER TABLE dbo.staff_year_end_fuyo_staging
            ADD failure_certificate_file_path NVARCHAR(500) NULL,
                failure_certificate_original_name NVARCHAR(255) NULL,
                failure_certificate_uploaded_at DATETIME NULL;
    END;

    COMMIT;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0
        ROLLBACK;

    THROW;
END CATCH;

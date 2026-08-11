/*
  年末調整スタッフ申請：本人情報セクションの証憑必須化。
  住所変更・氏名変更・本人障害者の3項目は、それぞれ別の証憑書類が必要
  （住所変更→住民票等、氏名変更→戸籍謄本・婚姻届受理証明書等、
  本人障害→障害者手帳の写し）なので、証憑カラムを分けて持つ。

  冪等（IF COL_LENGTH(...) IS NULL）。
*/

SET NOCOUNT ON;

BEGIN TRY
    BEGIN TRAN;

    IF COL_LENGTH('dbo.staff_year_end_applications', 'address_change_certificate_file_path') IS NULL
    BEGIN
        ALTER TABLE dbo.staff_year_end_applications
            ADD address_change_certificate_file_path NVARCHAR(500) NULL,
                address_change_certificate_original_name NVARCHAR(255) NULL,
                address_change_certificate_uploaded_at DATETIME NULL,
                name_change_certificate_file_path NVARCHAR(500) NULL,
                name_change_certificate_original_name NVARCHAR(255) NULL,
                name_change_certificate_uploaded_at DATETIME NULL,
                disability_certificate_file_path NVARCHAR(500) NULL,
                disability_certificate_original_name NVARCHAR(255) NULL,
                disability_certificate_uploaded_at DATETIME NULL;
    END;

    COMMIT;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0
        ROLLBACK;

    THROW;
END CATCH;

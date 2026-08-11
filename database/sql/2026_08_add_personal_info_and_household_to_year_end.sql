/*
  年末調整スタッフ申請機能：本人情報セクション拡張（氏名・フリガナ・生年月日・世帯主・続柄）。

  staff_year_end_applications: 氏名・フリガナ・生年月日の変更申告用カラムと、
  世帯主氏名・世帯主との続柄（今まで収集していなかった新規項目）を追加。
  mx_nen_tyo: 反映先として世帯主氏名・続柄を追加（扶養控除等申告書の記載項目）。

  冪等（IF COL_LENGTH(...) IS NULL）。
*/

SET NOCOUNT ON;

BEGIN TRY
    BEGIN TRAN;

    IF COL_LENGTH('dbo.staff_year_end_applications', 'new_staff_name') IS NULL
    BEGIN
        ALTER TABLE dbo.staff_year_end_applications
            ADD new_staff_name NVARCHAR(50) NULL,
                new_staff_name_furi NVARCHAR(50) NULL,
                new_birthday DATETIME NULL,
                setai_nushi NVARCHAR(50) NULL,
                setai_zoku_gara NVARCHAR(20) NULL;
    END;

    IF COL_LENGTH('dbo.mx_nen_tyo', 'setai_nushi') IS NULL
    BEGIN
        ALTER TABLE dbo.mx_nen_tyo
            ADD setai_nushi NVARCHAR(50) NULL,
                setai_zoku_gara NVARCHAR(20) NULL;
    END;

    COMMIT;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0
        ROLLBACK;

    THROW;
END CATCH;

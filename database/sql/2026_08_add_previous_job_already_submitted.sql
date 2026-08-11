/*
  年末調整スタッフ申請：前職セクションの二段階確認用。
  「前職はあるが、源泉徴収票は入社時に事務所へ提出済み」の場合、
  スタッフに重複入力させないためのフラグ。

  冪等（IF COL_LENGTH(...) IS NULL）。
*/

SET NOCOUNT ON;

BEGIN TRY
    BEGIN TRAN;

    IF COL_LENGTH('dbo.staff_year_end_applications', 'previous_job_already_submitted') IS NULL
    BEGIN
        ALTER TABLE dbo.staff_year_end_applications
            ADD previous_job_already_submitted BIT NULL;
    END;

    COMMIT;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0
        ROLLBACK;

    THROW;
END CATCH;

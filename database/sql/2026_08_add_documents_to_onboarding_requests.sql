/*
  入社手続き申請：書類ページ（入社時に必要な書類・提出物の一覧）を統合するため、
  証憑アップロードのみで扱う4項目を追加する。
  運転免許証・住民票は通勤や住所確認の状況によっては不要な場合があるが、
  バリデーションでは必須にせず、画面側の注記で条件を伝える運用とする。

  対象接続: sqlsrv_payroll（staff_onboarding_requestsと同じ接続）。
  冪等（IF COL_LENGTH(...) IS NULL）。
*/

SET NOCOUNT ON;

BEGIN TRY
    BEGIN TRAN;

    IF COL_LENGTH('dbo.staff_onboarding_requests', 'license_certificate_file_path') IS NULL
    BEGIN
        ALTER TABLE dbo.staff_onboarding_requests ADD
            license_certificate_file_path NVARCHAR(500) NULL,
            license_certificate_original_name NVARCHAR(255) NULL,
            license_certificate_uploaded_at DATETIME NULL,
            residence_certificate_file_path NVARCHAR(500) NULL,
            residence_certificate_original_name NVARCHAR(255) NULL,
            residence_certificate_uploaded_at DATETIME NULL,
            employment_insurance_certificate_file_path NVARCHAR(500) NULL,
            employment_insurance_certificate_original_name NVARCHAR(255) NULL,
            employment_insurance_certificate_uploaded_at DATETIME NULL,
            previous_job_certificate_file_path NVARCHAR(500) NULL,
            previous_job_certificate_original_name NVARCHAR(255) NULL,
            previous_job_certificate_uploaded_at DATETIME NULL;
    END;

    COMMIT;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0
        ROLLBACK;

    THROW;
END CATCH;

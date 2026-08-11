/*
  入社手続きフォーム：ステージングテーブル新設。

  新入社スタッフ（mx_staffs.employment='採用予定'）が住所・連絡先・振込口座・通勤経路・
  マイナンバー証憑を年中いつでも申請できる機能。個人情報変更申請機能
  （staff_profile_requests）と同じ理由・同じ形で、mx_staffsへの反映前にここへ
  ステージングする。扶養増減はmx_fuyoへ直接書き込む方式（他機能と同じ理由）のため、
  ステージングテーブルは持たない。マイナンバーは証憑ファイルの保存のみで、
  数値そのものは保存しない（mx_staffs.my_numberへは書き込まない）。

  対象接続: sqlsrv_payroll（staff_profile_requests・mx_fuyoと同じ接続）。
  冪等（IF OBJECT_ID(...) IS NULL）。
*/

SET NOCOUNT ON;

BEGIN TRY
    BEGIN TRAN;

    IF OBJECT_ID(N'dbo.staff_onboarding_requests', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.staff_onboarding_requests (
            request_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            staff_id NVARCHAR(50) NOT NULL,
            status NVARCHAR(20) NOT NULL CONSTRAINT DF_sor_status DEFAULT N'draft',
            new_address NVARCHAR(255) NULL,
            new_address_furi NVARCHAR(255) NULL,
            new_home_tel NVARCHAR(50) NULL,
            new_mobile_tel NVARCHAR(50) NULL,
            new_bank_name NVARCHAR(100) NULL,
            new_bank_branch NVARCHAR(100) NULL,
            new_account_type NVARCHAR(50) NULL,
            new_account_num NVARCHAR(50) NULL,
            new_car_km NVARCHAR(100) NULL,
            new_traffic_day NVARCHAR(100) NULL,
            new_traffic_day_tuika NVARCHAR(100) NULL,
            address_certificate_file_path NVARCHAR(500) NULL,
            address_certificate_original_name NVARCHAR(255) NULL,
            address_certificate_uploaded_at DATETIME NULL,
            mynumber_certificate_file_path NVARCHAR(500) NULL,
            mynumber_certificate_original_name NVARCHAR(255) NULL,
            mynumber_certificate_uploaded_at DATETIME NULL,
            submitted_at DATETIME2 NULL,
            confirmed_at DATETIME2 NULL,
            reflected_at DATETIME2 NULL,
            return_note NVARCHAR(2000) NULL,
            admin_note NVARCHAR(2000) NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_sor_created_at DEFAULT SYSDATETIME(),
            updated_at DATETIME2 NOT NULL CONSTRAINT DF_sor_updated_at DEFAULT SYSDATETIME()
        );

        CREATE INDEX IX_sor_staff_id ON dbo.staff_onboarding_requests (staff_id);
    END;

    COMMIT;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0
        ROLLBACK;

    THROW;
END CATCH;

/*
  個人情報変更申請機能：ステージングテーブル新設。

  在籍スタッフが氏名・住所・世帯主・通勤経路の変更を年中いつでも申請できる機能。
  対象はmx_staffs（履歴を持たない単一マスタで、年調以外の給与計算等でも参照される）
  のため、年末調整の「本人情報」セクションと同じく、いったんここへ保存し、
  事務所が証憑を確認して承認したものだけをmx_staffsへ反映する
  （ProfileRequestV2Controller側で実装）。

  扶養の増減はmx_fuyoへ直接書き込む方式（年末調整のmx_fuyo直書きと同じ理由）のため、
  ステージングテーブルは持たない。

  対象接続: sqlsrv_payroll（staff_year_end_applications・mx_fuyoと同じ接続）。
  冪等（IF OBJECT_ID(...) IS NULL）。
*/

SET NOCOUNT ON;

BEGIN TRY
    BEGIN TRAN;

    IF OBJECT_ID(N'dbo.staff_profile_requests', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.staff_profile_requests (
            request_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            staff_id NVARCHAR(50) NOT NULL,
            status NVARCHAR(20) NOT NULL CONSTRAINT DF_spr_status DEFAULT N'draft',
            new_staff_name NVARCHAR(50) NULL,
            new_staff_name_furi NVARCHAR(50) NULL,
            new_address NVARCHAR(255) NULL,
            new_address_furi NVARCHAR(255) NULL,
            setai_nushi NVARCHAR(50) NULL,
            setai_zoku_gara NVARCHAR(20) NULL,
            new_car_km NVARCHAR(100) NULL,
            new_traffic_day NVARCHAR(100) NULL,
            new_traffic_day_tuika NVARCHAR(100) NULL,
            name_change_certificate_file_path NVARCHAR(500) NULL,
            name_change_certificate_original_name NVARCHAR(255) NULL,
            name_change_certificate_uploaded_at DATETIME NULL,
            address_change_certificate_file_path NVARCHAR(500) NULL,
            address_change_certificate_original_name NVARCHAR(255) NULL,
            address_change_certificate_uploaded_at DATETIME NULL,
            commute_change_certificate_file_path NVARCHAR(500) NULL,
            commute_change_certificate_original_name NVARCHAR(255) NULL,
            commute_change_certificate_uploaded_at DATETIME NULL,
            submitted_at DATETIME2 NULL,
            confirmed_at DATETIME2 NULL,
            reflected_at DATETIME2 NULL,
            return_note NVARCHAR(2000) NULL,
            admin_note NVARCHAR(2000) NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_spr_created_at DEFAULT SYSDATETIME(),
            updated_at DATETIME2 NOT NULL CONSTRAINT DF_spr_updated_at DEFAULT SYSDATETIME()
        );

        CREATE INDEX IX_spr_staff_id ON dbo.staff_profile_requests (staff_id);
    END;

    COMMIT;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0
        ROLLBACK;

    THROW;
END CATCH;

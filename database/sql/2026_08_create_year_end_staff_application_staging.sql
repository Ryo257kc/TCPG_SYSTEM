/*
  年末調整スタッフ申請機能：ステージングテーブル新設。

  スタッフが申請フォームに入力した内容は、実データ（mx_fuyo/mx_hoken/mx_nen_tyo/mx_staffs）を
  直接書き換えず、いったんここへ保存する。事務所が証憑を確認して承認したものだけを
  管理画面から実データへコピーする（YearEndAdjustmentV2Controller側で実装）。

  対象接続: sqlsrv_payroll（mx_fuyo/mx_hoken/staff_year_end_applicationsと同じ接続）。
  冪等（IF OBJECT_ID(...) IS NULL / IF COL_LENGTH(...) IS NULL）。
*/

SET NOCOUNT ON;

BEGIN TRY
    BEGIN TRAN;

    -- 扶養親族の申請ステージング（mx_fuyoと同じ項目。マイナンバーは含めない＝入社時取得済みで不変のため対象外）
    IF OBJECT_ID(N'dbo.staff_year_end_fuyo_staging', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.staff_year_end_fuyo_staging (
            staging_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            application_id INT NOT NULL,
            staff_id NVARCHAR(50) NOT NULL,
            source_fuyo_no INT NULL,
            action NVARCHAR(20) NOT NULL,
            fuyo_name NVARCHAR(50) NULL,
            fuyo_name_furi NVARCHAR(50) NULL,
            fuyo_address NVARCHAR(255) NULL,
            fuyo_relationship NVARCHAR(50) NULL,
            fuyo_birthday DATETIME NULL,
            failure_notebook NVARCHAR(50) NULL,
            failure_judgment NVARCHAR(50) NULL,
            deduction_target BIT NOT NULL CONSTRAINT DF_syefs_deduction_target DEFAULT 1,
            widow BIT NOT NULL CONSTRAINT DF_syefs_widow DEFAULT 0,
            fuyo_sex NVARCHAR(5) NULL,
            kyojyu NVARCHAR(5) NULL,
            fuyo_shunyu MONEY NULL,
            review_status NVARCHAR(20) NOT NULL CONSTRAINT DF_syefs_review_status DEFAULT N'pending',
            reviewer_note NVARCHAR(MAX) NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_syefs_created_at DEFAULT SYSDATETIME(),
            updated_at DATETIME2 NOT NULL CONSTRAINT DF_syefs_updated_at DEFAULT SYSDATETIME(),
            CONSTRAINT FK_syefs_application FOREIGN KEY (application_id)
                REFERENCES dbo.staff_year_end_applications(application_id)
        );

        CREATE INDEX IX_syefs_application_id ON dbo.staff_year_end_fuyo_staging (application_id);
    END;

    -- 保険料控除の申請ステージング（mx_hokenと同じ項目、証憑ファイル情報つき）
    IF OBJECT_ID(N'dbo.staff_year_end_hoken_staging', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.staff_year_end_hoken_staging (
            staging_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
            application_id INT NOT NULL,
            staff_id NVARCHAR(50) NOT NULL,
            source_hoken_no INT NULL,
            action NVARCHAR(20) NOT NULL,
            insurance_company NVARCHAR(50) NULL,
            category NVARCHAR(20) NULL,
            applied_system NVARCHAR(20) NULL,
            declared_amount MONEY NULL,
            insurance_type NVARCHAR(50) NULL,
            insurance_period NVARCHAR(10) NULL,
            policy_holder_name NVARCHAR(20) NULL,
            beneficiary_name NVARCHAR(20) NULL,
            beneficiary_relationship NVARCHAR(10) NULL,
            pension_payment_start_date DATETIME NULL,
            year_end_insurance_note NVARCHAR(MAX) NULL,
            certificate_file_path NVARCHAR(500) NULL,
            certificate_original_name NVARCHAR(255) NULL,
            certificate_uploaded_at DATETIME NULL,
            review_status NVARCHAR(20) NOT NULL CONSTRAINT DF_syehs_review_status DEFAULT N'pending',
            reviewer_note NVARCHAR(MAX) NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_syehs_created_at DEFAULT SYSDATETIME(),
            updated_at DATETIME2 NOT NULL CONSTRAINT DF_syehs_updated_at DEFAULT SYSDATETIME(),
            CONSTRAINT FK_syehs_application FOREIGN KEY (application_id)
                REFERENCES dbo.staff_year_end_applications(application_id)
        );

        CREATE INDEX IX_syehs_application_id ON dbo.staff_year_end_hoken_staging (application_id);
    END;

    -- 年調申請の公開日設定（対象年ごとに1行。公開日を過ぎるまではシステムマスタのみアクセス可）
    IF OBJECT_ID(N'dbo.year_end_adjustment_settings', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.year_end_adjustment_settings (
            target_year INT NOT NULL PRIMARY KEY,
            publish_date DATETIME2 NULL,
            created_at DATETIME2 NOT NULL CONSTRAINT DF_yeas_created_at DEFAULT SYSDATETIME(),
            updated_at DATETIME2 NOT NULL CONSTRAINT DF_yeas_updated_at DEFAULT SYSDATETIME()
        );
    END;

    -- staff_year_end_applications：住所・配偶者・前職・保険料控除・住宅ローン控除の申告項目（1申請1行なので別テーブルにせずここへ追加）
    -- 住宅ローン控除は既存の証明書に印字済みの控除額をそのまま転記させるだけで、計算ロジックは持たない（jyu_kari_kouへ反映時にコピー）。
    IF COL_LENGTH('dbo.staff_year_end_applications', 'new_address') IS NULL
    BEGIN
        ALTER TABLE dbo.staff_year_end_applications
            ADD new_address NVARCHAR(255) NULL,
                new_address_furi NVARCHAR(255) NULL,
                spouse_changed BIT NOT NULL CONSTRAINT DF_syea_spouse_changed DEFAULT 0,
                haigu_umu NVARCHAR(2) NULL,
                haigu_kou_umu NVARCHAR(20) NULL,
                spouse_gross_income MONEY NULL,
                spouse_computed_income MONEY NULL,
                zen_shotoku MONEY NULL,
                zen_syaho_kou MONEY NULL,
                zen_kyuyo_tax MONEY NULL,
                zen_bonus_tax MONEY NULL,
                zen_tai_date DATETIME NULL,
                zen_syamei NVARCHAR(60) NULL,
                zen_add NVARCHAR(120) NULL,
                previous_job_certificate_file_path NVARCHAR(500) NULL,
                previous_job_certificate_original_name NVARCHAR(255) NULL,
                previous_job_certificate_uploaded_at DATETIME NULL,
                shin_syaho_fee_kou MONEY NULL,
                shun_kigyou_fee_kou MONEY NULL,
                seimei_fee_kou MONEY NULL,
                jishun_fee_kou MONEY NULL,
                housing_loan_declared_amount MONEY NULL,
                housing_loan_certificate_file_path NVARCHAR(500) NULL,
                housing_loan_certificate_original_name NVARCHAR(255) NULL,
                housing_loan_certificate_uploaded_at DATETIME NULL;
    END;

    COMMIT;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0
        ROLLBACK;

    THROW;
END CATCH;

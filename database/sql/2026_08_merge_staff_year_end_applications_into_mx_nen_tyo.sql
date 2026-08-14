-- staff_year_end_applications（申請ワークフロー用に新設したテーブル）を廃止し、
-- mx_nen_tyo（年調専用・既存テーブル）へ統合する。
-- 理由：テーブルを分ける必要が無い。mx_nen_tyoは年調専用で他機能と共有しないので
-- 直接書き込みでよく、ステージング列（*_json）は実際には一切使われていなかった。
-- zen_*・haigu_*・setai_*・各種証明書列など、大半は既にmx_nen_tyoに同名列があった。

ALTER TABLE dbo.mx_nen_tyo ADD
    application_status nvarchar(30) NULL,
    year_end_adjustment bit NULL,
    personal_info_changed bit NULL,
    dependents_changed bit NULL,
    insurance_deduction_changed bit NULL,
    housing_loan_changed bit NULL,
    previous_job_withholding_changed bit NULL,
    special_collection_requested bit NULL,
    spouse_changed bit NULL,
    previous_job_already_submitted bit NULL,
    submitted_at datetime2 NULL,
    confirmed_at datetime2 NULL,
    reflected_at datetime2 NULL,
    return_note nvarchar(max) NULL,
    admin_note nvarchar(max) NULL,
    new_address nvarchar(255) NULL,
    new_address_furi nvarchar(255) NULL,
    new_staff_name nvarchar(50) NULL,
    new_staff_name_furi nvarchar(50) NULL,
    new_birthday datetime NULL,
    spouse_gross_income money NULL,
    spouse_computed_income money NULL,
    housing_loan_declared_amount money NULL,
    address_change_certificate_file_path nvarchar(500) NULL,
    address_change_certificate_original_name nvarchar(255) NULL,
    address_change_certificate_uploaded_at datetime NULL,
    name_change_certificate_file_path nvarchar(500) NULL,
    name_change_certificate_original_name nvarchar(255) NULL,
    name_change_certificate_uploaded_at datetime NULL;

-- 実行済み: 2026-08-15
-- JSON下書き列（personal_info_json等5列＋*_files_json 5列）は未使用のため移行しない。
-- zen_*・haigu_*・setai_*・前職/住宅ローン/障害者の証明書3点セットは
-- 既にmx_nen_tyoに同名列があるため移行不要（そちらを直接使う）。

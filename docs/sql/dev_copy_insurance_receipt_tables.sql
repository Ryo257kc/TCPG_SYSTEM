/*
  Copy legacy insurance receipt tables into recreated English tables.
  Source tables are read-only. This script does not update/delete/drop source data.

  Usage:
    1. Set @SourceDb to the legacy DB name.
    2. Set @TargetDb to the DB that contains the recreated mx_ tables.
    3. Run the script.
*/

SET NOCOUNT ON;
GO

DECLARE @SourceDb sysname = N'TCPGSYSTEM';
DECLARE @TargetDb sysname = N'YOUR_TARGET_DB';

DECLARE @sql nvarchar(max);

SET @sql = N'
INSERT INTO ' + QUOTENAME(@TargetDb) + N'.dbo.mx_insurers (
    insurer_number,
    insurer_name,
    display_name,
    display_branch,
    scheduled_payment_name,
    scheduled_payment_name_2,
    insurance_category,
    insurance_type,
    insurance_kind,
    subsidy_type,
    subsidy_provider,
    postal_code,
    insurer_address,
    mailing_address,
    insurer_phone,
    municipality_number,
    late_elderly_registration_number,
    general_registration_number,
    legacy_no
)
SELECT
    src.[保険者番号],
    src.[保険者名称],
    src.[表示名称],
    src.[表示支部],
    src.[入金予定名称],
    src.[入金予定名称2],
    src.[保険区分],
    src.[保険種別],
    src.[保険種類],
    src.[助成種類],
    src.[助成支給者],
    src.[郵便番号],
    src.[保険者住所],
    src.[送付先],
    src.[保険者電話],
    src.[市区町村番号],
    src.[後期高齢登録番号],
    src.[一般登録番号],
    src.[No]
FROM ' + QUOTENAME(@SourceDb) + N'.dbo.[T_保険者] AS src
WHERE NOT EXISTS (
    SELECT 1
    FROM ' + QUOTENAME(@TargetDb) + N'.dbo.mx_insurers AS dest
    WHERE dest.insurer_number = src.[保険者番号]
);';

EXEC sys.sp_executesql @sql;

SET @sql = N'
INSERT INTO ' + QUOTENAME(@TargetDb) + N'.dbo.mx_insurance_claim_details (
    insurance_claim_detail_id,
    insurer_number,
    treatment_month,
    store_name,
    staff_name,
    summary_category,
    total_sheets,
    total_parts_count,
    total_amount,
    copayment_amount,
    claim_amount,
    cash_collected_amount,
    adjustment_amount,
    is_high_cost_medical,
    is_uncollectible,
    bank_deposit_date,
    bank_deposit_amount,
    payment_date_text,
    payment_amount,
    remarks,
    deposit_name,
    payment_date_display,
    returned_amount,
    returned_journal_processed_at,
    patient_name,
    is_journalized,
    is_invoiced,
    subject_name
)
SELECT
    src.[保険請求内訳No],
    src.[保険者No],
    src.[施術月],
    src.[店舗],
    src.[担当者],
    src.[集計区分],
    src.[総枚数],
    src.[総部位数],
    src.[合計金額],
    src.[一部負担金],
    src.[請求金額],
    src.[現金回収],
    src.[修正額],
    src.[高額療養費],
    src.[回収不可],
    src.[通帳入金日],
    src.[通帳入金額],
    src.[入金日],
    src.[入金額],
    src.[備考],
    src.[入金名称],
    src.[入金日表示],
    src.[返戻額],
    src.[返戻仕訳処理日],
    src.[患者名],
    src.[記帳済],
    src.[請求書],
    src.[対象者名]
FROM ' + QUOTENAME(@SourceDb) + N'.dbo.[T_保険請求内訳] AS src
WHERE NOT EXISTS (
    SELECT 1
    FROM ' + QUOTENAME(@TargetDb) + N'.dbo.mx_insurance_claim_details AS dest
    WHERE dest.insurance_claim_detail_id = src.[保険請求内訳No]
);';

EXEC sys.sp_executesql @sql;
GO

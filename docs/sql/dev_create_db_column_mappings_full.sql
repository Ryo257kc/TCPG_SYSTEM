/*
  Payroll_DEV
  Create Payroll -> Payroll_DEV column mapping registry.
  Same-name columns are inserted automatically.
  Renamed columns are defined in rename_rows below.
*/
SET NOCOUNT ON;
GO
USE Payroll_DEV;
GO

IF OBJECT_ID(N'dbo.m_db_column_mappings', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.m_db_column_mappings (
        column_mapping_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        table_mapping_id INT NOT NULL,
        legacy_column NVARCHAR(255) NOT NULL,
        new_column NVARCHAR(255) NOT NULL,
        note NVARCHAR(255) NULL,
        is_active BIT NOT NULL CONSTRAINT DF_m_db_column_mappings_is_active DEFAULT (1),
        created_at DATETIME2(0) NOT NULL CONSTRAINT DF_m_db_column_mappings_created_at DEFAULT SYSUTCDATETIME(),
        updated_at DATETIME2(0) NOT NULL CONSTRAINT DF_m_db_column_mappings_updated_at DEFAULT SYSUTCDATETIME(),
        CONSTRAINT FK_m_db_column_mappings_table_mapping
            FOREIGN KEY (table_mapping_id) REFERENCES dbo.m_db_table_mappings(mapping_id)
    );
END;
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.m_db_column_mappings')
      AND name = N'IX_m_db_column_mappings_table_mapping'
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_m_db_column_mappings_table_mapping
        ON dbo.m_db_column_mappings (table_mapping_id, legacy_column, new_column);
END;
GO

;WITH rename_rows AS (
    SELECT *
    FROM (VALUES
        (N't_hoken',      N'mx_hoken',      N'保険会社',       N'insurance_company',          N'名称変更', CAST(1 AS BIT)),
        (N't_hoken',      N'mx_hoken',      N'区分',           N'category',                   N'名称変更', CAST(1 AS BIT)),
        (N't_hoken',      N'mx_hoken',      N'適用制度',       N'applied_system',             N'名称変更', CAST(1 AS BIT)),
        (N't_hoken',      N'mx_hoken',      N'申告額',         N'declared_amount',            N'名称変更', CAST(1 AS BIT)),
        (N't_hoken',      N'mx_hoken',      N'保険労働者No',   N'insurance_staff_no',         N'名称変更', CAST(1 AS BIT)),
        (N't_hoken',      N'mx_hoken',      N'保険年度',       N'insurance_year',             N'名称変更', CAST(1 AS BIT)),
        (N't_hoken',      N'mx_hoken',      N'保険種類',       N'insurance_type',             N'名称変更', CAST(1 AS BIT)),
        (N't_hoken',      N'mx_hoken',      N'保険期間',       N'insurance_period',           N'名称変更', CAST(1 AS BIT)),
        (N't_hoken',      N'mx_hoken',      N'契約者',         N'policy_holder_name',         N'名称変更', CAST(1 AS BIT)),
        (N't_hoken',      N'mx_hoken',      N'受取人',         N'beneficiary_name',           N'名称変更', CAST(1 AS BIT)),
        (N't_hoken',      N'mx_hoken',      N'受取人続柄',     N'beneficiary_relationship',   N'名称変更', CAST(1 AS BIT)),
        (N't_hoken',      N'mx_hoken',      N'年金支払開始日', N'pension_payment_start_date', N'名称変更', CAST(1 AS BIT)),
        (N't_hoken',      N'mx_hoken',      N'確認済チェック', N'checked_flag',               N'名称変更', CAST(1 AS BIT)),
        (N't_hoken',      N'mx_hoken',      N'年調保険備考',   N'year_end_insurance_note',    N'名称変更', CAST(1 AS BIT)),
        (N't_resident',   N'mx_resident',   N'宛名番号',       N'addressee_no',               N'名称変更', CAST(1 AS BIT)),
        (N't_nen_tyo',    N'mx_nen_tyo',    N'16miman_fuyo',   N'dependent_under_16',         N'名称変更', CAST(1 AS BIT)),
        (N't_kyuyo_shou', N'mx_kyuyo_shou', N'振込残額',       N'transfer_balance',           N'名称変更', CAST(1 AS BIT)),
        (N't_kyuyo_shou', N'mx_kyuyo_shou', N'会社立替費用',   N'company_advance_cost',       N'名称変更', CAST(1 AS BIT)),
        (N't_mayor',      N'mx_mayor',      N'office_name',    N'office_no',                  N'名称変更 + 型差分 old nvarchar(3) / new int', CAST(1 AS BIT))
    ) AS v(legacy_table, new_table, legacy_column, new_column, note, is_active)
),
tcpg_rename_rows AS (
    SELECT *
    FROM (VALUES
        (N'T_勘定科目', N'mx_account_titles', N'ID',                     N'account_title_id',               CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_勘定科目', N'mx_account_titles', N'勘定科目',               N'account_title_name',             CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_勘定科目', N'mx_account_titles', N'表示名（決算書）',       N'statement_display_name',         CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_勘定科目', N'mx_account_titles', N'小分類',                 N'sub_category_name',              CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_勘定科目', N'mx_account_titles', N'中分類',                 N'middle_category_name',           CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_勘定科目', N'mx_account_titles', N'大分類',                 N'major_category_name',            CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_勘定科目', N'mx_account_titles', N'収入取引相手方勘定科目', N'receipt_counterparty_title',     CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_勘定科目', N'mx_account_titles', N'支出取引相手方勘定科目', N'payment_counterparty_title',     CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_勘定科目', N'mx_account_titles', N'税区分',                 N'tax_category_name',              CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_勘定科目', N'mx_account_titles', N'ショートカット1',        N'shortcut_1',                     CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_勘定科目', N'mx_account_titles', N'ショートカット2',        N'shortcut_2',                     CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_勘定科目', N'mx_account_titles', N'入力候補',               N'input_candidates',               CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_勘定科目', N'mx_account_titles', N'部門',                   N'department_name',                CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_勘定科目', N'mx_account_titles', N'未使用',                 N'is_unused',                      CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_勘定科目', N'mx_account_titles', N'入金確認',               N'is_payment_check',               CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_勘定科目', N'mx_account_titles', N'sort',                   N'sort_order',                     CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_勘定科目', N'mx_account_titles', N'損益',                   N'is_profit_loss',                 CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_勘定科目', N'mx_account_titles', N'貸借',                   N'is_debit_credit',                CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_勘定科目', N'mx_account_titles', N'経費並び',               N'expense_sort_name',              CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_勘定科目', N'mx_account_titles', N'試算表並び',             N'trial_balance_sort',             CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),

        (N'T_仕訳帳',   N'mx_journal_entries', N'仕訳No',               N'legacy_journal_no',              CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'仕訳内訳',             N'journal_breakdown',              CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'管理番号',             N'management_number',              CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'月度',                 N'month_date',                     CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'発生日',               N'occurred_at',                    CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'金庫',                 N'vault_name',                     CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'入金',                 N'deposit_amount',                 CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'出金',                 N'withdrawal_amount',              CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'借方勘定科目',         N'debit_account_title',            CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'借方金額',             N'debit_amount',                   CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'借方税区分',           N'debit_tax_category',             CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'借方品目',             N'debit_item_name',                CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'借方取引先',           N'debit_counterparty',             CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'借方部門',             N'debit_department_name',          CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'借方メモタグ',         N'debit_memo_tag',                 CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'貸方勘定科目',         N'credit_account_title',           CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'貸方金額',             N'credit_amount',                  CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'貸方税区分',           N'credit_tax_category',            CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'貸方品目',             N'credit_item_name',               CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'貸方取引先',           N'credit_counterparty',            CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'貸方部門',             N'credit_department_name',         CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'貸方メモタグ',         N'credit_memo_tag',                CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'摘要',                 N'summary_text',                   CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'決算整理仕訳',         N'closing_journal_entry',          CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'アップ不要',           N'is_upload_unnecessary',          CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'確認チェック',         N'is_confirmation_checked',        CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'入金名称',             N'deposit_name',                   CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'管理備考',             N'management_note',                CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'入金確認備考',         N'deposit_confirmation_note',      CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'経理備考',             N'accounting_note',                CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'仕訳済',               N'is_journalized',                 CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'社名',                 N'company_name_short',             CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'入金額',               N'received_amount',                CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'試算表除外',           N'is_trial_balance_excluded',      CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'経理チェック',         N'is_accounting_checked',          CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'経理リスト確認',       N'is_accounting_list_checked',     CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'分割割合',             N'allocation_ratio',               CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'仕訳備考',             N'journal_note',                   CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_仕訳帳',   N'mx_journal_entries', N'報酬計算除外',         N'is_reward_calculation_excluded', CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),

        (N'T_部門',     N'mx_departments',     N'店舗略称',             N'store_short_name',              CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_部門',     N'mx_departments',     N'店舗分類',             N'store_category',                CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_部門',     N'mx_departments',     N'正式名称',             N'official_store_no',             CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_部門',     N'mx_departments',     N'レセ店舗No',           N'receipt_store_no',              CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_部門',     N'mx_departments',     N'閉店日',               N'closed_on',                     CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_部門',     N'mx_departments',     N'部門No',               N'department_no',                 CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_部門',     N'mx_departments',     N'レセ有',               N'has_receipt',                   CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_部門',     N'mx_departments',     N'通帳選択',             N'bank_account_selection',        CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT)),
        (N'T_部門',     N'mx_departments',     N'No',                   N'legacy_no',                     CAST(NULL AS NVARCHAR(255)), CAST(1 AS BIT))
    ) AS v(legacy_table, new_table, legacy_column, new_column, note, is_active)
),
same_name_rows AS (
    SELECT
        tm.mapping_id AS table_mapping_id,
        oldc.COLUMN_NAME AS legacy_column,
        newc.COLUMN_NAME AS new_column,
        CASE
            WHEN tm.legacy_table = N't_allowance' AND oldc.COLUMN_NAME = N'office_name' THEN N'型差分 old nvarchar(3) / new int'
            WHEN tm.legacy_table IN (N't_rouho', N't_syaho') AND oldc.COLUMN_NAME = N'office_no' THEN N'型差分 old nvarchar(3) / new int'
            ELSE CAST(NULL AS NVARCHAR(255))
        END AS note,
        CAST(1 AS BIT) AS is_active
    FROM dbo.m_db_table_mappings tm
    INNER JOIN Payroll.INFORMATION_SCHEMA.COLUMNS oldc
        ON oldc.TABLE_SCHEMA = tm.legacy_schema
       AND oldc.TABLE_NAME = tm.legacy_table
    INNER JOIN Payroll_DEV.INFORMATION_SCHEMA.COLUMNS newc
        ON newc.TABLE_SCHEMA = tm.new_schema
       AND newc.TABLE_NAME = tm.new_table
       AND newc.COLUMN_NAME = oldc.COLUMN_NAME
    WHERE tm.legacy_db = N'Payroll'
      AND tm.new_db = N'Payroll_DEV'
      AND NOT EXISTS (
          SELECT 1
          FROM rename_rows rr
          WHERE rr.legacy_table = tm.legacy_table
            AND rr.new_table = tm.new_table
            AND rr.legacy_column = oldc.COLUMN_NAME
      )
),
renamed_rows AS (
    SELECT
        tm.mapping_id AS table_mapping_id,
        rr.legacy_column,
        rr.new_column,
        rr.note,
        rr.is_active
    FROM rename_rows rr
    INNER JOIN dbo.m_db_table_mappings tm
        ON tm.legacy_db = N'Payroll'
       AND tm.legacy_schema = N'dbo'
       AND tm.legacy_table = rr.legacy_table
       AND tm.new_db = N'Payroll_DEV'
       AND tm.new_schema = N'dbo'
       AND tm.new_table = rr.new_table
    UNION ALL
    SELECT
        tm.mapping_id AS table_mapping_id,
        rr.legacy_column,
        rr.new_column,
        rr.note,
        rr.is_active
    FROM tcpg_rename_rows rr
    INNER JOIN dbo.m_db_table_mappings tm
        ON tm.legacy_db = N'TCPGSYSTEM'
       AND tm.legacy_schema = N'dbo'
       AND tm.legacy_table = rr.legacy_table
       AND tm.new_db = N'TCPGSYSTEM_DEV'
       AND tm.new_schema = N'dbo'
       AND tm.new_table = rr.new_table
),
source_rows AS (
    SELECT * FROM same_name_rows
    UNION ALL
    SELECT * FROM renamed_rows
)
MERGE dbo.m_db_column_mappings AS target
USING source_rows AS source
ON  target.table_mapping_id = source.table_mapping_id
AND target.legacy_column    = source.legacy_column
AND target.new_column       = source.new_column
WHEN MATCHED THEN
    UPDATE SET
        note = source.note,
        is_active = source.is_active,
        updated_at = SYSUTCDATETIME()
WHEN NOT MATCHED THEN
    INSERT (table_mapping_id, legacy_column, new_column, note, is_active)
    VALUES (source.table_mapping_id, source.legacy_column, source.new_column, source.note, source.is_active);
GO

SELECT
    cm.column_mapping_id,
    tm.legacy_table,
    cm.legacy_column,
    tm.new_table,
    cm.new_column,
    cm.note,
    cm.is_active
FROM dbo.m_db_column_mappings cm
INNER JOIN dbo.m_db_table_mappings tm
    ON tm.mapping_id = cm.table_mapping_id
ORDER BY tm.legacy_table, cm.column_mapping_id;

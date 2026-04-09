/*
  TCPGSYSTEM_DEV (sqlsrv connection)
  Recreate readable English tables from legacy Access-era tables.

  Source legacy tables:
    - dbo.[T_蜍伜ｮ夂ｧ醍岼]
    - dbo.[T_莉戊ｨｳ蟶ｳ]

  Target tables:
    - dbo.mx_account_titles
    - dbo.mx_journal_entries

  Notes:
    - Existing placeholder tables were removed before this script.
    - This script creates the tables with readable column names.
    - If the legacy tables still exist in the same database, it also imports data.
*/
SET NOCOUNT ON;

/* ---------------------------------------------------------
   1) mx_account_titles (from T_蜍伜ｮ夂ｧ醍岼)
--------------------------------------------------------- */
IF OBJECT_ID(N'dbo.mx_account_titles', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.mx_account_titles (
        account_title_id INT NOT NULL PRIMARY KEY,
        account_title_name NVARCHAR(50) NULL,
        statement_display_name NVARCHAR(50) NULL,
        sub_category_name NVARCHAR(50) NULL,
        middle_category_name NVARCHAR(50) NULL,
        major_category_name NVARCHAR(50) NULL,
        receipt_counterparty_title NVARCHAR(50) NULL,
        payment_counterparty_title NVARCHAR(50) NULL,
        tax_category_name NVARCHAR(50) NULL,
        shortcut_1 NVARCHAR(50) NULL,
        shortcut_2 NVARCHAR(50) NULL,
        input_candidates NVARCHAR(50) NULL,
        department_name NVARCHAR(20) NULL,
        is_unused BIT NULL,
        is_payment_check BIT NULL,
        sort_order INT NULL,
        is_profit_loss BIT NULL,
        is_debit_credit BIT NULL,
        expense_sort_name NVARCHAR(20) NULL,
        trial_balance_sort INT NULL,
        created_at DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        updated_at DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME()
    );
END;

IF OBJECT_ID(N'dbo.[T_蜍伜ｮ夂ｧ醍岼]', N'U') IS NOT NULL
BEGIN
    TRUNCATE TABLE dbo.mx_account_titles;

    INSERT INTO dbo.mx_account_titles (
        account_title_id,
        account_title_name,
        statement_display_name,
        sub_category_name,
        middle_category_name,
        major_category_name,
        receipt_counterparty_title,
        payment_counterparty_title,
        tax_category_name,
        shortcut_1,
        shortcut_2,
        input_candidates,
        department_name,
        is_unused,
        is_payment_check,
        sort_order,
        is_profit_loss,
        is_debit_credit,
        expense_sort_name,
        trial_balance_sort
    )
    SELECT
        TRY_CONVERT(INT, [ID]) AS account_title_id,
        CAST([蜍伜ｮ夂ｧ醍岼] AS NVARCHAR(50)) AS account_title_name,
        CAST([陦ｨ遉ｺ蜷搾ｼ域ｱｺ邂玲嶌・云 AS NVARCHAR(50)) AS statement_display_name,
        CAST([蟆丞・鬘枉 AS NVARCHAR(50)) AS sub_category_name,
        CAST([荳ｭ蛻・｡枉 AS NVARCHAR(50)) AS middle_category_name,
        CAST([螟ｧ蛻・｡枉 AS NVARCHAR(50)) AS major_category_name,
        CAST([蜿主・蜿門ｼ慕嶌謇区婿蜍伜ｮ夂ｧ醍岼] AS NVARCHAR(50)) AS receipt_counterparty_title,
        CAST([謾ｯ蜃ｺ蜿門ｼ慕嶌謇区婿蜍伜ｮ夂ｧ醍岼] AS NVARCHAR(50)) AS payment_counterparty_title,
        CAST([遞主玄蛻・ AS NVARCHAR(50)) AS tax_category_name,
        CAST([繧ｷ繝ｧ繝ｼ繝医き繝・ヨ1] AS NVARCHAR(50)) AS shortcut_1,
        CAST([繧ｷ繝ｧ繝ｼ繝医き繝・ヨ2] AS NVARCHAR(50)) AS shortcut_2,
        CAST([蜈･蜉帛､] AS NVARCHAR(50)) AS input_candidates,
        CAST([驛ｨ髢] AS NVARCHAR(20)) AS department_name,
        TRY_CONVERT(BIT, [譛ｪ菴ｿ逕ｨ]) AS is_unused,
        TRY_CONVERT(BIT, [蜈･驥醍｢ｺ隱江) AS is_payment_check,
        TRY_CONVERT(INT, [sort]) AS sort_order,
        TRY_CONVERT(BIT, [謳咲寢]) AS is_profit_loss,
        TRY_CONVERT(BIT, [雋ｸ蛟歉) AS is_debit_credit,
        CAST([邨瑚ｲｻ荳ｦ縺ｳ] AS NVARCHAR(20)) AS expense_sort_name,
        TRY_CONVERT(INT, [隧ｦ邂苓｡ｨ荳ｦ縺ｳ]) AS trial_balance_sort
    FROM dbo.[T_蜍伜ｮ夂ｧ醍岼]
    WHERE TRY_CONVERT(INT, [ID]) IS NOT NULL;
END;

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.mx_account_titles')
      AND name = N'IX_mx_account_titles_name'
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_mx_account_titles_name
        ON dbo.mx_account_titles (account_title_name);
END;

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.mx_account_titles')
      AND name = N'IX_mx_account_titles_statement_display_name'
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_mx_account_titles_statement_display_name
        ON dbo.mx_account_titles (statement_display_name);
END;

/* ---------------------------------------------------------
   2) mx_journal_entries (from T_莉戊ｨｳ蟶ｳ)
--------------------------------------------------------- */
IF OBJECT_ID(N'dbo.mx_journal_entries', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.mx_journal_entries (
        journal_entry_id INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        legacy_journal_no INT NULL,
        journal_summary NVARCHAR(20) NULL,
        management_number NVARCHAR(20) NULL,
        month_date DATETIME NULL,
        posted_at DATETIME NULL,
        vault_name NVARCHAR(50) NULL,
        deposit_amount MONEY NULL,
        withdrawal_amount MONEY NULL,
        debit_account_title NVARCHAR(30) NULL,
        debit_amount MONEY NULL,
        debit_tax_category NVARCHAR(20) NULL,
        debit_item_name NVARCHAR(50) NULL,
        debit_counterparty NVARCHAR(30) NULL,
        debit_department_name NVARCHAR(20) NULL,
        debit_memo_tag NVARCHAR(255) NULL,
        credit_account_title NVARCHAR(30) NULL,
        credit_amount MONEY NULL,
        credit_tax_category NVARCHAR(20) NULL,
        credit_item_name NVARCHAR(50) NULL,
        credit_counterparty NVARCHAR(30) NULL,
        credit_department_name NVARCHAR(20) NULL,
        credit_memo_tag NVARCHAR(255) NULL,
        summary_text NVARCHAR(255) NULL,
        closing_slip_summary NVARCHAR(50) NULL,
        is_checked BIT NULL,
        is_confirmation_checked BIT NULL,
        deposit_name NVARCHAR(50) NULL,
        management_note NVARCHAR(255) NULL,
        deposit_confirmation_note NVARCHAR(255) NULL,
        process_note NVARCHAR(255) NULL,
        is_journalized BIT NULL,
        company_code NVARCHAR(10) NULL,
        deposit_total_amount MONEY NULL,
        is_trial_balance_excluded BIT NULL,
        is_process_checked BIT NULL,
        is_expense_list_checked BIT NULL,
        allocation_ratio INT NULL,
        exit_note NVARCHAR(255) NULL,
        is_reward_calculation_excluded BIT NULL,
        created_at DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME(),
        updated_at DATETIME2(0) NOT NULL DEFAULT SYSUTCDATETIME()
    );
END;

IF OBJECT_ID(N'dbo.[T_莉戊ｨｳ蟶ｳ]', N'U') IS NOT NULL
BEGIN
    TRUNCATE TABLE dbo.mx_journal_entries;

    SET IDENTITY_INSERT dbo.mx_journal_entries OFF;

    INSERT INTO dbo.mx_journal_entries (
        legacy_journal_no,
        journal_summary,
        management_number,
        month_date,
        posted_at,
        vault_name,
        deposit_amount,
        withdrawal_amount,
        debit_account_title,
        debit_amount,
        debit_tax_category,
        debit_item_name,
        debit_counterparty,
        debit_department_name,
        debit_memo_tag,
        credit_account_title,
        credit_amount,
        credit_tax_category,
        credit_item_name,
        credit_counterparty,
        credit_department_name,
        credit_memo_tag,
        summary_text,
        closing_slip_summary,
        is_checked,
        is_confirmation_checked,
        deposit_name,
        management_note,
        deposit_confirmation_note,
        process_note,
        is_journalized,
        company_code,
        deposit_total_amount,
        is_trial_balance_excluded,
        is_process_checked,
        is_expense_list_checked,
        allocation_ratio,
        exit_note,
        is_reward_calculation_excluded
    )
    SELECT
        TRY_CONVERT(INT, [莉戊ｨｳNo]) AS legacy_journal_no,
        CAST([莉戊ｨｳ險ｳ] AS NVARCHAR(20)) AS journal_summary,
        CAST([邂｡逅・分蜿ｷ] AS NVARCHAR(20)) AS management_number,
        TRY_CONVERT(DATETIME, [譛亥ｺｦ]) AS month_date,
        TRY_CONVERT(DATETIME, [逋ｺ逕滓律]) AS posted_at,
        CAST([驥大ｺｫ] AS NVARCHAR(50)) AS vault_name,
        TRY_CONVERT(MONEY, [蜈･驥曽) AS deposit_amount,
        TRY_CONVERT(MONEY, [蜃ｺ驥曽) AS withdrawal_amount,
        CAST([蛟滓婿蜍伜ｮ夂ｧ醍岼] AS NVARCHAR(30)) AS debit_account_title,
        TRY_CONVERT(MONEY, [蛟滓婿驥鷹｡江) AS debit_amount,
        CAST([蛟滓婿遞主玄蛻・ AS NVARCHAR(20)) AS debit_tax_category,
        CAST([蛟滓婿蜩∫岼] AS NVARCHAR(50)) AS debit_item_name,
        CAST([蛟滓婿蜿門ｼ募・] AS NVARCHAR(30)) AS debit_counterparty,
        CAST([蛟滓婿驛ｨ髢] AS NVARCHAR(20)) AS debit_department_name,
        CAST([蛟滓婿繝｡繝｢繧ｿ繧ｰ] AS NVARCHAR(255)) AS debit_memo_tag,
        CAST([雋ｸ譁ｹ蜍伜ｮ夂ｧ醍岼] AS NVARCHAR(30)) AS credit_account_title,
        TRY_CONVERT(MONEY, [雋ｸ譁ｹ驥鷹｡江) AS credit_amount,
        CAST([雋ｸ譁ｹ遞主玄蛻・ AS NVARCHAR(20)) AS credit_tax_category,
        CAST([雋ｸ譁ｹ蜩∫岼] AS NVARCHAR(50)) AS credit_item_name,
        CAST([雋ｸ譁ｹ蜿門ｼ募・] AS NVARCHAR(30)) AS credit_counterparty,
        CAST([雋ｸ譁ｹ驛ｨ髢] AS NVARCHAR(20)) AS credit_department_name,
        CAST([雋ｸ譁ｹ繝｡繝｢繧ｿ繧ｰ] AS NVARCHAR(255)) AS credit_memo_tag,
        CAST([鞫倩ｦ‐ AS NVARCHAR(255)) AS summary_text,
        CAST([豎ｺ邂玲紛逅・ｻ戊ｨｳ] AS NVARCHAR(50)) AS closing_slip_summary,
        TRY_CONVERT(BIT, [繧｢繝・・隕‐) AS is_checked,
        TRY_CONVERT(BIT, [遒ｺ隱阪メ繧ｧ繝・け]) AS is_confirmation_checked,
        CAST([蜈･驥大錐遘ｰ] AS NVARCHAR(50)) AS deposit_name,
        CAST([邂｡逅・ｙ閠ゾ AS NVARCHAR(255)) AS management_note,
        CAST([蜈･驥醍｢ｺ隱榊ｙ閠ゾ AS NVARCHAR(255)) AS deposit_confirmation_note,
        CAST([邨檎炊蛯呵ゾ AS NVARCHAR(255)) AS process_note,
        TRY_CONVERT(BIT, [莉戊ｨｳ貂・) AS is_journalized,
        CAST([遉ｾ蜷江 AS NVARCHAR(10)) AS company_code,
        TRY_CONVERT(MONEY, [蜈･驥鷹｡江) AS deposit_total_amount,
        TRY_CONVERT(BIT, [隧ｦ邂苓｡ｨ髯､螟望) AS is_trial_balance_excluded,
        TRY_CONVERT(BIT, [邨檎炊繝√ぉ繝・け]) AS is_process_checked,
        TRY_CONVERT(BIT, [邨檎炊繝ｪ繧ｹ繝育｢ｺ隱江) AS is_expense_list_checked,
        TRY_CONVERT(INT, [蛻・牡蜑ｲ蜷・) AS allocation_ratio,
        CAST([蜃ｺ險ｳ蛯呵ゾ AS NVARCHAR(255)) AS exit_note,
        TRY_CONVERT(BIT, [蝣ｱ驟ｬ險育ｮ鈴勁螟望) AS is_reward_calculation_excluded
    FROM dbo.[T_莉戊ｨｳ蟶ｳ];
END;

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.mx_journal_entries')
      AND name = N'IX_mx_journal_entries_month_date'
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_mx_journal_entries_month_date
        ON dbo.mx_journal_entries (month_date);
END;

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.mx_journal_entries')
      AND name = N'IX_mx_journal_entries_company_code'
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_mx_journal_entries_company_code
        ON dbo.mx_journal_entries (company_code);
END;

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.mx_journal_entries')
      AND name = N'IX_mx_journal_entries_credit_department_name'
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_mx_journal_entries_credit_department_name
        ON dbo.mx_journal_entries (credit_department_name, month_date);
END;

IF NOT EXISTS (
    SELECT 1
    FROM sys.indexes
    WHERE object_id = OBJECT_ID(N'dbo.mx_journal_entries')
      AND name = N'IX_mx_journal_entries_credit_item_name'
)
BEGIN
    CREATE NONCLUSTERED INDEX IX_mx_journal_entries_credit_item_name
        ON dbo.mx_journal_entries (credit_item_name, credit_account_title);
END;

/* ---------------------------------------------------------
   Summary
--------------------------------------------------------- */
SELECT
    'mx_account_titles' AS table_name,
    COUNT(1) AS row_count
FROM dbo.mx_account_titles
UNION ALL
SELECT
    'mx_journal_entries',
    COUNT(1)
FROM dbo.mx_journal_entries;

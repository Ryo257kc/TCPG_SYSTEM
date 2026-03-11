/*
  t_allowance を主運用テーブルとして使うための最小インデックス。
  対象: Payroll_DEV（検証DB）
*/

USE [Payroll_DEV];
GO

IF OBJECT_ID('dbo.t_allowance', 'U') IS NULL
BEGIN
    RAISERROR('dbo.t_allowance not found.', 16, 1);
    RETURN;
END;
GO

-- 会社 + AmountKey の重複防止（空文字は除外）
IF COL_LENGTH('dbo.t_allowance', 'office_name') IS NOT NULL
   AND COL_LENGTH('dbo.t_allowance', 'amount_column_key') IS NOT NULL
   AND NOT EXISTS (
        SELECT 1
        FROM sys.indexes
        WHERE object_id = OBJECT_ID('dbo.t_allowance')
          AND name = 'UX_t_allowance_office_amount_key'
   )
BEGIN
    CREATE UNIQUE NONCLUSTERED INDEX UX_t_allowance_office_amount_key
    ON dbo.t_allowance (office_name, amount_column_key)
    WHERE amount_column_key IS NOT NULL AND LTRIM(RTRIM(amount_column_key)) <> '';
END;
GO

-- 一覧表示/並び替え用
IF COL_LENGTH('dbo.t_allowance', 'office_name') IS NOT NULL
   AND COL_LENGTH('dbo.t_allowance', 'display_order') IS NOT NULL
   AND COL_LENGTH('dbo.t_allowance', 'slot_no') IS NOT NULL
   AND NOT EXISTS (
        SELECT 1
        FROM sys.indexes
        WHERE object_id = OBJECT_ID('dbo.t_allowance')
          AND name = 'IX_t_allowance_office_display_slot'
   )
BEGIN
    CREATE NONCLUSTERED INDEX IX_t_allowance_office_display_slot
    ON dbo.t_allowance (office_name, display_order, slot_no, allowance_no);
END;
GO

SELECT
    i.name AS index_name,
    i.is_unique,
    i.type_desc
FROM sys.indexes i
WHERE i.object_id = OBJECT_ID('dbo.t_allowance')
  AND i.name IN ('UX_t_allowance_office_amount_key', 'IX_t_allowance_office_display_slot');
GO


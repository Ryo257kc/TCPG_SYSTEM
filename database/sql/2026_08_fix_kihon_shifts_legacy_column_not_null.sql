/*
  基本シフト（mx_kihon_shifts）の「新規登録」が必ずSQLエラーになっていた不具合の修正。

  x_legacy_t_kihon_shift_14143084d8列（bit, NOT NULL, デフォルト値なし）は、
  アプリケーションコード側から一切参照されていない（grep で0件）。移行元テーブル
  （t_kihon_shift）由来の残骸列と見られ、既存231行には0/1の値が入っているため
  過去に何らかの意味を持って使われていたことは分かるが、現行コードはこの列の
  意味を一切把握していない。

  安全側に倒し、列は削除せずNULL許容に変更するだけに留める（既存データは無傷、
  新規行はこの列を意識せず保存できるようになる）。

  冪等（現在の状態を確認してからのみ変更）。
*/

SET NOCOUNT ON;

BEGIN TRY
    BEGIN TRAN;

    IF EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'mx_kihon_shifts'
          AND COLUMN_NAME = 'x_legacy_t_kihon_shift_14143084d8'
          AND IS_NULLABLE = 'NO'
    )
    BEGIN
        ALTER TABLE dbo.mx_kihon_shifts
            ALTER COLUMN x_legacy_t_kihon_shift_14143084d8 BIT NULL;
    END;

    COMMIT;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0
        ROLLBACK;

    THROW;
END CATCH;

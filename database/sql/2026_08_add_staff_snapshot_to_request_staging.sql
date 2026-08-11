/*
  個人情報変更申請・入社手続き申請：反映（mx_staffsへの書き込み）時の競合検知用。

  「確認済み」にした時点のmx_staffs該当項目の値をJSONでスナップショット保存し、
  「反映」実行時に再度mx_staffsを読み直して比較する。値が変わっていた場合は
  （＝確認〜反映の間に別の管理者がマスタを直接編集した場合）、反映を中断し
  「提出済み」へ差し戻して再確認を求める。事務所が複数人で同時に触る運用に
  変わったときのための保険（現状は運用上そのタイミングは起きない想定）。

  冪等（IF COL_LENGTH(...) IS NULL）。
*/

SET NOCOUNT ON;

BEGIN TRY
    BEGIN TRAN;

    IF COL_LENGTH('dbo.staff_profile_requests', 'staff_snapshot_json') IS NULL
    BEGIN
        ALTER TABLE dbo.staff_profile_requests
            ADD staff_snapshot_json NVARCHAR(MAX) NULL;
    END;

    IF COL_LENGTH('dbo.staff_onboarding_requests', 'staff_snapshot_json') IS NULL
    BEGIN
        ALTER TABLE dbo.staff_onboarding_requests
            ADD staff_snapshot_json NVARCHAR(MAX) NULL;
    END;

    COMMIT;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0
        ROLLBACK;

    THROW;
END CATCH;

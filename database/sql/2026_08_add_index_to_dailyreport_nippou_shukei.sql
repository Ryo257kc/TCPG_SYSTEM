SET NOCOUNT ON;
GO

/*
目的:
- 3DBのインデックス総点検（2026-08-15）で、DailyReport_DEV.T_日報集計にPK以外の
  索引が無いことが判明したため追加する。退避済みの複製テーブル
  （（不要）T_日報集計-1）には本来 (日報集計No, 日報集計店舗, 日付) 相当の索引が
  残っていたが、本体のT_日報集計には無くなっていた。

対象: DailyReport_DEV.dbo.T_日報集計

追加理由:
- StoreDailyReportController.php で `whereDate('T_日報集計.日付', ...)` による
  日付絞り込みの問い合わせが多数ある。

注意:
- この接続に使うDBアカウントは、当初インデックス作成（ALTER相当）の権限が
  無くエラーになった。ユーザー側で権限を付与してもらい解決した。
- 列名は日本語で「店舗」ではなく「日報集計店舗」なので注意。
*/

CREATE NONCLUSTERED INDEX IX_T_日報集計_date_store
    ON dbo.T_日報集計 (日付, 日報集計店舗);
GO

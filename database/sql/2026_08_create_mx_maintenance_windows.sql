-- メンテナンス時間帯の設定テーブル
-- この期間中はシステムマスタ以外、ログイン不可・既存セッションも次のページ遷移で強制ログアウトになる
CREATE TABLE dbo.mx_maintenance_windows (
    maintenance_id INT IDENTITY(1,1) PRIMARY KEY,
    start_at DATETIME2 NOT NULL,
    end_at DATETIME2 NOT NULL,
    message NVARCHAR(200) NULL,
    created_at DATETIME2 NULL,
    updated_at DATETIME2 NULL
);

# 管理者2FAの信頼済み端末（専用テーブル作成済み・ローカルのみ有効）

## 状態と適用範囲

既存の独自ID・パスワード認証とセッションを維持する。対象は管理者の `/admin/login` のみ。
初期化コマンドは既存の `admin:2fa-reset` を再利用する。別の初期化機能やWebの回避URLは追加しない。
2026-10-23、ユーザーの明示承認後に `TCPGSYSTEM_DEV.dbo.admin_two_factor_trusted_devices` を新規作成した。
8列・主キー・スタッフIDインデックスを確認し、作成直後のデータは0件。
作成時に既存テーブル・データは変更していない。実際の初期化と本番サーバーへのコード反映は未実施。

## 承認済み・適用済みのテーブル追加SQL

次の専用テーブル1つだけを作成した。既存テーブルへのALTER・外部キー・データ移行・初期データ投入は行っていない。
SQL Serverでの端末情報の実書込み・排他動作は、自動書込みテストをしていないため未検証。

2026-10-23、ユーザーがローカル実機でログアウト・再ログインし、信頼済み端末ではTOTP入力が
省略されることを確認済み。これはユーザーによる操作確認であり、自動テストの本番DB書込みではない。

```sql
USE [TCPGSYSTEM_DEV];
SET XACT_ABORT ON;
SET LOCK_TIMEOUT 5000;

BEGIN TRY
    BEGIN TRANSACTION;

    IF OBJECT_ID(N'dbo.admin_two_factor_trusted_devices') IS NOT NULL
        THROW 50001, N'同名オブジェクトが存在するため中止します。', 1;

    CREATE TABLE dbo.admin_two_factor_trusted_devices (
        id CHAR(32) NOT NULL
            CONSTRAINT PK_admin_two_factor_trusted_devices PRIMARY KEY,
        staff_id NVARCHAR(50) NOT NULL,
        token_hash CHAR(64) NOT NULL,
        auth_version CHAR(64) NOT NULL,
        password_fingerprint CHAR(64) NOT NULL,
        expires_at DATETIME2 NOT NULL,
        created_at DATETIME2 NOT NULL,
        updated_at DATETIME2 NOT NULL,
        INDEX IX_admin_two_factor_trusted_devices_staff NONCLUSTERED (staff_id)
    );

    COMMIT TRANSACTION;
END TRY
BEGIN CATCH
    IF XACT_STATE() <> 0 ROLLBACK TRANSACTION;
    THROW;
END CATCH;
```

有効化の指示を受け、ローカル `.env` に次の値を設定済み。本番サーバーの環境設定は変更していない。

```dotenv
ADMIN_TWO_FACTOR_TRUSTED_DEVICES_APPROVED=true
```

既定値はfalseのままで、明示設定のない環境では動作しない。従来のTOTPはそのまま利用できる。

## 信頼済み端末の動作

- TOTP入力画面の「この端末を30日間信頼する」は既定で未選択。
- 初回QR登録ではTOTP確認とリカバリーコード保存確認が両方完了してから信頼を発行する。
- 登録済みの場合はTOTP確認後に発行する。リカバリーコードだけで新しい端末の信頼は発行しない。
- 有効期限は発行から30日固定。途中のログインで期限を延長しない。
- ログイン時は、従来のID・パスワード・管理者権限・退職判定を先に必ず行う。
- 有効な端末トークンがある場合だけTOTPを省略し、従来と同じ管理者セッションを確定する。
- 期限切れ・不正・別アカウント・失効済みトークンでは通常のTOTPへ進む。
- ログアウトは通常セッションを破棄するが、信頼済み端末Cookieは残す。

## 保存と保護

- ランダム128bitの端末IDと、ランダム256bitの秘密トークンを使用する。
- DBには秘密トークンのSHA-256ハッシュだけを保存する。パスワード検知情報はAPP_KEY付きHMACで、パスワードそのものは保存しない。
- CookieはLaravel標準の暗号化・改ざん検知を受け、HttpOnly・SameSite=Strict・ホスト限定・`/admin` パスにする。
- HTTPSではSecureを付ける。平文HTTPはローカル検証のループバックホストだけに限定し、それ以外では信頼を発行・利用しない。
- トークン使用時は行ロックして新しい秘密トークンへ更新し、古いコピーの再利用を拒否する。
- 生のトークンはログ・DB・HTMLへ出力しない。

## 全端末の失効

2FA初期化は既存の `auth_version` を更新するため、全端末の旧トークンと既存の管理者認証が無効になる。
古い端末行を物理削除しなくても、照合時に世代不一致で拒否する。次回は必ずQR再登録になる。

アプリ内の既存パスワード変更処理は、同じSQL Server接続のトランザクションで次を行う構成にする。

1. 対象者の2FA世代を更新（秘密鍵は維持）
2. 対象者の信頼済み端末行をすべて削除
3. 従来のパスワード更新を実行

これにより変更後にパスワードを元へ戻しても、旧端末トークンは復活しない。他のスタッフは対象外。
パスワード認証途中にも現在のパスワード検知情報を照合し、変更前の認証途中画面では新端末を信頼登録できない。
アプリ外からのパスワード更新も現在値が異なる間はトークンを拒否するが、変更と元への復帰がすべてアプリ外で行われた履歴までは検知できない。

## 検証

スタッフ・2FA情報・端末情報をメモリに置き換えたHTTPテスト、およびDB接続をモックしたトランザクション境界のテストで検証する。
本番DBへ書き込むテストや初期化コマンドの実実行は行わない。

```text
php vendor/phpunit/phpunit/phpunit --do-not-cache-result tests/Feature/AdminTwoFactorTest.php tests/Feature/AdminTwoFactorCredentialStoreTest.php tests/Feature/AdminTrustedDevicePasswordChangeTest.php
```

2026-10-23の全体確認: PHPUnit 52項目・595アサーション成功（うち2FA関連43項目・547アサーション）。
通常スタッフ・管理者によるスタッフ入口利用・初期パスワード変更への誘導も、実DBを使わず確認済み。
`php artisan view:clear`・`php artisan view:cache` も成功。

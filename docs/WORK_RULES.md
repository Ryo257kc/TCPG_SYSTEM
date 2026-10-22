# Work Rules

## Scope
- Edit targets are limited to `resources/views/admin/**`, `app/Http/Controllers/Admin/**`, `routes/web.php` (admin routes only), and admin-related docs/scripts.
- Do not edit staff-side pages/routes/controllers.

## Mandatory Start Gate
- Before any change, always output: `TARGET: tcpg_system_admin_laravel`.
- Do not run edit commands until user reply is `OK`.

## Freeze Rule
- Completed screens/files are freeze targets.
- Do not modify freeze targets unless user explicitly approves unfreeze for that file.

## Change Isolation
- One task should touch a minimal set of files (prefer 1-3 files).
- Separate view text fixes and logic fixes into different tasks.

## Verification
- After each change, run:
  - `php artisan view:clear`
  - `php artisan view:cache`
- If either fails, stop and fix before the next change.

## Enforced By Hooks
- Hooks path: `.githooks` (set by `scripts/install-hooks.ps1`)
- Main checker: `scripts/enforce-work-rules.ps1`

### Enforced 7 Rules
1. Admin-scope only: reject staged files outside admin scope.
2. Freeze protection: reject staged files listed in `.freeze-files.txt`.
3. Isolation: reject commit when work-file count exceeds 3.
4. Build check: run `php artisan view:clear` and `php artisan view:cache` before commit.
5. Target declaration: `commit-msg` must include `TARGET: tcpg_system_admin_laravel`.
6. Page-level separation: reject commits that modify multiple admin pages at once.
7. Strict admin-folder rule: reject non-admin views/controllers explicitly.
8. New page structure rule: new admin view files must be
   `resources/views/admin/<page>/index.blade.php`
   or `resources/views/admin/<page>/partials/<name>.blade.php`.

## 本番DBの事前承認（2026-10-23追記）

- `TCPGSYSTEM_DEV` は、名称に `DEV` が含まれていても実運用中の本番DBとして扱う。
  DB名・接続名やローカルLaravelの `APP_ENV=local` を理由に、開発用DBと判断しない。
- 本番DBのテーブル作成・変更・削除、データの追加・更新・削除は、必ず実行前にユーザーの
  明示承認を得る。Migration・一時SQL・テスト経由の書込みも同じ扱いとする。
- 承認前に、接続先DB・実行予定SQL・対象と影響・バックアップの確認状況を提示する。
  承認された対象と操作だけを実行し、テーブル作成の承認をデータ更新や機能有効化へ拡大しない。
- ローカルでの機能検証でも本番DBへ接続し得る。自動テストはメモリ保存・モック等を使い、
  本番DBを初期化・更新するテストや実際の2FA初期化コマンドを無断実行しない。

## 認証・セッションと管理者2FA（2026-10-23追記）

- 認証・セッション関連を変更する場合は、`/admin/login` と `/staff/login` の両方への影響を
  確認する。`/staff/admin-login` は管理者からスタッフ画面への引継ぎ経路として別途確認する。
  既存の独自認証・パスワード保存方式・業務権限を、依頼範囲を超えて変更しない。
- 管理者2FAの初期化コマンド `admin:2fa-reset` と、信頼済み端末の失効処理を維持する。
  初期化は対象ID・理由・対話確認・監査記録を伴うCLI操作に限定し、通常ログインから2FAを
  回避できるWeb機能や共通パスワードを追加しない。
- 2FA初期化では旧TOTP・旧リカバリーコード・全信頼済み端末を無効化し、再登録を必須とする。
  認証世代による既存管理者セッション・認証途中のセッション・管理者引継ぎセッションの
  失効確認を削除しない。古い認証は次のアクセスで拒否し、他スタッフの独立したログインに波及させない。
- 信頼済み端末でもID・パスワード認証は必須。有効期限は30日固定、DBには秘密トークンの
  ハッシュだけを保存する。パスワード変更時は対象者の全端末を失効させる処理を維持する。
- 回帰確認には通常TOTP、信頼済み端末、期限切れ、パスワード変更、2FA初期化後の再登録・
  セッション失効、スタッフの通常ログインと管理者引継ぎを含める。
- 実装・復旧手順は `docs/admin_two_factor_local.md`、信頼済み端末・SQLは
  `docs/admin_two_factor_trusted_devices.md` を参照する。ローカル有効化・コミット・pushと
  本番サーバーへの反映は別の操作であり、本番反映は別途明示承認を得る。

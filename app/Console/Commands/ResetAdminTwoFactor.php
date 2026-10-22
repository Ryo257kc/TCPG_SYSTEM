<?php

namespace App\Console\Commands;

use App\Services\Admin\V2\AdminTwoFactorCredentialStore;
use App\Services\Admin\V2\AdminTwoFactorService;
use App\Services\Admin\V2\LoginV2Service;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ResetAdminTwoFactor extends Command
{
    protected $signature = 'admin:2fa-reset
        {staff_id : 初期化対象の管理者スタッフID}
        {--reason= : 本人確認済みの復旧理由（必須）}
        {--dry-run : 対象確認のみ。初期化しない}';

    protected $description = '指定した管理者1名の2FAを初期化し、既存の認証を失効して再登録を必須にする';

    public function handle(LoginV2Service $login, AdminTwoFactorCredentialStore $store, AdminTwoFactorService $twoFactor): int
    {
        if (PHP_SAPI !== 'cli' || !app()->runningInConsole()) {
            $this->error('サーバー管理者のCLIからのみ実行できます。');

            return self::FAILURE;
        }
        if (!$twoFactor->enabled() || !config('admin_two_factor.database_approved')) {
            $this->error('2FAまたは承認済みの保存先が有効になっていません。設定を自動変更せず中止します。');

            return self::FAILURE;
        }
        $staffId = mb_convert_kana(trim((string) $this->argument('staff_id')), 'as', 'UTF-8');
        $reason = trim((string) $this->option('reason'));
        if ($staffId === '' || mb_strlen($staffId) > 50 || preg_match('/[\x00-\x1f\x7f]/u', $staffId)
            || $reason === '' || mb_strlen($reason) > 200 || preg_match('/[\x00-\x1f\x7f]/u', $reason)) {
            $this->error('対象IDと、改行を含まない200文字以内の --reason を指定してください。');

            return self::FAILURE;
        }
        $staff = $login->findStaffForLogin($staffId);
        if ($staff === null || !$login->isAdminLogin($staff) || $login->isRetired((string) ($staff->employment ?? ''))) {
            $this->error('対象は有効な管理者ではありません。変更しません。');

            return self::FAILURE;
        }
        $staffId = (string) $staff->staff_id;
        $credential = $store->find($staffId);
        if ($credential === null) {
            $this->info('2FAは未登録です。変更しません。');

            return self::SUCCESS;
        }
        $database = $store->databaseName();
        $this->table(['項目', '対象'], [
            ['接続先DB', $database],
            ['スタッフID', $staffId],
            ['氏名', (string) $staff->staff_name],
            ['登録状態', $credential['secret'] === '' ? '再登録待ち' : '登録済み'],
            ['理由', $reason],
        ]);
        $this->warn('この1名の旧TOTP・旧リカバリーコード・全信頼済み端末・既存の管理者認証を失効し、次回はQR再登録が必要になります。');
        if ($this->option('dry-run')) {
            $this->info('確認のみです。初期化していません。');

            return self::SUCCESS;
        }
        if (!$this->input->isInteractive()) {
            $this->error('非対話実行では初期化できません。対象IDを手入力して確認してください。');

            return self::FAILURE;
        }
        if ((string) $this->ask('本人確認済みの対象スタッフIDを再入力してください') !== $staffId) {
            $this->error('IDが一致しません。変更しません。');

            return self::FAILURE;
        }
        $context = [
            'event_id' => bin2hex(random_bytes(16)),
            'staff_id' => $staffId,
            'database' => $database,
            'reason' => $reason,
            'operator' => getenv('USERNAME') ?: getenv('USER') ?: 'unknown',
            'host' => gethostname(),
        ];
        try {
            // 通知用チャンネルは使わず、サーバー内の専用監査ログにだけ記録する。
            $audit = Log::build(['driver' => 'single', 'path' => storage_path('logs/admin-two-factor-reset.log'), 'level' => 'info']);
            $audit->notice('admin_2fa_reset_requested', $context);
        } catch (Throwable) {
            $this->error('監査ログを記録できないため、変更せず中止します。');

            return self::FAILURE;
        }
        try {
            $reset = $store->reset($staffId, $credential['auth_version']);
        } catch (Throwable) {
            $this->error('初期化処理でエラーが発生しました。接続先と対象の状態を確認してください。');

            return self::FAILURE;
        }
        try {
            $audit->notice($reset ? 'admin_2fa_reset_completed' : 'admin_2fa_reset_cancelled', $context);
        } catch (Throwable) {
            $this->error($reset ? '初期化は完了しましたが、完了監査ログを記録できませんでした。' : '初期化していません。監査ログも確認してください。');

            return self::FAILURE;
        }
        if (!$reset) {
            $this->error('確認中に登録状態が変わったため、変更せず中止しました。');

            return self::FAILURE;
        }
        $this->info('初期化しました。本人がID・パスワードでログインし、新しい認証アプリとリカバリーコードを登録してください。');

        return self::SUCCESS;
    }
}

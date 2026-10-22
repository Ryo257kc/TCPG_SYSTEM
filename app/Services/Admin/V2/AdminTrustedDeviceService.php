<?php

namespace App\Services\Admin\V2;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;

class AdminTrustedDeviceService
{
    public function __construct(
        private readonly AdminTrustedDeviceStore $store,
        private readonly AdminTwoFactorCredentialStore $credentials,
    ) {
    }

    public function available(?Request $request = null): bool
    {
        if (!config('admin_two_factor.enabled') || !config('admin_two_factor.database_approved')
            || !config('admin_two_factor.trusted_devices_approved')) {
            return false;
        }

        return $request === null || $request->isSecure()
            || (app()->environment(['local', 'testing']) && in_array($request->getHost(), ['localhost', '127.0.0.1', '::1'], true));
    }

    public function passwordFingerprint(string $password): string
    {
        return hash_hmac('sha256', 'admin-trusted-password:' . $password, (string) config('app.key'));
    }

    /** ID・パスワードと管理者権限を確認した後だけ呼ぶ。 */
    public function authenticate(Request $request, object $staff, ?array $credential): bool
    {
        if (!$this->available($request) || $credential === null || $credential['secret'] === '') {
            return false;
        }
        $cookie = $request->cookie(config('admin_two_factor.trusted_cookie'));
        if (!is_string($cookie) || !preg_match('/\A([a-f0-9]{32}):([a-f0-9]{64})\z/', $cookie, $parts)) {
            return false;
        }
        $nextToken = bin2hex(random_bytes(32));
        $device = $this->store->rotate($parts[1], (string) $staff->staff_id, function (array $row) use ($staff, $credential, $parts, $nextToken): ?array {
            if (Carbon::parse($row['expires_at'])->lessThanOrEqualTo(now())
                || !hash_equals($row['token_hash'], hash('sha256', $parts[2]))
                || !hash_equals($row['auth_version'], $credential['auth_version'])
                || !hash_equals($row['password_fingerprint'], $this->passwordFingerprint((string) $staff->password))) {
                return null;
            }

            return ['token_hash' => hash('sha256', $nextToken)];
        });
        if ($device === null) {
            Cookie::queue(Cookie::forget(config('admin_two_factor.trusted_cookie'), '/admin'));

            return false;
        }
        $this->queueCookie($request, $parts[1] . ':' . $nextToken, Carbon::parse($device['expires_at']));

        return true;
    }

    /** TOTP成功（初回はリカバリー保存確認も完了）後にだけ発行する。 */
    public function issue(Request $request, object $staff, ?array $credential): void
    {
        if (!$this->available($request) || $credential === null || $credential['secret'] === ''
            || $request->session()->get('admin_2fa_staff_id') !== (string) $staff->staff_id
            || !hash_equals($credential['auth_version'], (string) $request->session()->get('admin_2fa_version', ''))) {
            return;
        }
        $id = bin2hex(random_bytes(16));
        $token = bin2hex(random_bytes(32));
        $expires = now()->addDays((int) config('admin_two_factor.trusted_days'));
        $this->store->create([
            'id' => $id,
            'staff_id' => (string) $staff->staff_id,
            'token_hash' => hash('sha256', $token),
            'auth_version' => $credential['auth_version'],
            'password_fingerprint' => $this->passwordFingerprint((string) $staff->password),
            'expires_at' => $expires,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->queueCookie($request, $id . ':' . $token, $expires);
    }

    /** 既存のパスワード更新と、全端末・認証世代の失効を同じ接続のトランザクションに含める。 */
    public function changePassword(string $staffId, callable $write): mixed
    {
        if (!config('admin_two_factor.database_approved') || !config('admin_two_factor.trusted_devices_approved')) {
            return $write();
        }

        return DB::connection(config('admin_two_factor.connection'))->transaction(function () use ($staffId, $write): mixed {
            $this->credentials->consume($staffId, static fn(array $row): array => ['auth_version' => bin2hex(random_bytes(32))]);
            $this->store->revokeAll($staffId);

            return $write();
        });
    }

    private function queueCookie(Request $request, string $value, Carbon $expires): void
    {
        Cookie::queue(Cookie::make(
            config('admin_two_factor.trusted_cookie'), $value,
            max(1, (int) floor((float) now()->diffInSeconds($expires) / 60)),
            '/admin', null, $request->isSecure(), true, false, 'strict',
        ));
    }
}

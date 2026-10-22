<?php

namespace App\Services\Admin\V2;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use PragmaRX\Google2FA\Google2FA;

class AdminTwoFactorService
{
    public function __construct(
        private readonly AdminTwoFactorCredentialStore $store,
        private readonly Google2FA $totp,
    ) {
    }

    public function enabled(): bool
    {
        return (bool) config('admin_two_factor.enabled');
    }

    public function credential(string $staffId): ?array
    {
        return $this->store->find($staffId);
    }

    public function newSecret(): string
    {
        return $this->totp->generateSecretKey();
    }

    public function qrSvg(string $staffId, string $secret): string
    {
        $uri = $this->totp->getQRCodeUrl(config('admin_two_factor.issuer'), $staffId, $secret);
        $writer = new Writer(new ImageRenderer(new RendererStyle(256), new SvgImageBackEnd()));

        return $writer->writeString($uri);
    }

    public function enroll(string $staffId, string $secret, string $code, ?string $expectedVersion = null): ?array
    {
        $step = $this->verifyStep($secret, $code, 0);
        if ($step === null) {
            return null;
        }

        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $codes[] = bin2hex(random_bytes(5)) . '-' . bin2hex(random_bytes(5));
        }
        $credential = [
            'secret' => Crypt::encryptString($secret),
            'recovery_code_hashes' => json_encode(array_map($this->recoveryHash(...), $codes), JSON_THROW_ON_ERROR),
            'confirmed_at' => now(),
            'last_used_step' => $step,
            'auth_version' => bin2hex(random_bytes(32)),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return $this->store->enroll($staffId, $credential, $expectedVersion)
            ? ['credential' => $credential, 'codes' => $codes]
            : null;
    }

    public function verify(string $staffId, string $code, bool $recovery, ?string $expectedVersion = null): ?array
    {
        return $this->store->consume($staffId, function (array $credential) use ($code, $recovery, $expectedVersion): ?array {
            if ($credential['secret'] === '' || $expectedVersion === null
                || !hash_equals($credential['auth_version'], $expectedVersion)) {
                return null;
            }
            if ($recovery) {
                if (!preg_match('/\A[0-9a-f]{10}-[0-9a-f]{10}\z/', $code)) {
                    return null;
                }
                $hashes = json_decode($credential['recovery_code_hashes'], true, 512, JSON_THROW_ON_ERROR);
                foreach ($hashes as $index => $hash) {
                    if (hash_equals($hash, $this->recoveryHash($code))) {
                        unset($hashes[$index]);

                        return ['recovery_code_hashes' => json_encode(array_values($hashes), JSON_THROW_ON_ERROR)];
                    }
                }

                return null;
            }

            $step = $this->verifyStep(Crypt::decryptString($credential['secret']), $code, (int) $credential['last_used_step']);

            return $step === null ? null : ['last_used_step' => $step];
        });
    }

    public function verified(Request $request): bool
    {
        $id = (string) $request->session()->get('admin_staff_id', '');
        if (!$request->session()->get('admin_logged_in') || $id === ''
            || $request->session()->get('admin_2fa_staff_id') !== $id) {
            return false;
        }
        $version = (string) $request->session()->get('admin_2fa_version', '');
        if ($version === '') {
            return false;
        }
        $credential = $this->credential($id);

        return $credential !== null && $credential['secret'] !== ''
            && hash_equals($credential['auth_version'], $version);
    }

    public function complete(Request $request, object $staff, array $credential): void
    {
        $request->session()->forget(['admin_2fa_pending', 'admin_2fa_setup_secret', 'admin_2fa_recovery_codes', 'admin_2fa_trust_requested']);
        $request->session()->regenerate();
        $id = (string) $staff->staff_id;
        $request->session()->put([
            'admin_logged_in' => true,
            'admin_staff_id' => $id,
            'admin_staff_name' => (string) $staff->staff_name,
            'admin_2fa_staff_id' => $id,
            'admin_2fa_version' => $credential['auth_version'],
        ]);
    }

    public function clear(Request $request): void
    {
        // 管理者の認証情報を引き継いだ同一IDのスタッフセッションも失効させる。
        $adminId = $request->session()->get('admin_2fa_staff_id');
        if (is_string($adminId) && $adminId !== '' && $request->session()->get('staff_id') === $adminId) {
            $request->session()->forget('staff_id');
        }
        $request->session()->forget([
            'admin_logged_in', 'admin_staff_id', 'admin_staff_name',
            'admin_2fa_staff_id', 'admin_2fa_version', 'admin_2fa_pending',
            'admin_2fa_setup_secret', 'admin_2fa_recovery_codes',
            'admin_2fa_trust_requested',
        ]);
    }

    private function verifyStep(string $secret, string $code, int $lastStep): ?int
    {
        if (!preg_match('/\A[0-9]{6}\z/', $code)) {
            return null;
        }
        $step = $this->totp->verifyKeyNewer($secret, $code, $lastStep, 1, intdiv(now()->timestamp, 30));

        return $step === false ? null : (int) $step;
    }

    private function recoveryHash(string $code): string
    {
        // 80bitのランダムコード。平文は保存せず、アプリ鍵付きハッシュで照合する。
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }
}

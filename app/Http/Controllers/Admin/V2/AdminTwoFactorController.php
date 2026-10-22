<?php

namespace App\Http\Controllers\Admin\V2;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\AdminTwoFactorService;
use App\Services\Admin\V2\LoginV2Service;
use App\Services\Admin\V2\AdminTrustedDeviceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;

class AdminTwoFactorController extends Controller
{
    public function __construct(
        private readonly AdminTwoFactorService $twoFactor,
        private readonly LoginV2Service $loginService,
        private readonly AdminTrustedDeviceService $trustedDevices,
    ) {
    }

    public function challenge(Request $request): Response|RedirectResponse
    {
        $staff = $this->pendingStaff($request);
        if ($staff === null) {
            return redirect()->route('admin.login');
        }
        $credential = $this->twoFactor->credential((string) $staff->staff_id);
        $enrolling = $credential === null || $credential['secret'] === '';
        $secret = '';
        $qr = '';
        if ($enrolling) {
            if (!$request->session()->has('admin_2fa_setup_secret')) {
                $request->session()->put('admin_2fa_setup_secret', Crypt::encryptString($this->twoFactor->newSecret()));
            }
            $secret = Crypt::decryptString($request->session()->get('admin_2fa_setup_secret'));
            $qr = $this->twoFactor->qrSvg((string) $staff->staff_id, $secret);
        }

        $canTrust = $this->trustedDevices->available($request);

        return $this->page('admin_v2.login.two_factor', compact('enrolling', 'secret', 'qr', 'canTrust'));
    }

    public function enroll(Request $request): RedirectResponse
    {
        $staff = $this->pendingStaff($request);
        if ($staff === null) {
            return redirect()->route('admin.login');
        }
        $this->limit($request, (string) $staff->staff_id);
        $encryptedSecret = $request->session()->get('admin_2fa_setup_secret');
        if (!is_string($encryptedSecret)) {
            return redirect()->route('admin.two_factor.challenge');
        }
        $result = $this->twoFactor->enroll(
            (string) $staff->staff_id,
            Crypt::decryptString($encryptedSecret),
            $this->inputString($request, 'code'),
            $request->session()->get('admin_2fa_pending.auth_version'),
        );
        if ($result === null) {
            return redirect()->route('admin.two_factor.challenge')->withErrors(['code' => '認証できませんでした。最新の6桁コードを確認してください。']);
        }
        $this->twoFactor->complete($request, $staff, $result['credential']);
        $request->session()->put('admin_2fa_recovery_codes', Crypt::encryptString(json_encode($result['codes'], JSON_THROW_ON_ERROR)));
        if ($request->input('trust_device') === '1' && $this->trustedDevices->available($request)) {
            $request->session()->put('admin_2fa_trust_requested', $this->trustedDevices->passwordFingerprint((string) $staff->password));
        }

        return redirect()->route('admin.two_factor.recovery');
    }

    public function verify(Request $request): RedirectResponse
    {
        $staff = $this->pendingStaff($request);
        if ($staff === null) {
            return redirect()->route('admin.login');
        }
        $this->limit($request, (string) $staff->staff_id);
        $recoveryCode = strtolower($this->inputString($request, 'recovery_code'));
        $code = $this->inputString($request, 'code');
        if (($recoveryCode === '') === ($code === '')) {
            return redirect()->route('admin.two_factor.challenge')->withErrors(['code' => '認証コードかリカバリーコードのどちらか一方を入力してください。']);
        }
        $credential = $this->twoFactor->verify(
            (string) $staff->staff_id,
            $recoveryCode !== '' ? $recoveryCode : $code,
            $recoveryCode !== '',
            $request->session()->get('admin_2fa_pending.auth_version'),
        );
        if ($credential === null) {
            return redirect()->route('admin.two_factor.challenge')->withErrors(['code' => '認証できませんでした。コードが正しいか、使用済みでないか確認してください。']);
        }
        $this->twoFactor->complete($request, $staff, $credential);
        if ($recoveryCode === '' && $request->input('trust_device') === '1') {
            $this->trustedDevices->issue($request, $staff, $credential);
        }

        return redirect()->route('admin.dashboard');
    }

    public function recovery(Request $request): Response|RedirectResponse
    {
        abort_unless($this->twoFactor->enabled(), 404);
        if (!$this->twoFactor->verified($request)) {
            return redirect()->route('admin.login');
        }
        $encrypted = $request->session()->get('admin_2fa_recovery_codes');
        if (!is_string($encrypted)) {
            return redirect()->route('admin.dashboard');
        }
        $codes = json_decode(Crypt::decryptString($encrypted), true, 512, JSON_THROW_ON_ERROR);

        return $this->page('admin_v2.login.recovery_codes', compact('codes'));
    }

    public function continueAfterRecovery(Request $request): RedirectResponse
    {
        abort_unless($this->twoFactor->enabled(), 404);
        if (!$this->twoFactor->verified($request)) {
            return redirect()->route('admin.login');
        }
        if ($request->input('saved') !== '1') {
            return redirect()->route('admin.two_factor.recovery')->withErrors(['saved' => 'リカバリーコードを保存してから進んでください。']);
        }
        if ($request->session()->has('admin_2fa_trust_requested')) {
            $staff = $this->loginService->findStaffForLogin((string) $request->session()->get('admin_staff_id'));
            if ($staff !== null && $this->loginService->isAdminLogin($staff)
                && !$this->loginService->isRetired((string) $staff->employment)
                && hash_equals((string) $request->session()->get('admin_2fa_trust_requested'), $this->trustedDevices->passwordFingerprint((string) $staff->password))) {
                $this->trustedDevices->issue($request, $staff, $this->twoFactor->credential((string) $staff->staff_id));
            }
        }
        $request->session()->forget(['admin_2fa_recovery_codes', 'admin_2fa_trust_requested']);

        return redirect()->route('admin.dashboard');
    }

    public function cancel(Request $request): RedirectResponse
    {
        abort_unless($this->twoFactor->enabled(), 404);
        $this->twoFactor->clear($request);
        $request->session()->regenerate();

        return redirect()->route('admin.login');
    }

    private function pendingStaff(Request $request): ?object
    {
        abort_unless($this->twoFactor->enabled(), 404);
        $pending = $request->session()->get('admin_2fa_pending');
        if (!is_array($pending) || !is_string($pending['staff_id'] ?? null)
            || (int) ($pending['expires_at'] ?? 0) <= now()->timestamp) {
            $this->twoFactor->clear($request);

            return null;
        }
        $staff = $this->loginService->findStaffForLogin($pending['staff_id']);
        if ($staff === null || !$this->loginService->isAdminLogin($staff)
            || $this->loginService->isRetired((string) ($staff->employment ?? ''))) {
            $this->twoFactor->clear($request);

            return null;
        }

        $credential = $this->twoFactor->credential((string) $staff->staff_id);
        $version = $pending['auth_version'] ?? null;
        if (!is_string($pending['password_fingerprint'] ?? null)
            || !hash_equals($pending['password_fingerprint'], $this->trustedDevices->passwordFingerprint((string) $staff->password))
            || !array_key_exists('auth_version', $pending)
            || ($credential === null ? $version !== null
                : !is_string($version) || !hash_equals($credential['auth_version'], $version))) {
            $this->twoFactor->clear($request);

            return null;
        }

        return $staff;
    }

    private function inputString(Request $request, string $key): string
    {
        $value = $request->input($key, '');

        return is_string($value) ? trim($value) : '';
    }

    private function page(string $view, array $data): Response
    {
        return response()->view($view, $data, 200, [
            'Cache-Control' => 'no-store, private',
            'Pragma' => 'no-cache',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }

    private function limit(Request $request, string $staffId): void
    {
        $keys = [
            'admin-2fa:account:' . hash('sha256', $staffId) => (int) config('admin_two_factor.max_attempts'),
            'admin-2fa:ip:' . hash('sha256', (string) $request->ip()) => 30,
        ];
        foreach ($keys as $key => $maximum) {
            Cache::lock($key . ':lock', 5)->block(2, function () use ($key, $maximum): void {
                abort_if(RateLimiter::tooManyAttempts($key, $maximum), 429, '試行回数が上限に達しました。1分ほど待ってからお試しください。');
                RateLimiter::hit($key, (int) config('admin_two_factor.decay_seconds'));
            });
        }
    }
}

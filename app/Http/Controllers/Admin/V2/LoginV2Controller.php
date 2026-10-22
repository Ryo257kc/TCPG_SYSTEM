<?php

namespace App\Http\Controllers\Admin\V2;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\LoginV2Service;
use App\Services\Admin\V2\AdminTwoFactorService;
use App\Services\Admin\V2\AdminTrustedDeviceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginV2Controller extends Controller
{
    public function __construct(
        private readonly LoginV2Service $loginService,
        private readonly AdminTwoFactorService $twoFactor,
        private readonly AdminTrustedDeviceService $trustedDevices,
    )
    {
    }

    public function show(): View|RedirectResponse
    {
        if (session('admin_logged_in')) {
            if (!$this->twoFactor->enabled() || $this->twoFactor->verified(request())) {
                return redirect()->route('admin.dashboard');
            }
            $this->twoFactor->clear(request());
        }

        return view('admin_v2.login.index');
    }

public function login(Request $request): RedirectResponse
{
    $this->twoFactor->clear($request);
    $credentials = $request->validate([
        'staff_id' => ['required', 'string'],
        'password' => ['required', 'string'],
    ], [
        'staff_id.required' => 'IDを入力してください。',
        'password.required' => 'パスワードを入力してください。',
    ]);

    $staff = $this->loginService->findStaffForLogin((string) $credentials['staff_id']);
    if (!$staff) {
        return back()->withInput($request->except('password'))->withErrors(['staff_id' => 'ログインできません']);
    }

    if ($this->loginService->isRetired((string) ($staff->employment ?? ''))) {
        return back()->withInput($request->except('password'))->withErrors(['staff_id' => 'ログインできません']);
    }

    if (!$this->loginService->isAdminLogin($staff)) {
        return back()->withInput($request->except('password'))->withErrors(['staff_id' => 'ログインできません']);
    }

    if (!$this->loginService->verifyPassword($staff, (string) $credentials['password'])) {
        return back()->withInput($request->except('password'))->withErrors(['staff_id' => 'ログインできません']);
    }

    if ($this->twoFactor->enabled()) {
        $credential = $this->twoFactor->credential((string) $staff->staff_id);
        if ($this->trustedDevices->authenticate($request, $staff, $credential)) {
            $this->twoFactor->complete($request, $staff, $credential);

            return redirect()->route('admin.dashboard');
        }
        $request->session()->regenerate();
        $request->session()->put('admin_2fa_pending', [
            'staff_id' => (string) $staff->staff_id,
            'expires_at' => now()->timestamp + (int) config('admin_two_factor.pending_seconds'),
            'auth_version' => $credential['auth_version'] ?? null,
            'password_fingerprint' => $this->trustedDevices->passwordFingerprint((string) $staff->password),
        ]);

        return redirect()->route('admin.two_factor.challenge');
    }

    $request->session()->regenerate();
    $request->session()->put('admin_logged_in', true);
    $request->session()->put('admin_staff_id', (string) ($staff->staff_id ?? ''));
    $request->session()->put('admin_staff_name', (string) ($staff->staff_name ?? ''));

    return redirect()->route('admin.dashboard');
}

    public function logout(Request $request): RedirectResponse
    {
        $this->twoFactor->clear($request);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}

<?php

namespace App\Http\Controllers\Admin\V2;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\LoginV2Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginV2Controller extends Controller
{
    public function __construct(private readonly LoginV2Service $loginService)
    {
    }

    public function show(): View|RedirectResponse
    {
        if (session('admin_logged_in')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin_v2.login.index');
    }

public function login(Request $request): RedirectResponse
{
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

    $request->session()->regenerate();
    $request->session()->put('admin_logged_in', true);
    $request->session()->put('admin_staff_id', (string) ($staff->staff_id ?? ''));
    $request->session()->put('admin_staff_name', (string) ($staff->staff_name ?? ''));

    return redirect()->route('admin.dashboard');
}

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['admin_logged_in', 'admin_staff_id', 'admin_staff_name']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}

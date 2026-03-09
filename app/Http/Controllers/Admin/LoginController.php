<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (session('admin_logged_in')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login.index');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'staff_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $staffId = trim($credentials['staff_id']);
        $password = (string) $credentials['password'];

        $staff = DB::connection('sqlsrv')
            ->table('dbo.m_staffs')
            ->whereRaw('LTRIM(RTRIM(staff_code)) = ?', [$staffId])
            ->where('is_store_manager', 1)
            ->first();

        if (!$staff || (string) ($staff->login_password ?? '') !== $password) {
            return back()
                ->withInput($request->except('password'))
                ->withErrors([
                    'staff_id' => 'ログインIDまたはパスワードが正しくありません。',
                ]);
        }

        $request->session()->regenerate();
        $request->session()->put('admin_logged_in', true);
        $request->session()->put('admin_staff_id', $staffId);
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

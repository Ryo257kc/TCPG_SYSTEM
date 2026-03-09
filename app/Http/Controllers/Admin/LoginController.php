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

    public function dashboard(Request $request): View
    {
        $menuGroups = [
            '売上' => [
                ['key' => 'daily-list', 'label' => '日報一覧'],
                ['key' => 'sales-finalize', 'label' => '売上確定'],
                ['key' => 'visit-daily', 'label' => '往診日報'],
                ['key' => 'visit-sales', 'label' => '往診売上'],
            ],
            '給与・勤怠' => [
                ['key' => 'salary-detail', 'label' => '給与詳細'],
                ['key' => 'bonus-detail', 'label' => '賞与詳細'],
                ['key' => 'attendance-manage', 'label' => '勤怠管理', 'route_name' => 'admin.attendance.index'],
            ],
            'マスタ' => [
                ['key' => 'master-company', 'label' => '会社マスタ', 'route_name' => 'admin.master.company'],
                ['key' => 'master-staff', 'label' => 'スタッフマスタ', 'route_name' => 'admin.master.staff'],
                ['key' => 'master-store', 'label' => '店舗マスタ', 'route_name' => 'admin.master.store'],
                ['key' => 'master-allowance', 'label' => '手当項目設定', 'route_name' => 'admin.master.allowance'],
            ],
        ];

        $pages = [
            'home' => ['title' => '管理メニュー', 'description' => '左メニューから機能を選択してください。'],
            'daily-list' => ['title' => '日報一覧', 'description' => '準備中です。'],
            'sales-finalize' => ['title' => '売上確定', 'description' => '準備中です。'],
            'visit-daily' => ['title' => '往診日報', 'description' => '準備中です。'],
            'visit-sales' => ['title' => '往診売上', 'description' => '準備中です。'],
            'salary-detail' => ['title' => '給与詳細', 'description' => '給与一覧ページへ遷移します。'],
            'bonus-detail' => ['title' => '賞与詳細', 'description' => '準備中です。'],
            'attendance-manage' => ['title' => '勤怠管理', 'description' => '準備中です。'],
            'master-company' => ['title' => '会社マスタ', 'description' => '会社マスタページへ遷移します。'],
            'master-staff' => ['title' => 'スタッフマスタ', 'description' => 'スタッフマスタページへ遷移します。'],
            'master-store' => ['title' => '店舗マスタ', 'description' => '店舗マスタページへ遷移します。'],
            'master-allowance' => ['title' => '手当項目設定', 'description' => '手当項目設定ページへ遷移します。'],
        ];

        $selectedPage = (string) $request->query('page', 'home');
        if (!array_key_exists($selectedPage, $pages)) {
            $selectedPage = 'home';
        }

        return view('admin.dashboard.index', [
            'menuGroups' => $menuGroups,
            'selectedPage' => $selectedPage,
            'pageData' => $pages[$selectedPage],
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['admin_logged_in', 'admin_staff_id', 'admin_staff_name']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}

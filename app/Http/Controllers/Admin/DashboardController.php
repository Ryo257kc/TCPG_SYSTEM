<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
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
            'salary-detail' => ['title' => '給与詳細', 'description' => '給与一覧ページへ移動します。'],
            'bonus-detail' => ['title' => '賞与詳細', 'description' => '準備中です。'],
            'attendance-manage' => ['title' => '勤怠管理', 'description' => '準備中です。'],
            'master-company' => ['title' => '会社マスタ', 'description' => '会社マスタページへ移動します。'],
            'master-staff' => ['title' => 'スタッフマスタ', 'description' => 'スタッフマスタページへ移動します。'],
            'master-store' => ['title' => '店舗マスタ', 'description' => '店舗マスタページへ移動します。'],
            'master-allowance' => ['title' => '手当項目設定', 'description' => '手当項目設定ページへ移動します。'],
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
}


<?php

namespace App\Services\Admin\V2;

class DashboardV2MenuService
{
    /**
     * @return array<string, array<int, array{key:string,label:string,url:string}>>
     */
    public function menuGroups(): array
    {
        return [
            '勤怠・給与' => [
                ['key' => 'attendance-manage', 'label' => '勤怠管理', 'url' => '/admin/attendance'],
                ['key' => 'salary-detail', 'label' => '給与計算', 'url' => '/admin/payroll'],
            ],
            'マスタ' => [
                ['key' => 'master-company', 'label' => '会社マスタ', 'url' => '/admin/master/company'],
                ['key' => 'master-staff', 'label' => 'スタッフマスタ', 'url' => '/admin/master/staff'],
                ['key' => 'master-store', 'label' => '店舗マスタ', 'url' => '/admin/master/store'],
                ['key' => 'master-allowance', 'label' => '手当設定', 'url' => '/admin/master/allowance'],
                ['key' => 'master-calendar', 'label' => 'カレンダー', 'url' => '/admin/master/calendar'],
            ],
        ];
    }

    /**
     * @return array<string, array{title:string,description:string}>
     */
    public function pages(): array
    {
        return [
            'home' => ['title' => '管理者メニュー', 'description' => '左メニューから機能を選択してください。'],
            'attendance-manage' => ['title' => '勤怠管理', 'description' => '勤怠管理ページへ移動します。'],
            'salary-detail' => ['title' => '給与計算', 'description' => '給与計算ページへ移動します。'],
            'master-company' => ['title' => '会社マスタ', 'description' => '会社マスタページへ移動します。'],
            'master-staff' => ['title' => 'スタッフマスタ', 'description' => 'スタッフマスタページへ移動します。'],
            'master-store' => ['title' => '店舗マスタ', 'description' => '店舗マスタページへ移動します。'],
            'master-allowance' => ['title' => '手当設定', 'description' => '手当設定ページへ移動します。'],
            'master-calendar' => ['title' => 'カレンダー', 'description' => 'カレンダーマスタページへ移動します。'],
        ];
    }
}

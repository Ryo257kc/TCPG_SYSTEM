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
                ['key' => 'bonus-detail', 'label' => '賞与計算', 'url' => '/admin/bonus'],
                ['key' => 'paid-leave-manage', 'label' => '有休管理', 'url' => '/admin/paid-leave'],
                ['key' => 'year-end-adjustments', 'label' => '年末調整管理', 'url' => '/admin/year-end-adjustments'],
            ],
            '売上' => [
                ['key' => 'sales-preview', 'label' => '売上', 'url' => '/admin/sales'],
                ['key' => 'accounts-receivable', 'label' => '未収入金', 'url' => '/admin'],
                ['key' => 'high-cost-medical', 'label' => '高額療養費', 'url' => '/admin'],
                ['key' => 'home-visit-counter-list', 'label' => '往診窓口一覧', 'url' => '/admin'],
                ['key' => 'return-processing', 'label' => '返戻処理', 'url' => '/admin'],
            ],
            '請求・仕訳' => [
                ['key' => 'journal-entries', 'label' => '仕訳帳', 'url' => '/admin'],
                ['key' => 'petty-cash-list', 'label' => '小口一覧', 'url' => '/admin'],
                ['key' => 'billing-list', 'label' => '請求一覧', 'url' => '/admin/work/billing-list'],
                ['key' => 'loan-repayment', 'label' => '借入返済', 'url' => '/admin'],
            ],
            '帳票' => [
                ['key' => 'report-center', 'label' => '帳票一覧', 'url' => '/admin/reports'],
            ],
            'マスタ' => [
                ['key' => 'master-company', 'label' => '会社マスタ', 'url' => '/admin/master/company'],
                ['key' => 'master-staff', 'label' => 'スタッフマスタ', 'url' => '/admin/master/staff'],
                ['key' => 'master-store', 'label' => '店舗マスタ', 'url' => '/admin/master/store'],
                ['key' => 'master-allowance', 'label' => '手当設定', 'url' => '/admin/master/allowance'],
                ['key' => 'master-calendar', 'label' => 'カレンダー', 'url' => '/admin/master/calendar'],
            ],
            '事務所MENU' => [
                ['key' => 'report-center', 'label' => '勤怠', 'url' => '/admin/reports'],
                ['key' => 'report-center', 'label' => 'レセ関連', 'url' => '/admin/reports'],
                ['key' => 'report-center', 'label' => '日報', 'url' => '/admin/reports'],
                ['key' => 'report-center', 'label' => '事務業務', 'url' => '/admin/reports'],
                ['key' => 'report-center', 'label' => '売上関連', 'url' => '/admin/reports'],
            ],
            '管理者MENU' => [
                ['key' => 'report-center', 'label' => 'シフト変更', 'url' => '/admin/reports'],
                ['key' => 'report-center', 'label' => '勤怠管理', 'url' => '/admin/reports'],
                ['key' => 'report-center', 'label' => '打刻一覧', 'url' => '/admin/reports'],
                ['key' => 'report-center', 'label' => '申請有休', 'url' => '/admin/reports'],
                ['key' => 'report-center', 'label' => '各種売上', 'url' => '/admin/reports'],
                ['key' => 'report-center', 'label' => '書類一覧', 'url' => '/admin/reports'],

            ],
        ];
    }

    /**
     * @return array<string, array{title:string,description:string}>
     */
    public function pages(): array
    {
        return [
            'home' => ['title' => '管理メニュー', 'description' => '各メニューからページを選択してください。'],
            'attendance-manage' => ['title' => '勤怠管理', 'description' => '勤怠管理ページへ移動します。'],
            'salary-detail' => ['title' => '給与計算', 'description' => '給与計算ページへ移動します。'],
            'bonus-detail' => ['title' => '賞与計算', 'description' => '賞与計算ページへ移動します。'],
            'paid-leave-manage' => ['title' => '有休管理', 'description' => '有休の使用・残数確認ページへ移動します。'],
            'year-end-adjustments' => ['title' => '年末調整管理', 'description' => '年末調整申請の受付・提出状況を確認します。'],
            'sales-preview' => ['title' => '売上', 'description' => '売上ページはこちらから移動してください。'],
            'accounts-receivable' => ['title' => '未収入金', 'description' => '未収入金ページはこちらから移動してください。'],
            'high-cost-medical' => ['title' => '高額療養費', 'description' => '高額療養費ページはこちらから移動してください。'],
            'report-center' => ['title' => '帳票一覧', 'description' => '帳票一覧ページへ移動します。'],
            'master-company' => ['title' => '会社マスタ', 'description' => '会社マスタページへ移動します。'],
            'master-staff' => ['title' => 'スタッフマスタ', 'description' => 'スタッフマスタページへ移動します。'],
            'master-store' => ['title' => '店舗マスタ', 'description' => '店舗マスタページへ移動します。'],
            'master-allowance' => ['title' => '手当設定', 'description' => '手当設定ページへ移動します。'],
            'master-calendar' => ['title' => 'カレンダー', 'description' => 'カレンダーページへ移動します。'],
        ];
    }
}

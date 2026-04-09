<!DOCTYPE html>
<html lang="ja">
<head>
    @php
        $menuGroups = [
            '勤怠・給与' => [
                ['key' => 'attendance-manage', 'label' => '勤怠管理', 'url' => '/admin/attendance'],
                ['key' => 'salary-detail', 'label' => '給与計算', 'url' => '/admin/payroll'],
                ['key' => 'bonus-detail', 'label' => '賞与計算', 'url' => '/admin/bonus'],
                ['key' => 'paid-leave-manage', 'label' => '有休管理', 'url' => '/admin/paid-leave'],
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
                ['key' => 'billing-list', 'label' => '請求一覧', 'url' => '/admin'],
                ['key' => 'loan-repayment', 'label' => '借入返済', 'url' => '/admin'],
            ],
            '帳票' => [
                ['key' => 'report-center', 'label' => '給与帳票', 'url' => '/admin/reports'],
                ['key' => 'manager-documents', 'label' => '入社書類', 'url' => '/staff/attendance/management'],
            ],
            'マスタ' => [
                ['key' => 'master-company', 'label' => '会社マスタ', 'url' => '/admin/master/company'],
                ['key' => 'master-staff', 'label' => 'スタッフマスタ', 'url' => '/admin/master/staff'],
                ['key' => 'master-store', 'label' => '店舗マスタ', 'url' => '/admin/master/store'],
                ['key' => 'master-allowance', 'label' => '手当設定', 'url' => '/admin/master/allowance'],
                ['key' => 'master-calendar', 'label' => 'カレンダー', 'url' => '/admin/master/calendar'],
            ],
            '事務所MENU' => [
                ['key' => 'office-attendance', 'label' => '事務所勤怠', 'url' => '/staff/office/attendance'],
                ['key' => 'office-receipt', 'label' => 'レセ関連', 'url' => '/staff/office/receipt'],
                ['key' => 'office-daily-report', 'label' => '店舗日報', 'url' => '/admin/reports'],
                ['key' => 'office-backoffice', 'label' => '事務業務', 'url' => '/admin/reports'],
                ['key' => 'office-sales', 'label' => '売上関連', 'url' => '/admin/reports'],
            ],
            '管理者MENU' => [
                ['key' => 'manager-shift-change', 'label' => 'シフト変更', 'url' => '/staff/attendance/management'],
                ['key' => 'manager-attendance-manage', 'label' => '勤怠管理', 'url' => '/staff/attendance/management'],
                ['key' => 'manager-punch-list', 'label' => '打刻一覧', 'url' => '/staff/attendance/punch-list'],
                ['key' => 'manager-paid-leave', 'label' => '申請有休', 'url' => '/staff/attendance/management'],
                ['key' => 'manager-sales', 'label' => '各種売上', 'url' => '/staff/attendance/management'],
            ],
        ];

        $pages = [
            'home' => ['title' => '管理メニュー', 'description' => '各メニューからページを選択してください。'],
            'attendance-manage' => ['title' => '勤怠管理', 'description' => '勤怠管理ページへ移動します。'],
            'salary-detail' => ['title' => '給与計算', 'description' => '給与計算ページへ移動します。'],
            'bonus-detail' => ['title' => '賞与計算', 'description' => '賞与計算ページへ移動します。'],
            'paid-leave-manage' => ['title' => '有休管理', 'description' => '有休の使用・残数確認ページへ移動します。'],
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

        $selectedPage = array_key_exists($requestedPage ?? 'home', $pages) ? $requestedPage : 'home';
        $pageData = $pages[$selectedPage];
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM ダッシュボード</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/dashboard.css') }}">
</head>
<body>
    @include('admin_v2.shared.global_nav')

    <div class="page-shell">
        <section class="hero">
            <div class="hero-card">
                <span class="hero-kicker">TOP PAGE</span>
                <h1>{{ $pageData['title'] }}</h1>
                <p>{{ $pageData['description'] }}</p>
            </div>

            <aside class="session-card">
                <div class="session-label">SESSION</div>
                <div class="session-name">{{ session('admin_staff_name') ?: '管理者' }}</div>
                <div class="session-meta">
                    <div class="session-row"><strong>ログインID</strong>{{ session('admin_staff_id') ?: '-' }}</div>
                    <div class="session-row"><strong>選択中</strong>{{ $pageData['title'] }}</div>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="logout-btn">ログアウト</button>
                </form>
            </aside>
        </section>

        <section class="menu-grid">
            @foreach ($menuGroups as $groupName => $items)
                <section class="menu-card">
                    <h2 class="menu-title">{{ $groupName }}</h2>
                    <div class="menu-list">
                        @foreach ($items as $item)
                            @continue(!empty($item['hidden']))
                            @php
                                $itemPage = $pages[$item['key']] ?? null;
                                $itemHref = $item['url'] === '/admin'
                                    ? url($item['url']) . '?page=' . $item['key']
                                    : url($item['url']);
                            @endphp
                            <a class="menu-link {{ $selectedPage === $item['key'] ? 'active' : '' }}"
                               href="{{ $itemHref }}">
                                <span class="menu-link-title">{{ $item['label'] }}</span>
                                @if (!empty($itemPage['description']))
                                    <span class="menu-link-sub">{{ $itemPage['description'] }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </section>
    </div>
</body>
</html>

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
    ['key' => 'year-end-adjustments', 'label' => '年末調整管理', 'url' => route('admin.work.year_end_adjustments')],
    ],
    '売上' => [
    ['key' => 'sales-preview', 'label' => '店舗売上', 'url' => '/admin/sales'],
    ['key' => 'accounts-receivable', 'label' => '未収入金', 'url' => '/staff/office/sales/uncollected'],
    ['key' => 'high-cost-medical', 'label' => '高額療養費', 'url' => '/staff/office/receipt/high_medical'],
    ['key' => 'home-visit-counter-list', 'label' => '往診窓口一覧', 'url' => '/staff/office/receipt/home-visit-counter'],
    ['key' => 'return-processing', 'label' => '返戻処理', 'url' => '/admin'],
    ],
    '請求・仕訳' => [
    ['key' => 'journal-entries', 'label' => '仕訳帳', 'url' => route('admin.work.journal_entries')],
    ['key' => 'petty-cash-list', 'label' => '現金出納帳', 'url' => 'staff/office/office_menu/cash_book'],
    ['key' => 'billing-list', 'label' => '請求一覧', 'url' => route('admin.work.billing_list')],
    ['key' => 'loan-repayment', 'label' => '借入返済', 'url' => '/admin'],
    ],
    '帳票' => [
    ['key' => 'report-center', 'label' => '給与帳票', 'url' => '/admin/reports'],
    ['key' => 'manager-documents', 'label' => '入社書類', 'url' => route('office.documents')],
    ],
    /*'月次処理' => [
    ['key' => 'rese_unlock', 'label' => 'レセ解除', 'url' => '/admin/work/information'],
    ['key' => 'dairy_unlock', 'label' => '日報解除', 'url' => '/admin/'],
    ['key' => 'cash_unlock', 'label' => '小口現金解除', 'url' => '/admin/'],
    ],*/
    '通知・申請' => [
    ['key' => 'information', 'label' => 'インフォメーション', 'url' => '/admin/work/information'],
    ['key' => 'maintenance', 'label' => 'メンテナンス設定', 'url' => route('admin.work.maintenance')],
    ['key' => 'message', 'label' => '個人メッセージ', 'url' => '/admin/'],
    ['key' => 'profile-requests', 'label' => '個人情報変更申請管理', 'url' => route('admin.work.profile_requests')],
    ['key' => 'onboarding-requests', 'label' => '入社手続き申請管理', 'url' => route('admin.work.onboarding_requests')],
    ],
    'マスタ' => [
    ['key' => 'master-company', 'label' => '会社マスタ', 'url' => '/admin/master/company'],
    ['key' => 'master-staff', 'label' => 'スタッフマスタ', 'url' => '/admin/master/staff'],
    ['key' => 'master-store', 'label' => '店舗マスタ', 'url' => '/admin/master/store'],
    ['key' => 'master-allowance', 'label' => '手当設定', 'url' => '/admin/master/allowance'],
    ['key' => 'master-calendar', 'label' => 'カレンダー', 'url' => '/admin/master/calendar'],
    ['key' => 'master-staff_portal', 'label' => 'スタッフポータル', 'url' => '/staff/admin-login', 'blank' => true],
    ],
    /*
    '事務所' => [
    ['key' => 'office-attendance', 'label' => '事務所勤怠', 'url' => '/staff/office/attendance'],
    ['key' => 'office-receipt', 'label' => 'レセ関連', 'url' => '/staff/office/receipt'],
    ['key' => 'office-daily-report', 'label' => '店舗日報', 'url' => '/admin/reports'],
    ['key' => 'office-backoffice', 'label' => '事務業務', 'url' => '/admin/reports'],
    ['key' => 'office-sales', 'label' => '売上関連', 'url' => '/admin/reports'],
    ],
    '店舗管理' => [
    ['key' => 'manager-shift-change', 'label' => 'シフト変更', 'url' => '/staff/attendance/management'],
    ['key' => 'manager-attendance-manage', 'label' => '勤怠管理', 'url' => '/staff/attendance/management'],
    ['key' => 'manager-punch-list', 'label' => '打刻一覧', 'url' => '/staff/attendance/punch-list'],
    ['key' => 'manager-paid-leave', 'label' => '申請有休', 'url' => '/staff/attendance/management'],
    ['key' => 'manager-sales', 'label' => '各種売上', 'url' => '/staff/attendance/management'],
    ],
    */
    ];

    $pages = [
    'home' => ['title' => '管理メニュー', 'description' => '各メニューからページを選択してください。'],
    'attendance-manage' => ['title' => '勤怠管理', 'description' => '勤怠確認・確定'],
    'salary-detail' => ['title' => '給与計算', 'description' => 'データ作成、帳票確認'],
    'bonus-detail' => ['title' => '賞与計算', 'description' => 'データ作成、帳票確認'],
    'paid-leave-manage' => ['title' => '有休管理', 'description' => '有休の使用・残数確認'],
    'year-end-adjustments' => ['title' => '年末調整管理', 'description' => '年末調整申請の受付・提出状況を確認'],
    'sales-preview' => ['title' => '売上', 'description' => '各店舗別月間売上'],
    'accounts-receivable' => ['title' => '未収入金', 'description' => 'レセ未収入金一覧'],
    'high-cost-medical' => ['title' => '高額療養費', 'description' => '高額療養費の記帳チェック'],
    'home-visit-counter-list' => ['title' => '往診窓口一覧', 'description' => '個人振込入金チェック'],
    'report-center' => ['title' => '給与帳票', 'description' => '給与帳票、申請帳票、年度集計'],
    'manager-documents' => ['title' => '入社書類', 'description' => '入社時必要書類'],
    'master-company' => ['title' => '会社マスタ', 'description' => '会社詳細編集'],
    'master-staff' => ['title' => 'スタッフマスタ', 'description' => 'スタッフ詳細編集'],
    'master-store' => ['title' => '店舗マスタ', 'description' => '店舗詳細編集'],
    'master-allowance' => ['title' => '手当設定', 'description' => '手当設定編集'],
    'master-calendar' => ['title' => 'カレンダー', 'description' => '祝日、会社休日の設定'],
    'master-staff_portal' => ['title' => 'スタッフポータル', 'description' => 'スタッフ専用サイト'],
    'journal-entries' => ['title' => '仕訳帳', 'description' => '会計ソフトデータ'],
    'petty-cash-list' => ['title' => '現金出納帳', 'description' => '小口現金の確認'],
    'return-processing' => ['title' => '返戻処理', 'description' => '不要？未収入金に統合？'],
    'information' => ['title' => 'インフォメーション', 'description' => 'お知らせの公開、非公開'],
    'maintenance' => ['title' => 'メンテナンス設定', 'description' => 'メンテナンス時間帯の設定'],
    'rese_unlock' => ['title' => 'レセ解除', 'description' => '月次：入金確認、往診売上　仕訳削除'],
    'dairy_unlock' => ['title' => '日報解除', 'description' => '月次：日報、店舗売上　仕訳削除'],
    'cash_unlock' => ['title' => '小口現金解除', 'description' => '月次：経理、残高繰越　削除'],
    'billing-list' => ['title' => '請求一覧', 'description' => '請求書の作成'],
    ];

    $selectedPage = array_key_exists($requestedPage ?? 'home', $pages) ? $requestedPage : 'home';
    $pageData = $pages[$selectedPage];
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - ダッシュボード</title>
    <link rel="stylesheet" href="{{ asset('css/admin_v2/dashboard.css') }}">
</head>

<body>
    <main class="container">
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

            @if (!empty($returnedAttendanceStaff))
            <section class="dashboard-alert">
                <span class="dashboard-alert-badge">要確認</span>
                <span>修正が必要な勤怠があります（差戻中）: {{ implode('、', $returnedAttendanceStaff) }}</span>
            </section>
            @endif

            @if (!empty($paidLeaveGrantTargetStaff))
            <section class="dashboard-notice">
                <span class="dashboard-notice-badge">今月</span>
                <span>今月が有休付与月のスタッフがいます: {{ implode('、', $paidLeaveGrantTargetStaff) }}</span>
            </section>
            @endif

            @if (!empty($kaigoInsuranceStartTargetStaff))
            <section class="dashboard-notice">
                <span class="dashboard-notice-badge">今月</span>
                <span>今月から介護保険料の徴収が始まるスタッフがいます（イレギュラー業務対象・要確認）: {{ implode('、', $kaigoInsuranceStartTargetStaff) }}</span>
            </section>
            @endif

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
                            href="{{ $itemHref }}"
                            @if(!empty($item['blank'])) target="_blank" rel="noopener noreferrer" @endif>
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
    </main>
</body>

</html>

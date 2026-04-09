<!DOCTYPE html>
<html lang="ja">
<head>
    @php
        $dashboardSections = [
            [
                'visible' => true,
                'badge' => 'STAFF',
                'title' => 'MENU',
                'cards' => [
                    [
                        'type' => 'link',
                        'route' => 'dashboard',
                        'title' => 'マイページ',
                        'sub' => 'システム情報の確認',
                    ],
                    [
                        'type' => 'link',
                        'route' => 'dashboard',
                        'title' => '各種申請',
                        'sub' => '個人情報変更の申請',
                        'hidden' => true,
                    ],
                                        [
                        'type' => 'link',
                        'route' => 'dashboard',
                        'title' => '年末調整',
                        'sub' => 'その年度の対象者の申請',
                        'hidden' => true,
                    ],
                ],
            ],
            [
                'visible' => !empty($isAdmin),
                'badge' => 'ADMIN',
                'title' => '管理者MENU',
                'cards' => [
                    [
                        'type' => 'link',
                        'route' => 'admin.shift.change',
                        'title' => 'シフト変更',
                        'sub' => 'スタッフのシフト変更',
                    ],
                    [
                        'type' => 'link',
                        'route' => 'attendance.management',
                        'title' => '勤怠管理',
                        'sub' => '勤怠データの確認',
                    ],
                    [
                        'type' => 'link',
                        'route' => 'attendance.punch-list',
                        'title' => '打刻一覧',
                        'sub' => '打刻スタッフを確認',
                    ],
                    [
                        'type' => 'link',
                        'route' => 'attendance.paid_leave',
                        'title' => '有休管理',
                        'sub' => '有休申請の確認',
                    ],
                    [
                        'type' => 'link',
                        'route' => 'office.sales.menu',
                        'title' => '売上',
                        'sub' => '売上情報の確認',
                        'hidden' => true,
                    ],
                    [
                        'type' => 'link',
                        'route' => 'office.documents',
                        'title' => '書類',
                        'sub' => '入社関連書類一覧',
                    ],
                ],
            ],
            [
                'visible' => !empty($isOfficeAdmin),
                'badge' => 'OFFICE',
                'title' => '事務所MENU',
                'cards' => [
                    [
                        'type' => 'link',
                        'route' => 'office.attendance',
                        'title' => '勤怠関連',
                        'sub' => '打刻・シフト変更',
                    ],
                    [
                        'type' => 'link',
                        'route' => 'office.receipt',
                        'title' => '請求関連',
                        'sub' => 'レセ入力、入金確認など',
                    ],
                    [
                        'type' => 'link',
                        'route' => 'dashboard',
                        'title' => '日報',
                        'sub' => '準備中',
                    ],
                    [
                        'type' => 'static',
                        'title' => '売上関連',
                        'sub' => null,
                        'links' => [
                            [
                                'type' => 'link',
                                'route' => 'office.sales.menu',
                                'label' => '売上',
                            ],
                            [
                                'type' => 'text',
                                'label' => '未収入金',
                            ],
                        ],
                    ],
                    [
                        'type' => 'link',
                        'route' => 'dashboard',
                        'title' => '事務業務',
                        'sub' => '準備中',
                    ],
                    [
                        'type' => 'link',
                        'route' => 'attendance.punch-list',
                        'title' => '打刻一覧',
                        'sub' => '打刻スタッフを確認',
                    ],
                ],
            ],
        ];
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/dashboard-index.css') }}">
</head>
<body class="dashboard-page">
<main class="container">
    @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

    <section class="panel dashboard-info-panel">
        <div class="dashboard-section-head">
            <span class="dashboard-section-badge">INFO</span>
            <h2 class="dashboard-section-title">Information</h2>
        </div>
        <p class="dashboard-info-text">お知らせはありません。</p>
        @if ($needsCorrection)
            <div class="warn">修正が必要な勤怠があります。</div>
        @endif
    </section>

    @foreach ($dashboardSections as $section)
        @if ($section['visible'])
            <section class="panel dashboard-office-panel">
                <div class="dashboard-section-head">
                    <span class="dashboard-section-badge">{{ $section['badge'] }}</span>
                    <h2 class="dashboard-section-title">{{ $section['title'] }}</h2>
                </div>

                <div class="office-menu-grid">
                    @foreach ($section['cards'] as $card)
                        @continue(!empty($card['hidden']))
                        @if ($card['type'] === 'link')
                            <a class="office-menu-card" href="{{ route($card['route']) }}">
                                <span class="office-menu-card-title">{{ $card['title'] }}</span>
                                <span class="office-menu-card-sub">{{ $card['sub'] }}</span>
                            </a>
                        @elseif ($card['type'] === 'static')
                            <div class="office-menu-card office-menu-card-static">
                                <span class="office-menu-card-title">{{ $card['title'] }}</span>
                                @if (!empty($card['sub']))
                                    <span class="office-menu-card-sub">{{ $card['sub'] }}</span>
                                @endif
                                <span class="office-menu-card-links">
                                    @foreach ($card['links'] as $chip)
                                        @if ($chip['type'] === 'link')
                                            <a class="office-menu-chip office-menu-chip-link" href="{{ route($chip['route']) }}">{{ $chip['label'] }}</a>
                                        @else
                                            <span class="office-menu-chip">{{ $chip['label'] }}</span>
                                        @endif
                                    @endforeach
                                </span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach
</main>
</body>
</html>

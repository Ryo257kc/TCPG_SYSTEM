<!DOCTYPE html>
<html lang="ja">

<head>
    @php
    $dashboardSections = [
    [
    'visible' => !$hidePayrollLinks || $isAdmin,
    'badge' => 'STAFF',
    'title' => 'MENU',
    'cards' => [
    [
    'type' => 'link',
    'route' => 'payslip',
    'title' => '給与',
    'sub' => '給与明細の表示',
    'visible' => !$hidePayrollLinks,
    ],
    [
    'type' => 'link',
    'route' => 'bonus',
    'title' => '賞与',
    'sub' => '賞与明細の表示',
    'visible' => !$hidePayrollLinks,
    ],
    [
    'type' => 'link',
    'route' => 'apply',
    'title' => '各種申請',
    'sub' => '準備中/個人情報変更の申請',
    'hidden' => true,
    ],
    [
    'type' => 'link',
    'route' => 'year_end_adjustment',
    'title' => '年末調整',
    'sub' => 'その年度の対象者の申請',
    'visible' => $isYearEndAdjustmentVisible ?? false,
    ],
    ],
    ],
    [
    'visible' => $isOushinStaff || $isAdmin || $isViewOnly,
    'badge' => 'STAFF',
    'title' => '往診',
    'cards' => [
    [
    'type' => 'link',
    'route' => 'home_visit.daily_report',
    'title' =>'往診日報',
    'sub' => '日々の往診業務履歴の作成',
    ],
    [
    'type' => 'link',
    'route' => 'home_visit.monthly_report',
    'title' => '往診月報',
    'sub' => '月間日報及び確定状況確認',
    ],
    [
    'type' => 'link',
    'route' => 'home_visit.receipts',
    'title' => '入金管理表',
    'sub' => '往診入金管理一覧・印刷',
    ],
    [
    'type' => 'link',
    'route' => 'dashboard',
    'title' => '回数表',
    'sub' => '準備中/月・店舗毎の回数表の表示',
    'hidden' => true,
    ],
    [
    'type' => 'link',
    'route' => 'hv_office.payment_confirmed',
    'title' => '入金確定履歴',
    'sub' => '入力済入金管理の確定履歴（自分の分）',
    ],
    ],
    ],

    [
    'visible' => $isPaymentCheck || $isAdmin,
    'badge' => 'OFFICE',
    'title' => '事務所',
    'cards' => [
    [
    'type' => 'link',
    'route' => 'office.attendance',
    'title' => '勤怠関連',
    'sub' => '打刻・シフト変更',
    'hidden' => true,
    ],

    [
    'type' => 'static',
    'title' => '勤怠関連',
    'sub' => null,
    'links' => [
    [
    'type' => 'link',
    'route' => 'office.attendance',
    'label' => 'シフト変更',
    ],
    [
    'type' => 'link',
    'route' => 'attendance.punch',
    'label' => '打刻',
    ],
    ],
    ],


    [
    'type' => 'link',
    'route' => 'office.receipt',
    'title' => '請求関連',
    'sub' => 'レセ入力、入金確認など',
    ],
    [
    'type' => 'link',
    'route' => 'office.store_daily_report',
    'title' => '店舗日報',
    'sub' => '店舗日報一覧、返戻、修正依頼など',
    ],


    [
    'type' => 'static',
    'title' => '売上関連',
    'sub' => null,
    'links' => [
    [
    'type' => 'link',
    'route' => 'office.sales.menu',
    'label' => '店舗売上',
    ],
    [
    'type' => 'link',
    'route' => 'office.sales.uncollected',
    'label' => '未収入金',
    ],
    ],
    ],


    [
    'type' => 'link',
    'route' => 'office.office_menu',
    'title' => '事務業務',
    'sub' => '経理、宛名ラベル（宛名印刷／準備中）',
    ],
    [
    'type' => 'link',
    'route' => 'attendance.punch-list',
    'title' => '打刻一覧',
    'sub' => '打刻スタッフを確認',
    ],
    ],
    ],



    [
    'visible' => $isAccounting || $isAdmin || $isViewOnly || $isVisitManagement,
    'badge' => 'OFFICE',
    'title' => '往診事務',
    'cards' => [
    [
    'type' => 'link',
    'route' => 'home_visit.patients.index',
    'title' => '患者一覧',
    'sub' => '患者一覧、詳細の編集',
    ],
    [
    'type' => 'link',
    'route' => 'home_visit.receipts',
    'title' => '入金管理表',
    'sub' => '往診入金管理一覧・印刷',
    ],
    [
    'type' => 'link',
    'route' => 'hv_office.distance',
    'title' => '距離集計',
    'sub' => '往診距離一覧',
    ],
    [
    'type' => 'link',
    'route' => 'dashboard',
    'title' => '回数表',
    'sub' => '準備中/回数表',
    'hidden' => true,
    ],
    [
    'type' => 'link',
    'route' => 'hv_office.download',
    'title' => 'ダウンロード',
    'sub' => '店舗別施術データ',
    ],
    [
    'type' => 'link',
    'route' => 'home_visit.sales',
    'title' => '往診売上',
    'sub' => '往診売上の登録',
    'visible' => $isAccounting,
    ],
    ],
    ],


    [
    'visible' => $isVisitManagement || $isAdmin,
    'badge' => 'ADMIN',
    'title' => '往診管理',
    'cards' => [
    [
    'type' => 'link',
    'route' => 'home_visit.monthly_visit',
    'title' => '月間回数',
    'sub' => '鍼灸・往療・マッサージ月間回数一覧',
    ],
    [
    'type' => 'link',
    'route' => 'home_visit.daily_report',
    'title' => '往診日報一覧',
    'sub' => 'スタッフを選択して往診日報を確認',
    ],
    [
    'type' => 'link',
    'route' => 'home_visit.receipt_summary',
    'title' => '領収一覧',
    'sub' => '領収金額集計及び一覧',
    ],
    [
    'type' => 'link',
    'route' => 'hv_office.payment_confirmed.management',
    'title' => '入金確定履歴（管理）',
    'sub' => '全スタッフ分の入金確定状況',
    'visible' => $isVisitManagement || $isViewOnly || $isAdmin,
    ],
    [
    'type' => 'link',
    'route' => 'hv_office.download',
    'title' => '往療内訳',
    'sub' => '準備中/店舗別施術データ',
    'hidden' => true,
    ],
    [
    'type' => 'link',
    'route' => 'dashboard',
    'title' => 'チェック',
    'sub' => '準備中/入社関連書類一覧',
    'hidden' => true,
    ],
    ],
    ],
    [
    'visible' => $isStoreManager || $isAdmin,
    'badge' => 'ADMIN',
    'title' => '店舗管理',
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
    'route' => 'store.sales',
    'title' => '店舗売上',
    'sub' => '売上情報の確認',
    ],
    [
    'type' => 'link',
    'route' => 'office.documents',
    'title' => '書類',
    'sub' => '入社関連書類一覧',
    'hidden' => true,
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

            @if(!empty($informationMessages))
            <div class="dashboard-info-list">
                @foreach($informationMessages as $message)
                @if(empty($message['me_check']))
                <article class="dashboard-info-item">
                    <div class="dashboard-info-meta">
                        <span class="dashboard-info-date">{{ $message['mase_date'] }}</span>
                        <strong class="dashboard-info-title">{{ $message['title'] }}</strong>
                    </div>
                    <p class="dashboard-info-text">{{ $message['contents'] }}</p>
                </article>
                @endif
                @endforeach
            </div>
            @elseif(!$needsCorrection && !($needsYearEndAttention ?? false) && empty($unconfirmedPaymentMonths))
            <p class="dashboard-info-text">お知らせはありません。</p>
            @endif

            @if($needsCorrection)
            <div class="warn">修正が必要な勤怠があります。</div>
            @endif

            @if($needsYearEndAttention ?? false)
            <div class="warn"><a href="{{ route('year_end_adjustment') }}">年末調整の申請が差し戻されました。内容を確認してください。</a></div>
            @endif

            @if(!empty($unconfirmedPaymentMonths))
            <div class="warn"><a href="{{ route('hv_office.payment_confirmed') }}">入金確定が未確定です（{{ implode('、', $unconfirmedPaymentMonths) }}）。内容を確認してください。</a></div>
            @endif
        </section>

        @foreach($dashboardSections as $section)
        @if($section['visible'])
        <section class="panel dashboard-office-panel">
            <div class="dashboard-section-head">
                <span class="dashboard-section-badge">{{ $section['badge'] }}</span>
                <h2 class="dashboard-section-title">{{ $section['title'] }}</h2>
            </div>

            <div class="office-menu-grid">
                @foreach($section['cards'] as $card)
                @continue(!empty($card['hidden']))
                @continue(array_key_exists('visible', $card) && !$card['visible'])
                @if($card['type'] === 'link')

                <a class="office-menu-card" href="{{ route($card['route']) }}">
                    <span class="office-menu-card-title">{{ $card['title'] }}</span>
                    <span class="office-menu-card-sub">{{ $card['sub'] }}</span>
                </a>
                @elseif($card['type'] === 'static')
                <div class="office-menu-card office-menu-card-static">
                    <span class="office-menu-card-title">{{ $card['title'] }}</span>
                    @if(!empty($card['sub']))
                    <span class="office-menu-card-sub">{{ $card['sub'] }}</span>
                    @endif
                    <span class="office-menu-card-links">
                        @foreach($card['links'] as $chip)
                        @if($chip['type'] === 'link')
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

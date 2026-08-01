<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 店舗日報</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/dashboard-index.css') }}">
</head>

<body class="dashboard-page">
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel dashboard-office-panel">
            @php
            $receiptLinks = [
            ['label' => '日報一覧', 'sub' => '店舗別日報一覧', 'route' => 'office.store_daily_report.daily_summary'],
            ['label' => '修正依頼', 'sub' => '確定後の日報修正依頼履歴', 'route' => 'office.store_daily_report.request_history'],
            ['label' => '返戻ノート', 'sub' => '返戻履歴一覧', 'route' => 'office.store_daily_report.return_note'],
            ['label' => 'その他一覧', 'sub' => 'チャージ金、レジ金一覧', 'route' => 'office.store_daily_report.other_list'],
            ['label' => '項目別集計', 'sub' => '割引、交通事故、物品販売一覧', 'route' => 'office.store_daily_report.item_summary'],
            ];
            @endphp

            <div class="dashboard-section-head">
                <span class="dashboard-section-badge">OFFICE</span>
                <h1 class="dashboard-section-title">店舗日報</h1>
            </div>

            <div class="office-menu-grid">
                @foreach ($receiptLinks as $item)
                @if (!empty($item['route']))
                <a class="office-menu-card" href="{{ route($item['route']) }}">
                    <span class="office-menu-card-title">{{ $item['label'] }}</span>
                    <span class="office-menu-card-sub">{{ $item['sub'] }}</span>
                </a>
                @else
                <div class="office-menu-card office-menu-card-static">
                    <span class="office-menu-card-title">{{ $item['label'] }}</span>
                    <span class="office-menu-card-sub">{{ $item['sub'] }}</span>
                </div>
                @endif
                @endforeach
            </div>

            <div>
                <a href="{{ route('dashboard') }}" class="btn btn_back">戻る</a>
            </div>
        </section>
    </main>
</body>

</html>

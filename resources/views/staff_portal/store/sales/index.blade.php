<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 売上MENU</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/dashboard-index.css') }}">
</head>

<body class="dashboard-page">
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel dashboard-office-panel">
            @php
            $salesLinks = [
            ['label' => '個別売上', 'sub' => 'スタッフ別の売上一覧', 'route' => 'store.sales.personal'],
            ['label' => '委託売上', 'sub' => '委託売上の確認', 'route' => 'store.sales.consignment'],
            ['label' => '先生別日報', 'sub' => '先生別の日報売上', 'route' => 'store.sales.daily'],
            ['label' => '先生別月報', 'sub' => '先生別の月報売上', 'route' => 'store.sales.monthly'],
            ];
            @endphp

            <div class="dashboard-section-head">
                <span class="dashboard-section-badge">STORE</span>
                <h1 class="dashboard-section-title">売上MENU</h1>
            </div>

            <div class="office-menu-grid">
                @foreach ($salesLinks as $item)
                <a class="office-menu-card" href="{{ route($item['route']) }}">
                    <span class="office-menu-card-title">{{ $item['label'] }}</span>
                    <span class="office-menu-card-sub">{{ $item['sub'] }}</span>
                </a>
                @endforeach
            </div>

            <div>
                <a href="{{ route('dashboard') }}" class="btn btn_back">戻る</a>
            </div>
        </section>
    </main>
</body>

</html>

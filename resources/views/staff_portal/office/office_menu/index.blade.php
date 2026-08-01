<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 事務業務</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/dashboard-index.css') }}">
</head>

<body class="dashboard-page">
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel dashboard-office-panel">
            @php
            $receiptLinks = [
            ['label' => '現金出納帳', 'sub' => '小口現金の入力', 'route' => 'office.office_menu.cash_book'],
            ['label' => '宛名ラベル', 'sub' => '封筒・ハガキの宛名印刷', 'route' => 'office.office_menu.address'],
            ];
            @endphp

            <div class="dashboard-section-head">
                <span class="dashboard-section-badge">OFFICE</span>
                <h1 class="dashboard-section-title">事務業務</h1>
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

            <div class="margin_t20">
                <a href="{{ route('dashboard') }}" class="btn btn_back">戻る</a>
            </div>
        </section>
    </main>
</body>

</html>

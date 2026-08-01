<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 請求関連</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/dashboard-index.css') }}">
</head>

<body class="dashboard-page">
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel dashboard-office-panel">
            @php
            $receiptLinks = [
            ['label' => 'レセ請求入力', 'sub' => '鍼灸、柔整のレセプト請求', 'route' => 'office.receipt.entry'],
            ['label' => '入金確認', 'sub' => 'レセプトの入金確認、返戻入力', 'route' => 'office.receipt.payment_confirmation'],
            ['label' => '往診窓口', 'sub' => '店舗別往診窓口一覧', 'route' => 'office.receipt.home_visit_counter'],
            ['label' => '高額療養費', 'sub' => '高額療養費の登録・確認', 'route' => 'office.receipt.high_medical'],
            ['label' => '保険者一覧', 'sub' => '保険者の登録・編集', 'route' => 'office.receipt.insurers'],
            ];
            @endphp

            <div class="dashboard-section-head">
                <span class="dashboard-section-badge">OFFICE</span>
                <h1 class="dashboard-section-title">請求関連</h1>
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

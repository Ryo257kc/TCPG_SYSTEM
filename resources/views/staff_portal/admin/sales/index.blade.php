<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 売上MENU</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
</head>
<body>
<main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

    <section class="panel content-panel">
        <h2>売上MENU</h2>

        <div class="menu-grid">
            <span class="menu-link">個別売上</span>
            <span class="menu-link">委託売上</span>
            <span class="menu-link">先生別日報</span>
            <span class="menu-link">先生別月報</span>
            {{-- 交通事故は運用検討中のため一時的に非表示（実装は保持） --}}
            {{-- <a class="menu-link single" href="{{ route('admin.sales.accident') }}">交通事故</a> --}}
        </div>

    </section>

    
</main>
</body>
</html>

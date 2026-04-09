<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 往診窓口一覧</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
</head>
<body>
<main class="container">
    @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

    <section class="panel content-panel">
        <div class="content-head">
            <h1 class="content-title">往診窓口一覧</h1>
        </div>

        <div class="office-toolbar">
            <div class="office-toolbar-group">
                <a href="{{ route('office.receipt') }}" class="btn margin20">戻る</a>
            </div>
        </div>
    </section>
</main>
</body>
</html>

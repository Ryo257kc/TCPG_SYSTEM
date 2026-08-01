<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 各種申請</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">

</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section
            class="panel content-panel sales-panel staff-viewport-panel"
            data-insurer-save-url="{{ route('office.receipt.insurers.save') }}"
            data-insurer-delete-url="{{ route('office.receipt.insurers.delete') }}"
            data-insurer-csrf="{{ csrf_token() }}">
            <div class="content-head">
                <h2 class="content-title">各種申請</h2>
            </div>


            <div class="table-wrap staff-viewport-list-wrap">

            </div>
            <div>
                <a href="{{ route('dashboard') }}" class="btn btn_back">戻る</a>
            </div>
        </section>
    </main>


</body>

</html>

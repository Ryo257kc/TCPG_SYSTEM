<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 入金確定履歴</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel sales-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">入金確定履歴一覧</h2>
            </div>

            @if(session('status'))
            <div class="status">{{ session('status') }}</div>
            @endif

            @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
            @endif

            <form method="get" action="{{ route('hv_office.payment_confirmed.management') }}" class="filter-row">
                <label>日付:
                    <input type="month" name="target_month" value="{{ $target_month }}">
                </label>
                <button type="submit" class="btn">月毎</button>
            </form>

            <div class="table-wrap staff-viewport-list-wrap">
                @if($rows->count() === 0)
                <div class="empty">「{{ $target_month }}」のデータがありません。</div>
                @else
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>月度</th>
                            <th>ID</th>
                            <th>担当</th>
                            <th>入金管理　確定日</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                        @php
                        $isConfirmed = (int) ($row->is_payment_confirmed ?? 0) === 1;
                        $displayName = $row->display_name_ja ?: $row->staff_name;
                        @endphp
                        <tr>
                            <td>{{ $target_month }}</td>
                            <td>{{ $row->payment_staff }}</td>
                            <td>{{ $displayName }}</td>
                            <td style="{{ $isConfirmed ? '' : 'background:#fff3cd;' }}">
                                {{ $isConfirmed && $row->payment_confirmed_at ? \Carbon\Carbon::parse($row->payment_confirmed_at)->format('Y-m-d H:i:s') : '' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>

            <div>
                <a href="{{ route('dashboard') }}" class="btn btn_back">戻る</a>
            </div>
        </section>
    </main>
</body>

</html>

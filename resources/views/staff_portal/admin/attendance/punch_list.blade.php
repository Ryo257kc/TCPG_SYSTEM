<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 打刻一覧</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
</head>

<body class="attendance-punch-list-page">
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">勤務予定・打刻一覧</h2>
            </div>
            <form method="get" class="filter-row">
                <label for="date">日付:</label>
                <input id="date" type="date" name="date" value="{{ $selectedDate }}">
                <button type="submit">表示</button>
            </form>

            @if ($rowCount === 0)
            <div class="empty">「{{ $selectedDate }}」の打刻はありません。</div>
            @else
            <div class="table-wrap">
                <table class="data-table">
                    <colgroup>
                        <col style="width:40px;">

                    </colgroup>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>名前</th>
                            <th>区分</th>
                            <th>始業</th>
                            <th>退出</th>
                            <th>入出</th>
                            <th>終業</th>
                            <th>店舗</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row['staff_name'] }}</td>
                            <td>{{ $row['kubun'] }}</td>
                            <td>{{ $row['start'] }}</td>
                            <td>{{ $row['exit'] }}</td>
                            <td>{{ $row['in_out'] }}</td>
                            <td>{{ $row['end'] }}</td>
                            <td>{{ $row['shop'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
            <div>
                <a href="{{ route('dashboard') }}" class="btn btn_back">戻る</a>
            </div>
        </section>

    </main>
</body>

</html>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 打刻一覧</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
    <style>
        /* .attendance-monthly-page .filter-row {
            margin-top: 14px;
        } */

        /* .attendance-monthly-page .status {
            margin-top: 10px;
            padding: 9px 10px;
            border-radius: 8px;
            border: 1px solid #e7b286;
            background: #fff3e4;
            color: #8f4c1f;
            font-weight: 700;
        } */

        .attendance-monthly-page .btn-edit {
            font-size: 12px;
            padding: 4px 7px;
            background: linear-gradient(120deg, #ffd1a8, #ffb973);
            border-color: #e9ae79;
        }

        .attendance-monthly-page .btn-apply {
            font-size: 15px;
            padding: 9px 14px;
            background: linear-gradient(120deg, #ffb36a, #ff9c52);
            border-color: #e9934f;
            color: #5f320f;
        }

        .attendance-monthly-page .note {
            text-align: right;
            color: var(--sub);
            margin: 10px 0;
        }

        .attendance-monthly-page .apply-row {
            margin-top: 14px;
            text-align: center;
        }

        .attendance-monthly-page .applied-text {
            color: #b42318;
            font-size: 19px;
            font-weight: 800;
        }

        @media (max-width: 1200px) {

            .attendance-monthly-page .data-table,
            .attendance-monthly-page .daily-table {
                min-width: 980px;
            }
        }
    </style>
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

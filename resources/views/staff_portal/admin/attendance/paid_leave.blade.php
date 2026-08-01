<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 有休管理</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
</head>

<body>
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">有休管理</h2>
            </div>
            <form method="get" class="filter-row">
                <label for="month">月度:</label>
                <input id="month" type="month" name="month" value="{{ $selectedMonth }}">
                <button type="submit">表示</button>
            </form>

            @if ($rowCount === 0)
            <div class="empty">「{{ $selectedMonth }}」の有休申請はありません。</div>
            @else
            <div class="table-wrap">
                <table class="data-table">
                    <colgroup>
                        <col style="width: 30px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>申請日</th>
                            <th>有休数</th>
                            <th>スタッフID</th>
                            <th>氏名</th>
                            <th>有休申請</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row['work_date'] }}</td>
                            <td>{{ $row['paid_leave_used'] }}</td>
                            <td>{{ $row['staff_id'] }}</td>
                            <td>{{ $row['staff_name'] }}</td>
                            <td>{{ $row['paid_leave_requested_at'] }}</td>
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

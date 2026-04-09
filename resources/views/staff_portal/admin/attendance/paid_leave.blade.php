<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 申請有休</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
</head>
<body>
<main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

    <section class="panel content-panel">
        <h2>申請有休状況</h2>
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
                    <thead>
                    <tr>
                        <th>No</th>
                        <th>有休数</th>
                        <th>申請日</th>
                        <th>スタッフID</th>
                        <th>氏名</th>
                        <th>有休申請</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row['leave_date'] }}</td>
                            <td>{{ $row['paid_leave_used'] }}</td>
                            <td>{{ $row['staff_id'] }}</td>
                            <td>{{ $row['staff_name'] }}</td>
                            <td>{{ $row['applied_date'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>


</main>
</body>
</html>

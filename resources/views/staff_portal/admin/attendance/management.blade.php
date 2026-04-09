<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 勤怠管理</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
</head>
<body class="attendance-management-page">
<main class="container">
    @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

    <section class="panel content-panel">
        <div class="content-head">
            <h2 class="content-title">勤怠申請一覧</h2>
        </div>
        <form method="get" class="filter-row">
            <label for="month">対象月:</label>
            <input id="month" type="month" name="month" value="{{ $selectedMonth }}">
            <button type="submit">表示</button>
        </form>

        @if ($rowCount === 0)
            <div class="empty">{{ $selectedMonth }} の勤怠申請はありません。</div>
        @else
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>No</th>
                        <th>年月</th>
                        <th>スタッフID</th>
                        <th>名前</th>
                        <th>本人申請</th>
                        <th>管理者承認</th>
                        <th>詳細</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row['year_month'] }}</td>
                            <td>{{ $row['staff_id'] }}</td>
                            <td>{{ $row['staff_name'] }}</td>
                            <td>{{ $row['self_applied_at'] }}</td>
                            <td>{{ $row['admin_approved'] }}</td>
                            <td><a class="btn" href="{{ $row['detail_url'] }}">詳細</a></td>
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

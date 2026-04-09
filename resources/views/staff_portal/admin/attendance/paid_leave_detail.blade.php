<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 申請有休詳細</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
</head>
<body>
<main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

    <section class="panel content-panel">
        <h2>申請有休詳細</h2>
        <div class="meta">
            <span>対象月: {{ $selectedMonth }}</span>
            <span>スタッフID: {{ $targetStaffId }}</span>
            <span>氏名: {{ $targetStaffName }}</span>
        </div>

        @if ($rowCount === 0)
            <div class="empty">対象データはありません。</div>
        @else
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>日付</th>
                        <th>区分</th>
                        <th>有休申請日時</th>
                        <th>管理者承認</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>{{ $row['date'] }}</td>
                            <td>{{ $row['kubun'] }}</td>
                            <td>{{ $row['paid_leave_applied_at'] }}</td>
                            <td>{{ $row['admin_approved'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="back-row">
            <a class="btn btn-back" href="{{ route('admin.paid-leave', ['month' => $selectedMonth]) }}">戻る</a>
        </div>
    </section>

    
</main>
</body>
</html>

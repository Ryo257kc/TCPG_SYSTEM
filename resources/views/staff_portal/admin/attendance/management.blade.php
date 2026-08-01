<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 勤怠管理</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">
    <style>
        .attendance-management-summary {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 14px;
        }

        .attendance-management-return-box {
            width: min(360px, 100%);
            border: 1px solid #efc6c6;
            background: #fff7f7;
            color: #9f1f1f;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 13px;
            line-height: 1.6;
        }

        .attendance-management-return-title {
            font-weight: 700;
            margin-bottom: 8px;
        }

        .attendance-management-return-item+.attendance-management-return-item {
            margin-top: 6px;
        }

        .attendance-management-page .data-table tbody tr.daily-row-returned>td,
        .attendance-management-page .data-table tbody tr>td.daily-row-returned-cell {
            background: #fdecec !important;
            color: #9f1f1f !important;
            font-weight: 700;
        }

        @media (max-width: 900px) {
            .attendance-management-summary {
                display: block;
            }
        }
    </style>

</head>

<body class="attendance-management-page">
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">勤怠管理</h2>
            </div>
            <form method="get" class="filter-row">
                <label for="month">対象月:</label>
                <input id="month" type="month" name="month" value="{{ $selectedMonth }}">
                <button type="submit">表示</button>
            </form>

            <div class="attendance-management-summary">
                <div class="attendance-management-return-box">
                    <div class="attendance-management-return-title">差戻し内容</div>
                    @forelse (($returnedSummaries ?? []) as $returnedSummary)
                    <div class="attendance-management-return-item">
                        {{ $returnedSummary['work_date'] }} {{ $returnedSummary['return_note'] }}
                    </div>
                    @empty
                    <div class="attendance-management-return-item">差戻しはありません。</div>
                    @endforelse
                </div>
            </div>

            @if ($rowCount === 0)
            <div class="empty">{{ $selectedMonth }} の勤怠申請はありません。</div>
            @else
            <div class="table-wrap">
                <table class="data-table">
                    <colgroup>
                        <col style="width: 30px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>年月</th>
                            <th>スタッフID</th>
                            <th>名前</th>
                            <th>本人申請</th>
                            <th>管理者承認</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                        <tr @class(['daily-row-returned'=> !empty($row['is_returned'])])>
                            <td @class(['daily-row-returned-cell'=> !empty($row['is_returned'])])>{{ $loop->iteration }}</td>
                            <td @class(['daily-row-returned-cell'=> !empty($row['is_returned'])])>{{ $row['year_month'] }}</td>
                            <td @class(['daily-row-returned-cell'=> !empty($row['is_returned'])])>{{ $row['staff_id'] }}</td>
                            <td @class(['daily-row-returned-cell'=> !empty($row['is_returned'])])>{{ $row['staff_name'] }}</td>
                            <td @class(['daily-row-returned-cell'=> !empty($row['is_returned'])])>{{ $row['self_applied_at'] }}</td>
                            <td @class(['daily-row-returned-cell'=> !empty($row['is_returned'])])>{{ $row['admin_approved'] }}</td>
                            <td><a class="btn btn_small" href="{{ $row['detail_url'] }}">詳細</a></td>
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

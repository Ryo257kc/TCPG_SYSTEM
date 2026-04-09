<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 月間勤怠</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/daily_table_item_plain.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/attendance-monthly.css') }}">
</head>
<body class="attendance-monthly-page">
<main class="container">
    @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

    <section class="panel content-panel">
        <div class="content-head">
            <h2 class="content-title">月間勤怠</h2>
        </div>

        @if ($statusMessage !== '')
            <div class="status">{{ $statusMessage }}</div>
        @endif

        <form method="get" action="{{ route('attendance.monthly') }}" class="filter-row">
            <label for="month">日付</label>
            <input id="month" type="month" name="month" value="{{ $selectedMonth }}">
            <button type="submit">表示</button>
        </form>

        @if ($rowCount === 0)
            <div class="empty">当月の勤怠はありません。</div>
        @else
            <p class="note">※赤セル: 修正が必要な勤怠があります。</p>

            @php
                $sharedRows = collect($dailyRows ?? [])->map(static function (array $row): array {
                    return [
                        'date_label' => $row['date_label'] ?? '',
                        'date_url' => $row['edit_url'] ?? '',
                        'holiday_category' => $row['holiday_category'] ?? '',
                        'attendance_category' => $row['attendance_category'] ?? '',
                        'category_time' => $row['category_time'] ?? '',
                        'paid_leave_used' => $row['paid_leave_used'] ?? '',
                        'actual_start' => $row['actual_start'] ?? '',
                        'actual_leave' => $row['actual_leave'] ?? '',
                        'actual_break_out' => $row['actual_break_out'] ?? '',
                        'actual_end' => $row['actual_end'] ?? '',
                        'actual_scheduled' => $row['actual_scheduled'] ?? '',
                        'shift_start' => $row['shift_start'] ?? '',
                        'shift_leave' => $row['shift_leave'] ?? '',
                        'shift_break_out' => $row['shift_break_out'] ?? '',
                        'shift_end' => $row['shift_end'] ?? '',
                        'shift_scheduled' => $row['shift_scheduled'] ?? '',
                        'change_start' => $row['change_start'] ?? '',
                        'change_leave' => $row['change_leave'] ?? '',
                        'change_break_out' => $row['change_break_out'] ?? '',
                        'change_end' => $row['change_end'] ?? '',
                        'change_scheduled' => $row['change_scheduled'] ?? '',
                        'overtime' => $row['overtime'] ?? '',
                        'night_overtime' => $row['night_overtime'] ?? '',
                        'work_store' => $row['work_store'] ?? '',
                        'timecard_note' => $row['timecard_note'] ?? '',
                    ];
                })->all();
                $rows = $sharedRows;
                $tableClass = 'data-table';
                $wrapClass = 'table-wrap';
                $rowClassResolver = static function (array $row): string {
                    $hasShift = collect([
                        $row['shift_start'] ?? '',
                        $row['shift_leave'] ?? '',
                        $row['shift_break_out'] ?? '',
                        $row['shift_end'] ?? '',
                    ])->contains(static fn ($value) => trim((string) $value) !== '');

                    return $hasShift ? '' : 'daily-row-muted';
                };
                $showPaidLeaveUsed = true;
                $showActualScheduled = true;
                $showShiftScheduled = true;
                $showChangeScheduled = true;
                $showNightOvertime = true;
            @endphp
            @include('shared.attendance.daily_table_item_plain', [
                'rows' => $rows,
                'tableClass' => $tableClass,
                'wrapClass' => $wrapClass,
                'showPaidLeaveUsed' => $showPaidLeaveUsed,
                'showActualScheduled' => $showActualScheduled,
                'showShiftScheduled' => $showShiftScheduled,
                'showChangeScheduled' => $showChangeScheduled,
                'showNightOvertime' => $showNightOvertime,
                'rowClassResolver' => $rowClassResolver,
            ])
            @if (false)
            <div class="{{ $wrapClass }}">
                <table class="{{ $tableClass }}">
                    <thead>
                    <tr>
                        <th rowspan="2">日付</th>
                        <th colspan="{{ $showPaidLeaveUsed ? 4 : 3 }}"  class="f-sen">勤怠</th>
                        <th colspan="{{ $showActualScheduled ? 5 : 4 }}" class="f-sen">打刻</th>
                        <th colspan="{{ $showShiftScheduled ? 5 : 4 }}" class="f-sen">シフト</th>
                        <th colspan="{{ 4 + ($showChangeScheduled ? 1 : 0) + 1 + ($showNightOvertime ? 1 : 0) }}" class="f-sen">変更実績</th>
                        <th rowspan="2">店舗</th>
                        <th rowspan="2" class="remark-col">備考</th>
                    </tr>
                    <tr>
                        <th>休日区分<br>勤怠区分</th>
                        <th>区分</th>
                        <th class="f-sen">時間</th>
                        @if ($showPaidLeaveUsed)
                            <th>有休使用</th>
                        @endif
                        <th>始業</th><th>退出</th><th>入出</th><th class="f-sen">終業</th>
                        @if ($showActualScheduled)
                            <th class="f-sen">所定</th>
                        @endif
                        <th>始業</th><th>退出</th><th>入出</th><th class="f-sen">終業</th>
                        @if ($showShiftScheduled)
                            <th class="f-sen">時間</th>
                        @endif
                        <th>始業</th><th>退出</th><th>入出</th><th>終業</th>
                        @if ($showChangeScheduled)
                            <th>残業</th>
                        @endif
                        <th class="f-sen">所定</th>
                        @if ($showNightOvertime)
                            <th>深夜</th>
                        @endif
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($rows as $row)
                        @php
                            $resolvedRowClass = is_callable($rowClassResolver) ? $rowClassResolver($row) : '';
                        @endphp
                        <tr @class([$resolvedRowClass => $resolvedRowClass !== ''])>
                            <td>{{ $row['date_label'] ?? '' }}</td>
                            <td>{{ $row['holiday_category'] ?? '' }}</td>
                            <td>{{ $row['attendance_category'] ?? '' }}</td>
                            <td class="f-sen">{{ $row['category_time'] ?? '' }}</td>
                            @if ($showPaidLeaveUsed)
                                <td>{{ $row['paid_leave_used'] ?? '' }}</td>
                            @endif
                            <td>{{ $row['actual_start'] ?? '' }}</td>
                            <td>{{ $row['actual_leave'] ?? '' }}</td>
                            <td>{{ $row['actual_break_out'] ?? '' }}</td>
                            <td class="f-sen">{{ $row['actual_end'] ?? '' }}</td>
                            @if ($showActualScheduled)
                                <td class="f-sen">{{ $row['actual_scheduled'] ?? '' }}</td>
                            @endif
                            <td>{{ $row['shift_start'] ?? '' }}</td>
                            <td>{{ $row['shift_leave'] ?? '' }}</td>
                            <td>{{ $row['shift_break_out'] ?? '' }}</td>
                            <td class="f-sen">{{ $row['shift_end'] ?? '' }}</td>
                            @if ($showShiftScheduled)
                                <td class="f-sen">{{ $row['shift_scheduled'] ?? '' }}</td>
                            @endif
                            <td>{{ $row['change_start'] ?? '' }}</td>
                            <td>{{ $row['change_leave'] ?? '' }}</td>
                            <td>{{ $row['change_break_out'] ?? '' }}</td>
                            <td>{{ $row['change_end'] ?? '' }}</td>
                            @if ($showChangeScheduled)
                                <td>{{ $row['overtime'] ?? '' }}</td>
                            @endif
                            <td class="f-sen">{{ $row['change_scheduled'] ?? '' }}</td>
                            @if ($showNightOvertime)
                                <td>{{ $row['night_overtime'] ?? '' }}</td>
                            @endif
                            <td>{{ $row['work_store'] ?? '' }}</td>
                            <td class="remark-col">{{ $row['timecard_note'] ?? '' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            <div class="apply-row">
                @if ($monthlyAppliedAt)
                    <span class="applied-text">{{ $monthlyAppliedAt }} 本人申請済</span>
                @elseif ($canMonthlyApply)
                    <form method="post" action="{{ route('attendance.monthly.apply') }}">
                        @csrf
                        <input type="hidden" name="month" value="{{ $selectedMonth }}">
                        <button class="btn btn-apply" type="submit">上記の勤怠で申請する</button>
                    </form>
                @endif
            </div>
        @endif
    </section>

</main>
</body>
</html>

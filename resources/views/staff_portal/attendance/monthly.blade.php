<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCPG SYSTEM - 月間勤怠</title>
    <link rel="stylesheet" href="{{ asset('css/staff_portal/app-shell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/daily_table_item_plain.css') }}">
    <link rel="stylesheet" href="{{ asset('css/staff_portal/data_table.css') }}">

    <style>
        .attendance-monthly-page .data-table tr.daily-row-returned td {
            background: #fdecec;
            color: #9f1f1f;
            font-weight: 700;
        }

        /* .attendance-monthly-page .apply-row {
            margin-top: 14px;
            text-align: center;
        } */

        /* .attendance-monthly-page .applied-text {
            color: #b42318;
            font-size: 19px;
            font-weight: 800;
        } */

        /* .attendance-monthly-page th {
            font-size: 14px;
            line-height: 14px;
        } */

        /* @media (max-width: 1200px) {

            .attendance-monthly-page .data-table,
            .attendance-monthly-page .daily-table {
                min-width: 980px;
            }
        } */
    </style>

</head>

<body class="attendance-monthly-page">
    <main class="container">
        @include('staff_portal.shared.app_header', ['displayName' => $displayName, 'hidePayrollLinks' => $hidePayrollLinks ?? false])

        <section class="panel content-panel staff-viewport-panel">
            <div class="content-head">
                <h2 class="content-title">月間勤怠</h2>
            </div>

            @if ($statusMessage !== '')
            <div class="status">{{ $statusMessage }}</div>
            @endif
            <div class="staff-title-row">
                <form method="get" action="{{ route('attendance.monthly') }}" class="filter-row">
                    <label for="month">日付</label>
                    <input id="month" type="month" name="month" value="{{ $selectedMonth }}">
                    <button type="submit">表示</button>
                </form>

                @if (!empty($hasTimeCards))
                <p class="notice right">※赤セル: 修正が必要な勤怠があります。</p>
                @endif
            </div>
            @if (empty($hasTimeCards))
                <div class="empty">この月の勤怠データはまだ作成されていません。シフト作成後に編集できます。</div>
                @else
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
            'actual_scheduled_hours' => $row['actual_scheduled_hours'] ?? '',
            'shift_start' => $row['shift_start'] ?? '',
            'shift_leave' => $row['shift_leave'] ?? '',
            'shift_break_out' => $row['shift_break_out'] ?? '',
            'shift_end' => $row['shift_end'] ?? '',
            'shift_scheduled_hours' => $row['shift_scheduled_hours'] ?? '',
            'change_start' => $row['change_start'] ?? '',
            'change_leave' => $row['change_leave'] ?? '',
            'change_break_out' => $row['change_break_out'] ?? '',
            'change_end' => $row['change_end'] ?? '',
            'change_scheduled' => $row['change_scheduled'] ?? '',
            'overtime' => $row['overtime'] ?? '',
            'night_overtime' => $row['night_overtime'] ?? '',
            'work_store' => $row['work_store'] ?? '',
            'timecard_note' => $row['timecard_note'] ?? '',
            'is_returned' => $row['is_returned'] ?? false,
            'row_class' => !empty($row['is_returned']) ? 'daily-row-returned' : '',
            ];
            })->all();
            $rows = $sharedRows;
            $tableClass = 'data-table';
            $wrapClass = 'table-wrap';
            $rowClassResolver = static function (array $row): string {
            if (!empty($row['row_class'])) {
            return (string) $row['row_class'];
            }

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

            <div class="back-row">
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
            <div>
                <a href="{{ route('dashboard') }}" class="btn btn_back">戻る</a>
            </div>
        </section>

    </main>
</body>

</html>

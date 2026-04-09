@php
    $sharedRows = collect($dailyRows ?? [])->map(static function (array $row): array {
        return [
            'date_label' => $row['date_label'] ?? '',
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
@endphp

@include('shared.attendance.daily_table_item_plain', [
    'rows' => $sharedRows,
    'tableClass' => 'data-table',
    'wrapClass' => 'table-wrap',
    'showPaidLeaveUsed' => true,
    'showActualScheduled' => true,
    'showShiftScheduled' => true,
    'showChangeScheduled' => true,
    'showNightOvertime' => true,
    'rowClassResolver' => static function (array $row): string {
        $hasShift = collect([
            $row['shift_start'] ?? '',
            $row['shift_leave'] ?? '',
            $row['shift_break_out'] ?? '',
            $row['shift_end'] ?? '',
        ])->contains(static fn ($value) => trim((string) $value) !== '');

        return $hasShift ? '' : 'daily-row-muted';
    },
])

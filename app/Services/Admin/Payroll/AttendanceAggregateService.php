<?php

namespace App\Services\Admin\Payroll;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AttendanceAggregateService
{
    public function buildForPayrollMonth(string $staffId, string $selectedMonthDate): array
    {
        if ($staffId === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedMonthDate)) {
            return [];
        }

        $attendanceMonthDate = date('Y-m-01', strtotime($selectedMonthDate . ' -1 month'));
        $fromDate = $attendanceMonthDate;
        $toDate = date('Y-m-t', strtotime($fromDate));

        $table = 'dbo.mx_time_cards';
        if (!Schema::connection('sqlsrv')->hasTable($table)) {
            return [];
        }

        $columns = Schema::connection('sqlsrv')->getColumnListing($table);
        $staffCol = $this->pickColumn($columns, ['staff_id', 'staff_code', 'staff_name']);
        $dateCol = $this->pickColumn($columns, ['work_date', 'date']);
        if ($staffCol === null || $dateCol === null) {
            return [];
        }

        $actualStartCol = $this->pickColumn($columns, ['actual_start']);
        $changeStartCol = $this->pickColumn($columns, ['change_start']);
        $shiftStartCol = $this->pickColumn($columns, ['shift_start']);

        $absenceCol = $this->pickColumn($columns, ['absence_num', 'absence_days']);
        $holidayDaysCol = $this->pickColumn($columns, ['work_horiday_num', 'work_holiday_num']);
        $workTimeCol = $this->pickColumn($columns, ['work_time']);
        $overtimeCol = $this->pickColumn($columns, ['overtime', 'overtime_hours']);
        $nightOvertimeCol = $this->pickColumn($columns, ['night_over_time', 'night_overtime_hours']);
        $paidLeaveUsedCol = $this->pickColumn($columns, ['paid_leave_used', 'horiday_true']);
        $paidLeaveRemainCol = $this->pickColumn($columns, ['paid_leave_balance', 'horiday_true_num']);
        $closedDaysCol = $this->pickColumn($columns, ['days_closed']);
        $closedTimeCol = $this->pickColumn($columns, ['time_closed']);

        $selectCols = array_values(array_filter(array_unique([
            $staffCol,
            $dateCol,
            $actualStartCol,
            $changeStartCol,
            $shiftStartCol,
            $absenceCol,
            $holidayDaysCol,
            $workTimeCol,
            $overtimeCol,
            $nightOvertimeCol,
            $paidLeaveUsedCol,
            $paidLeaveRemainCol,
            $closedDaysCol,
            $closedTimeCol,
        ])));

        $rows = DB::connection('sqlsrv')
            ->table($table)
            ->whereRaw('LTRIM(RTRIM(' . $this->wrap($staffCol) . ')) = ?', [$staffId])
            ->whereRaw('CONVERT(date, ' . $this->wrap($dateCol) . ') between ? and ?', [$fromDate, $toDate])
            ->get($selectCols);

        if ($rows->isEmpty()) {
            return [];
        }

        $attendanceDays = 0.0;
        $absenceDays = 0.0;
        $holidayWorkDays = 0.0;
        $workTime = 0.0;
        $overtime = 0.0;
        $nightOvertime = 0.0;
        $paidLeaveUsed = 0.0;
        $paidLeaveRemain = null;
        $closedDays = 0.0;
        $closedTime = 0.0;

        foreach ($rows as $row) {
            $r = (array) $row;

            $hasAttendance = false;
            foreach ([$changeStartCol, $actualStartCol, $shiftStartCol] as $c) {
                if ($c !== null && !empty($r[$c])) {
                    $hasAttendance = true;
                    break;
                }
            }
            if ($hasAttendance) {
                $attendanceDays += 1;
            }

            $absenceDays += (float) ($absenceCol !== null ? ($r[$absenceCol] ?? 0) : 0);
            $holidayWorkDays += (float) ($holidayDaysCol !== null ? ($r[$holidayDaysCol] ?? 0) : 0);
            $workTime += (float) ($workTimeCol !== null ? ($r[$workTimeCol] ?? 0) : 0);
            $overtime += (float) ($overtimeCol !== null ? ($r[$overtimeCol] ?? 0) : 0);
            $nightOvertime += (float) ($nightOvertimeCol !== null ? ($r[$nightOvertimeCol] ?? 0) : 0);
            $paidLeaveUsed += (float) ($paidLeaveUsedCol !== null ? ($r[$paidLeaveUsedCol] ?? 0) : 0);
            $closedDays += (float) ($closedDaysCol !== null ? ($r[$closedDaysCol] ?? 0) : 0);
            $closedTime += (float) ($closedTimeCol !== null ? ($r[$closedTimeCol] ?? 0) : 0);

            if ($paidLeaveRemainCol !== null && isset($r[$paidLeaveRemainCol]) && $r[$paidLeaveRemainCol] !== null && $r[$paidLeaveRemainCol] !== '') {
                $paidLeaveRemain = (float) $r[$paidLeaveRemainCol];
            }
        }

        return [
            'work_in_num' => $this->normalizeNumericInput($attendanceDays),
            'absence_num' => $this->normalizeNumericInput($absenceDays),
            'work_horiday_num' => $this->normalizeNumericInput($holidayWorkDays),
            'work_time' => $this->normalizeNumericInput($workTime),
            'overtime' => $this->normalizeNumericInput($overtime),
            'night_over_time' => $this->normalizeNumericInput($nightOvertime),
            'horiday_true' => $this->normalizeNumericInput($paidLeaveUsed),
            'horiday_true_num' => $this->normalizeNumericInput($paidLeaveRemain ?? 0),
            'days_closed' => $this->normalizeNumericInput($closedDays),
            'time_closed' => $this->normalizeNumericInput($closedTime),
        ];
    }

    private function normalizeNumericInput(mixed $value): mixed
    {
        if ($value === null) {
            return 0;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return 0;
        }

        $normalized = str_replace([',', ' ', '　'], '', $text);
        if (!preg_match('/^-?\d+(\.\d+)?$/', $normalized)) {
            return $text;
        }

        return str_contains($normalized, '.') ? (float) $normalized : (int) $normalized;
    }

    private function pickColumn(array $columns, array $candidates): ?string
    {
        $map = [];
        foreach ($columns as $col) {
            $map[mb_strtolower((string) $col)] = (string) $col;
        }
        foreach ($candidates as $name) {
            $k = mb_strtolower((string) $name);
            if (isset($map[$k])) {
                return $map[$k];
            }
        }

        return null;
    }

    private function wrap(string $column): string
    {
        return '[' . str_replace(']', ']]', $column) . ']';
    }
}

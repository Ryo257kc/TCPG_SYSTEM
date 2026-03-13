<?php

namespace App\Services\Admin\V2\Attendance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AttendanceV2BulkReflectService
{
    public function __construct(
        private readonly AttendanceV2PayrollTargetService $payrollTargetService,
    ) {
    }

    /**
     * @param list<array{staff_id:string,time_card_keys:list<string>}> $staffRows
     * @return array{updated:int,locked:int,missing:int}
     */
    public function reflect(array $staffRows, int $year, int $month): array
    {
        if ($year < 2000 || $month < 1 || $month > 12 || $staffRows === []) {
            return ['updated' => 0, 'locked' => 0, 'missing' => 0];
        }

        [$targetYear, $targetMonth] = $this->payrollTargetService->targetPayrollYearMonth($year, $month);

        $timeCardKeyToStaffId = [];
        $staffIds = [];
        foreach ($staffRows as $staffRow) {
            $staffId = trim((string) ($staffRow['staff_id'] ?? ''));
            if ($staffId === '') {
                continue;
            }

            $staffIds[$staffId] = true;
            foreach ((array) ($staffRow['time_card_keys'] ?? []) as $timeCardKey) {
                $timeCardKey = trim((string) $timeCardKey);
                if ($timeCardKey !== '') {
                    $timeCardKeyToStaffId[$timeCardKey] = $staffId;
                }
            }
        }

        if ($timeCardKeyToStaffId === [] || $staffIds === []) {
            return ['updated' => 0, 'locked' => 0, 'missing' => 0];
        }

        $timeCards = $this->timeCards(array_keys($timeCardKeyToStaffId), $year, $month);
        $summaries = [];
        foreach ($timeCards as $timeCard) {
            $timeCardKey = trim((string) ($timeCard->time_card_key ?? ''));
            $staffId = $timeCardKeyToStaffId[$timeCardKey] ?? '';
            if ($staffId === '') {
                continue;
            }

            $summaries[$staffId] ??= $this->emptySummary();
            $this->accumulate($summaries[$staffId], $timeCard);
        }

        $payrollRows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$targetYear])
            ->whereRaw('MONTH([supply_month]) = ?', [$targetMonth])
            ->whereIn(DB::raw('LTRIM(RTRIM([kyuyo_staff_id]))'), array_keys($staffIds))
            ->get(['kyuyo_sho_no', 'kyuyo_staff_id', 'edit_lock']);
        $payrollRowsByStaffId = [];
        foreach ($payrollRows as $payrollRow) {
            $staffId = trim((string) ($payrollRow->kyuyo_staff_id ?? ''));
            if ($staffId === '') {
                continue;
            }
            $payrollRowsByStaffId[$staffId] ??= [];
            $payrollRowsByStaffId[$staffId][] = $payrollRow;
        }

        $updated = 0;
        $locked = 0;
        $missing = 0;

        foreach (array_keys($staffIds) as $staffId) {
            $staffPayrollRows = $payrollRowsByStaffId[$staffId] ?? [];
            if ($staffPayrollRows === []) {
                $missing++;
                continue;
            }

            $summary = $summaries[$staffId] ?? $this->emptySummary();
            foreach ($staffPayrollRows as $payrollRow) {
                if (!isset($payrollRow->kyuyo_sho_no)) {
                    continue;
                }

                if (((int) ($payrollRow->edit_lock ?? 0)) === 1) {
                    $locked++;
                    continue;
                }

                $updated += DB::connection('sqlsrv_payroll')
                    ->table('dbo.mx_kyuyo_shou')
                    ->where('kyuyo_sho_no', (int) $payrollRow->kyuyo_sho_no)
                    ->update($this->payload($summary));
            }
        }

        return [
            'updated' => $updated,
            'locked' => $locked,
            'missing' => $missing,
        ];
    }

    /**
     * @param list<string> $timeCardKeys
     * @return \Illuminate\Support\Collection<int,object>
     */
    private function timeCards(array $timeCardKeys, int $year, int $month)
    {
        $query = DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards')
            ->select([
                DB::raw('LTRIM(RTRIM(staff_name)) as time_card_key'),
                'work_date',
                'holiday_category',
                'work_type',
                'work_type_time',
                'paid_leave_used',
                'shift_start',
                'shift_leave',
                'shift_break_out',
                'shift_end',
                'change_start',
                'change_leave',
                'change_break_out',
                'change_end',
                'overtime',
            ])
            ->whereRaw('YEAR([work_date]) = ?', [$year])
            ->whereRaw('MONTH([work_date]) = ?', [$month])
            ->whereIn(DB::raw('LTRIM(RTRIM([staff_name]))'), $timeCardKeys)
            ->orderBy('work_date');

        if (Schema::connection('sqlsrv')->hasColumn('mx_time_cards', 'night_overtime')) {
            $query->addSelect('night_overtime');
        }

        return $query->get();
    }

    /**
     * @return array{
     *   work_in_num:float,
     *   absence_num:float,
     *   work_holiday_num:float,
     *   work_time:float,
     *   work_time_num:float,
     *   overtime:float,
     *   late_time:float,
     *   night_over_time:float,
     *   holiday_true:float,
     *   days_closed:float,
     *   time_closed:float,
     *   work_kiso_num:float
     * }
     */
    private function emptySummary(): array
    {
        return [
            'work_in_num' => 0.0,
            'absence_num' => 0.0,
            'work_holiday_num' => 0.0,
            'work_time' => 0.0,
            'work_time_num' => 0.0,
            'overtime' => 0.0,
            'late_time' => 0.0,
            'night_over_time' => 0.0,
            'holiday_true' => 0.0,
            'days_closed' => 0.0,
            'time_closed' => 0.0,
            'work_kiso_num' => 0.0,
        ];
    }

    /**
     * @param array<string,float> $summary
     */
    private function accumulate(array &$summary, object $timeCard): void
    {
        $workType = trim((string) ($timeCard->work_type ?? ''));
        $workTypeTime = (float) ($timeCard->work_type_time ?? 0);
        $paidLeaveUsed = (float) ($timeCard->paid_leave_used ?? 0);
        $overtime = (float) ($timeCard->overtime ?? 0);
        $nightOvertime = (float) ($timeCard->night_overtime ?? 0);
        $changeScheduled = $this->scheduledHours(
            $timeCard->change_start ?? null,
            $timeCard->change_leave ?? null,
            $timeCard->change_break_out ?? null,
            $timeCard->change_end ?? null,
        );
        $shiftScheduled = $this->scheduledHours(
            $timeCard->shift_start ?? null,
            $timeCard->shift_leave ?? null,
            $timeCard->shift_break_out ?? null,
            $timeCard->shift_end ?? null,
        );
        $workHours = $changeScheduled > 0 ? $changeScheduled : $shiftScheduled;

        $isHolidayWork = in_array($workType, ['莨大・', '豕募・'], true);
        $isAbsent = $workType === '谺蜍､';
        $isLateEarly = in_array($workType, ['驕・綾', '譌ｩ騾', '驕・掠'], true);
        $isClosed = $workType === '休業';
        $hasChangeRecord = $this->hasAnyTimeValue([
            $timeCard->change_start ?? null,
            $timeCard->change_leave ?? null,
            $timeCard->change_break_out ?? null,
            $timeCard->change_end ?? null,
        ]);

        if (!$isAbsent && ($hasChangeRecord || $shiftScheduled > 0)) {
            $summary['work_in_num'] += 1;
        }
        if ($isAbsent) {
            $summary['absence_num'] += 1;
        }
        if ($isHolidayWork) {
            $summary['work_holiday_num'] += 1;
            $summary['work_time_num'] += $workTypeTime;
        }
        if ($isLateEarly) {
            $summary['late_time'] += $workTypeTime;
        }
        if ($isClosed) {
            $summary['days_closed'] += 1;
            $summary['time_closed'] += $workTypeTime;
        }

        $summary['work_time'] += $workHours;
        $summary['holiday_true'] += $paidLeaveUsed;
        $summary['overtime'] += $overtime;
        $summary['night_over_time'] += $nightOvertime;
        $summary['work_kiso_num'] = $summary['work_in_num']
            + $summary['work_holiday_num']
            + $summary['holiday_true']
            + $summary['days_closed'];
    }

    /**
     * @param array<string,float> $summary
     * @return array<string,float|int>
     */
    private function payload(array $summary): array
    {
        return [
            'work_in_num' => round($summary['work_in_num'], 2),
            'absence_num' => round($summary['absence_num'], 2),
            'work_horiday_num' => round($summary['work_holiday_num'], 2),
            'work_time' => round($summary['work_time'], 2),
            'work_time_num' => round($summary['work_time_num'], 2),
            'overtime' => round($summary['overtime'], 2),
            'late_time' => round($summary['late_time'], 2),
            'night_over_time' => round($summary['night_over_time'], 2),
            'horiday_true' => round($summary['holiday_true'], 2),
            'days_closed' => round($summary['days_closed'], 2),
            'time_closed' => round($summary['time_closed'], 2),
            'work_kiso_num' => round($summary['work_kiso_num'], 2),
        ];
    }

    private function scheduledHours(
        mixed $startValue,
        mixed $leaveValue,
        mixed $breakOutValue,
        mixed $endValue,
    ): float {
        $startMinutes = $this->parseTimeMinutes($startValue);
        $endMinutes = $this->parseTimeMinutes($endValue);
        if ($startMinutes === null || $endMinutes === null) {
            return 0.0;
        }

        $leaveMinutes = $this->parseTimeMinutes($leaveValue);
        $breakOutMinutes = $this->parseTimeMinutes($breakOutValue);
        if ($leaveMinutes !== null && $breakOutMinutes !== null) {
            return (
                $this->minutesBetween($startMinutes, $leaveMinutes)
                + $this->minutesBetween($breakOutMinutes, $endMinutes)
            ) / 60;
        }

        return $this->minutesBetween($startMinutes, $endMinutes) / 60;
    }

    private function parseTimeMinutes(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        if (preg_match('/^\d+(\.\d+)?$/', $text) === 1) {
            $numeric = (float) $text;
            if ($numeric <= 24) {
                return (int) round($numeric * 60);
            }

            $digitsOnly = preg_replace('/\D+/', '', $text);
            if ($digitsOnly !== null && preg_match('/^\d{3,4}$/', $digitsOnly) === 1) {
                $hours = (int) substr($digitsOnly, 0, -2);
                $minutes = (int) substr($digitsOnly, -2);
                if ($hours < 24 && $minutes < 60) {
                    return ($hours * 60) + $minutes;
                }
            }
        }

        $normalized = str_ireplace(['AM', 'PM'], [' AM ', ' PM '], $text);
        $timestamp = strtotime('2000-01-01 ' . $normalized);
        if ($timestamp === false) {
            $timestamp = strtotime($normalized);
        }

        if ($timestamp === false) {
            return null;
        }

        return ((int) date('G', $timestamp) * 60) + (int) date('i', $timestamp);
    }

    private function hasAnyTimeValue(array $values): bool
    {
        foreach ($values as $value) {
            if ($this->parseTimeMinutes($value) !== null) {
                return true;
            }
        }

        return false;
    }

    private function minutesBetween(int $startMinutes, int $endMinutes): int
    {
        if ($endMinutes < $startMinutes) {
            $endMinutes += 24 * 60;
        }

        return max(0, $endMinutes - $startMinutes);
    }
}

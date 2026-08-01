<?php

namespace App\Services\Admin\V2\Attendance;

use App\Support\AttendanceTime;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AttendanceV2ListSummaryService
{
    // 旧系の勤怠一覧集計サービス候補。
    // 現在の勤怠一覧と給与反映の計算式は AttendanceV2MonthlySummaryService に集約済み。
    // 削除する場合は、コントローラ注入が完全に外れていることを確認してから zzz_ 化する。
    /**
     * @return array<string, array{
     *   attendance_checked:bool,
     *   work_in_num:float,
     *   absence_num:float,
     *   work_holiday_num:float,
     *   work_time:float,
     *   holiday_true:float,
     *   overtime:float,
     *   night_over_time:float,
     *   holiday_work_time:float,
     *   late_early_time:float
     * }>
     */
    public function summaryMap(int $year, int $month): array
    {
        $query = DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards')
            ->select([
                DB::raw('LTRIM(RTRIM(staff_name)) as time_card_key'),
                'work_type',
                'work_type_time',
                'paid_leave_used',
                'shift_start',
                'shift_leave',
                'shift_break_out',
                'shift_end',
                'actual_start',
                'actual_leave',
                'actual_break_out',
                'actual_end',
                'change_start',
                'change_leave',
                'change_break_out',
                'change_end',
                'overtime',
            ])
            ->whereRaw('YEAR([work_date]) = ?', [$year])
            ->whereRaw('MONTH([work_date]) = ?', [$month])
            ->whereNotNull('staff_name')
            ->whereRaw('LTRIM(RTRIM(staff_name)) <> ?', ['']);

        $hasAttendanceChecked = Schema::connection('sqlsrv')->hasColumn('mx_time_cards', 'attendance_checked');
        if ($hasAttendanceChecked) {
            $query->addSelect('attendance_checked');
        }

        if (Schema::connection('sqlsrv')->hasColumn('mx_time_cards', 'night_overtime')) {
            $query->addSelect('night_overtime');
        }

        $rows = $query->get();

        $map = [];
        foreach ($rows as $row) {
            $timeCardKey = trim((string) ($row->time_card_key ?? ''));
            if ($timeCardKey === '') {
                continue;
            }

            $map[$timeCardKey] ??= [
                'attendance_checked' => true,
                'work_in_num' => 0.0,
                'absence_num' => 0.0,
                'work_holiday_num' => 0.0,
                'work_time' => 0.0,
                'holiday_true' => 0.0,
                'overtime' => 0.0,
                'night_over_time' => 0.0,
                'holiday_work_time' => 0.0,
                'late_early_time' => 0.0,
            ];

            if ($hasAttendanceChecked && ((int) ($row->attendance_checked ?? 0)) !== 1) {
                $map[$timeCardKey]['attendance_checked'] = false;
            }

            $workType = trim((string) ($row->work_type ?? ''));
            $workTypeTime = (float) ($row->work_type_time ?? 0);
            $hasChangeRecord = $this->hasAnyTimeValue([
                $row->change_start ?? null,
                $row->change_leave ?? null,
                $row->change_break_out ?? null,
                $row->change_end ?? null,
            ]);
            $shiftScheduled = $this->scheduledHours(
                $row->shift_start ?? null,
                $row->shift_leave ?? null,
                $row->shift_break_out ?? null,
                $row->shift_end ?? null,
            );
            $changeScheduled = $this->scheduledHours(
                $row->change_start ?? null,
                $row->change_leave ?? null,
                $row->change_break_out ?? null,
                $row->change_end ?? null,
            );

            if ($hasChangeRecord || $shiftScheduled > 0) {
                $map[$timeCardKey]['work_in_num'] += 1;
            }
            if ($workType === '欠勤') {
                $map[$timeCardKey]['absence_num'] += 1;
            }
            if ($workType === '休出') {
                $map[$timeCardKey]['work_holiday_num'] += 1;
                $map[$timeCardKey]['holiday_work_time'] += $workTypeTime;
            }
            if (in_array($workType, ['遅刻', '早退', '遅早'], true)) {
                $map[$timeCardKey]['late_early_time'] += $workTypeTime;
            }

            $map[$timeCardKey]['work_time'] += $changeScheduled > 0 ? $changeScheduled : $shiftScheduled;
            $map[$timeCardKey]['holiday_true'] += (float) ($row->paid_leave_used ?? 0);
            $map[$timeCardKey]['overtime'] += (float) ($row->overtime ?? 0);
            $map[$timeCardKey]['night_over_time'] += (float) ($row->night_overtime ?? 0);
        }

        return $map;
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
        return AttendanceTime::parseMinutes($value);
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

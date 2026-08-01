<?php

namespace App\Services\Admin\V2\Attendance;

use App\Support\AttendanceTime;

use Illuminate\Support\Facades\DB;

class AttendanceV2ListWorkdayService
{
    /**
     * @return array<string, float>
     */
    public function workdayMap(int $year, int $month): array
    {
        $rows = DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards')
            ->select([
                DB::raw('LTRIM(RTRIM(staff_name)) as time_card_key'),
                'shift_start',
                'shift_leave',
                'shift_break_out',
                'shift_end',
                'change_start',
                'change_leave',
                'change_break_out',
                'change_end',
            ])
            ->whereRaw('YEAR([work_date]) = ?', [$year])
            ->whereRaw('MONTH([work_date]) = ?', [$month])
            ->whereNotNull('staff_name')
            ->whereRaw('LTRIM(RTRIM(staff_name)) <> ?', [''])
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $timeCardKey = trim((string) ($row->time_card_key ?? ''));
            if ($timeCardKey === '') {
                continue;
            }

            $hasChange = $this->hasAnyTimeValue([
                $row->change_start ?? null,
                $row->change_leave ?? null,
                $row->change_break_out ?? null,
                $row->change_end ?? null,
            ]);
            $hasShift = $this->scheduledHours(
                $row->shift_start ?? null,
                $row->shift_leave ?? null,
                $row->shift_break_out ?? null,
                $row->shift_end ?? null,
            ) > 0;

            if ($hasChange || $hasShift) {
                $map[$timeCardKey] = ($map[$timeCardKey] ?? 0.0) + 1.0;
            }
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

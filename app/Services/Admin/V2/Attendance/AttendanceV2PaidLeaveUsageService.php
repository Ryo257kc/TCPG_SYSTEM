<?php

namespace App\Services\Admin\V2\Attendance;

use Illuminate\Support\Facades\DB;

class AttendanceV2PaidLeaveUsageService
{
    /**
     * @param list<string> $timeCardKeys
     */
    public function sync(string $staffId, int $year, int $month, array $timeCardKeys): void
    {
        $staffId = trim($staffId);
        $timeCardKeys = array_values(array_filter(array_map(
            static fn ($value): string => trim((string) $value),
            $timeCardKeys
        ), static fn (string $value): bool => $value !== ''));

        if ($staffId === '' || $year < 2000 || $month < 1 || $month > 12 || $timeCardKeys === []) {
            return;
        }

        $this->delete($staffId, $year, $month);

        if (!$this->isPaidLeaveTarget($staffId)) {
            return;
        }

        $usageRows = DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards')
            ->selectRaw('CONVERT(date, work_date) as date_use, SUM(ISNULL(paid_leave_used, 0)) as days_used')
            ->whereRaw('YEAR([work_date]) = ?', [$year])
            ->whereRaw('MONTH([work_date]) = ?', [$month])
            ->whereIn(DB::raw('LTRIM(RTRIM(staff_name))'), $timeCardKeys)
            ->whereRaw('ISNULL(paid_leave_used, 0) > 0')
            ->groupByRaw('CONVERT(date, work_date)')
            ->orderByRaw('CONVERT(date, work_date)')
            ->get();

        $insertRows = [];
        foreach ($usageRows as $usageRow) {
            $daysUsed = (float) ($usageRow->days_used ?? 0);
            if ($daysUsed <= 0) {
                continue;
            }

            $insertRows[] = [
                'staff_id' => $staffId,
                'date_use' => $usageRow->date_use,
                'days_used' => $daysUsed,
            ];
        }

        if ($insertRows !== []) {
            DB::connection('sqlsrv_payroll')
                ->table('dbo.mx_yukyu')
                ->insert($insertRows);
        }
    }

    public function delete(string $staffId, int $year, int $month): void
    {
        DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_yukyu')
            ->whereRaw('LTRIM(RTRIM(CAST(staff_id as nvarchar(50)))) = ?', [trim($staffId)])
            ->whereNotNull('date_use')
            ->whereRaw('YEAR([date_use]) = ?', [$year])
            ->whereRaw('MONTH([date_use]) = ?', [$month])
            ->delete();
    }

    private function isPaidLeaveTarget(string $staffId): bool
    {
        $value = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->whereRaw('LTRIM(RTRIM(CAST(staff_id as nvarchar(50)))) = ?', [$staffId])
            ->value('yukyu');

        return (bool) $value;
    }
}

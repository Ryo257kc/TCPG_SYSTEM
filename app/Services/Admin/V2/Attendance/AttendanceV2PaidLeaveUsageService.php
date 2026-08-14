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

    /**
     * sync()の複数スタッフ一括版。1スタッフにつき複数のtime_card_keys（過去の氏名変更等での
     * staff_name別名）が紐づく可能性があるため、time_card_key単位で取得した使用日数を
     * staff_id×日付単位に再集計してから保存する（sync()単体呼び出しと同じ集計結果になる）。
     *
     * @param array<string,list<string>> $timeCardKeysByStaffId
     */
    public function syncBulk(array $timeCardKeysByStaffId, int $year, int $month): void
    {
        $timeCardKeysByStaffId = array_filter(
            array_map(
                static fn (array $keys): array => array_values(array_filter(array_map(
                    static fn ($value): string => trim((string) $value),
                    $keys
                ), static fn (string $value): bool => $value !== ''))
                ,
                $timeCardKeysByStaffId
            ),
            static fn (array $keys): bool => $keys !== []
        );

        $staffIds = array_values(array_filter(array_map(
            static fn ($id): string => trim((string) $id),
            array_keys($timeCardKeysByStaffId)
        ), static fn (string $id): bool => $id !== ''));

        if ($staffIds === [] || $year < 2000 || $month < 1 || $month > 12) {
            return;
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_yukyu')
            ->whereIn(DB::raw('LTRIM(RTRIM(CAST(staff_id as nvarchar(50))))'), $staffIds)
            ->whereNotNull('date_use')
            ->whereRaw('YEAR([date_use]) = ?', [$year])
            ->whereRaw('MONTH([date_use]) = ?', [$month])
            ->delete();

        $targetStaffIds = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->whereIn(DB::raw('LTRIM(RTRIM(CAST(staff_id as nvarchar(50))))'), $staffIds)
            ->where('yukyu', true)
            ->pluck('staff_id')
            ->map(static fn ($id): string => trim((string) $id))
            ->flip()
            ->all();

        $timeCardKeyToStaffId = [];
        foreach ($timeCardKeysByStaffId as $staffId => $keys) {
            if (!isset($targetStaffIds[$staffId])) {
                continue;
            }
            foreach ($keys as $key) {
                $timeCardKeyToStaffId[$key] = $staffId;
            }
        }

        if ($timeCardKeyToStaffId === []) {
            return;
        }

        $usageRows = DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards')
            ->selectRaw('LTRIM(RTRIM(staff_name)) as staff_name, CONVERT(date, work_date) as date_use, SUM(ISNULL(paid_leave_used, 0)) as days_used')
            ->whereRaw('YEAR([work_date]) = ?', [$year])
            ->whereRaw('MONTH([work_date]) = ?', [$month])
            ->whereIn(DB::raw('LTRIM(RTRIM(staff_name))'), array_keys($timeCardKeyToStaffId))
            ->whereRaw('ISNULL(paid_leave_used, 0) > 0')
            ->groupByRaw('LTRIM(RTRIM(staff_name)), CONVERT(date, work_date)')
            ->get();

        // time_card_key単位の合計を、staff_id×日付単位へ再集計する。
        $sumsByStaffDate = [];
        foreach ($usageRows as $usageRow) {
            $staffName = trim((string) ($usageRow->staff_name ?? ''));
            $staffId = $timeCardKeyToStaffId[$staffName] ?? null;
            if ($staffId === null) {
                continue;
            }

            $key = $staffId . '|' . $usageRow->date_use;
            $sumsByStaffDate[$key] = ($sumsByStaffDate[$key] ?? 0.0) + (float) ($usageRow->days_used ?? 0);
        }

        $insertRows = [];
        foreach ($sumsByStaffDate as $key => $daysUsed) {
            if ($daysUsed <= 0) {
                continue;
            }

            [$staffId, $dateUse] = explode('|', $key, 2);
            $insertRows[] = [
                'staff_id' => $staffId,
                'date_use' => $dateUse,
                'days_used' => $daysUsed,
            ];
        }

        foreach (array_chunk($insertRows, 300) as $chunk) {
            DB::connection('sqlsrv_payroll')->table('dbo.mx_yukyu')->insert($chunk);
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

<?php

namespace App\Services\Admin\V2\PaidLeave;

use Illuminate\Support\Facades\DB;

class PaidLeaveV2RemainingService
{
    public function remainingDays(string $staffId): float
    {
        $staffId = trim($staffId);
        if ($staffId === '') {
            return 0.0;
        }

        $row = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_yukyu')
            ->whereRaw('LTRIM(RTRIM(CAST(staff_id as nvarchar(50)))) = ?', [$staffId])
            ->selectRaw('
                SUM(ISNULL(remaining_day, 0)) AS grant_total,
                SUM(ISNULL(days_used, 0)) AS used_total,
                SUM(ISNULL(lost_num, 0)) AS lost_total
            ')
            ->first();

        $remaining = $this->num($row->grant_total ?? 0)
            - $this->num($row->used_total ?? 0)
            - $this->num($row->lost_total ?? 0);

        return max(0.0, $remaining);
    }

    /**
     * remainingDays()の複数スタッフ一括版。
     *
     * @param list<string> $staffIds
     * @return array<string,float> staff_id => 残日数
     */
    public function remainingDaysBulk(array $staffIds): array
    {
        $staffIds = array_values(array_filter(array_map(
            static fn ($id): string => trim((string) $id),
            $staffIds
        ), static fn (string $id): bool => $id !== ''));

        if ($staffIds === []) {
            return [];
        }

        $rows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_yukyu')
            ->whereIn(DB::raw('LTRIM(RTRIM(CAST(staff_id as nvarchar(50))))'), $staffIds)
            ->selectRaw('
                LTRIM(RTRIM(CAST(staff_id as nvarchar(50)))) as staff_id,
                SUM(ISNULL(remaining_day, 0)) AS grant_total,
                SUM(ISNULL(days_used, 0)) AS used_total,
                SUM(ISNULL(lost_num, 0)) AS lost_total
            ')
            ->groupByRaw('LTRIM(RTRIM(CAST(staff_id as nvarchar(50))))')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $remaining = $this->num($row->grant_total ?? 0)
                - $this->num($row->used_total ?? 0)
                - $this->num($row->lost_total ?? 0);
            $result[trim((string) $row->staff_id)] = max(0.0, $remaining);
        }

        return $result;
    }

    private function num(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $text = str_replace([',', ' '], '', trim((string) $value));

        return is_numeric($text) ? (float) $text : 0.0;
    }
}

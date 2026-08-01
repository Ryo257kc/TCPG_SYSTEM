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

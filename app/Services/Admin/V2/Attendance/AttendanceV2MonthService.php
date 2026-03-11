<?php

namespace App\Services\Admin\V2\Attendance;

use Illuminate\Support\Facades\DB;

class AttendanceV2MonthService
{
    /** @return list<string> */
    public function availableMonths(): array
    {
        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->selectRaw('YEAR([supply_month]) as y, MONTH([supply_month]) as m')
            ->where('bonus', 0)
            ->whereNotNull('supply_month')
            ->groupByRaw('YEAR([supply_month]), MONTH([supply_month])')
            ->orderByRaw('YEAR([supply_month]) desc, MONTH([supply_month]) desc')
            ->get()
            ->map(fn ($r) => sprintf('%04d-%02d', (int) $r->y, (int) $r->m))
            ->values()
            ->all();
    }

    public function normalize(string $selectedMonth, array $availableMonths): string
    {
        $defaultMonth = now('Asia/Tokyo')->startOfMonth()->subMonth()->format('Y-m');

        if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            return $availableMonths[0] ?? $defaultMonth;
        }

        return $selectedMonth;
    }
}

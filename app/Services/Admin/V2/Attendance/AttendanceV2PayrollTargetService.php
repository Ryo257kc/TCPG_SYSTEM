<?php

namespace App\Services\Admin\V2\Attendance;

use Illuminate\Support\Facades\DB;

class AttendanceV2PayrollTargetService
{
    public function resolvePaymentDate(int $year, int $month): string
    {
        if ($year < 2000 || $month < 1 || $month > 12) {
            return '';
        }

        [$targetYear, $targetMonth] = $this->targetPayrollYearMonth($year, $month);
        $value = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$targetYear])
            ->whereRaw('MONTH([supply_month]) = ?', [$targetMonth])
            ->whereNotNull('supply_month')
            ->orderBy('supply_month')
            ->value('supply_month');

        if ($value === null) {
            return '';
        }

        $timestamp = strtotime((string) $value);
        return $timestamp === false ? '' : date('Y-m-d', $timestamp);
    }

    /**
     * @return array{0:int,1:int}
     */
    public function targetPayrollYearMonth(int $year, int $month): array
    {
        if ($month >= 12) {
            return [$year + 1, 1];
        }

        return [$year, $month + 1];
    }
}

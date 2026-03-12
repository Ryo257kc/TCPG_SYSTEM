<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;

class PayrollV2EmploymentInsuranceService
{
    public function recalculate(string $staffId, int $year, int $month): int
    {
        $staffId = trim($staffId);
        if ($staffId === '' || $year < 2000 || $month < 1 || $month > 12) {
            return 0;
        }

        $conn = DB::connection('sqlsrv_payroll');

        $current = $conn->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month])
            ->whereRaw('LTRIM(RTRIM([kyuyo_staff_id])) = ?', [$staffId])
            ->orderByDesc('kyuyo_sho_no')
            ->first(['kyuyo_sho_no', 'rouho_target_sum']);

        if (!$current || !isset($current->kyuyo_sho_no)) {
            return 0;
        }

        $firstDay = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $rouho = $conn->table('dbo.mx_rouho')
            ->whereNotNull('rou_apply_date')
            ->where('rou_apply_date', '<=', $firstDay)
            ->orderByDesc('rou_apply_date')
            ->first(['general_st']);

        $generalSt = (float) ($rouho->general_st ?? 0);
        $target = (float) ($current->rouho_target_sum ?? 0);

        $staff = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->whereRaw('LTRIM(RTRIM([staff_id])) = ?', [$staffId])
            ->first(['staff_division', 'koyou']);

        $division = trim((string) ($staff->staff_division ?? ''));
        $hasKoyou = ((int) ($staff->koyou ?? 0)) === 1;

        $isExcluded =
            $staffId === '001'
            || mb_strpos($division, '管理責任者') !== false
            || mb_strpos($division, '業務委託') !== false
            || !$hasKoyou;

        $koyou = 0;
        if (!$isExcluded && $generalSt > 0 && $target > 0) {
            $raw = $target * ($generalSt / 1000.0);
            $floor = (int) floor($raw);
            $fraction = $raw - $floor;
            $koyou = $fraction <= 0.5 ? $floor : (int) round($raw, 0, PHP_ROUND_HALF_UP);
        }

        return $conn->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_sho_no', (int) $current->kyuyo_sho_no)
            ->update(['koyou' => $koyou]);
    }
}
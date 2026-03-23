<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
            ->first();

        if (!$current || !isset($current->kyuyo_sho_no)) {
            return 0;
        }

        $firstDay = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $staff = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->whereRaw('LTRIM(RTRIM([staff_id])) = ?', [$staffId])
            ->first(['staff_division', 'koyou', 'section']);

        $companyId = $this->resolveCompanyId($staffId);

        $rouhoQuery = $conn->table('dbo.mx_rouho')
            ->whereNotNull('rou_apply_date')
            ->where('rou_apply_date', '<=', $firstDay);
        if ($companyId !== '' && $this->hasPayrollColumn('mx_rouho', 'office_no')) {
            $rouhoQuery->whereRaw('LTRIM(RTRIM(CAST(office_no AS nvarchar(50)))) = ?', [$companyId]);
        }
        $rouho = $rouhoQuery
            ->orderByDesc('rou_apply_date')
            ->first();

        $syahoQuery = $conn->table('dbo.mx_syaho')
            ->whereNotNull('jidou_apply_date')
            ->where('jidou_apply_date', '<=', $firstDay);
        if ($companyId !== '' && $this->hasPayrollColumn('mx_syaho', 'office_no')) {
            $syahoQuery->whereRaw('LTRIM(RTRIM(CAST(office_no AS nvarchar(50)))) = ?', [$companyId]);
        }
        $syaho = $syahoQuery
            ->orderByDesc('jidou_apply_date')
            ->first(['jidou_kyuyo']);

        $rouhoRow = (array) ($rouho ?? []);
        $generalSt = (float) ($rouhoRow['general_st'] ?? 0);
        $generalOf = (float) ($rouhoRow['general_of'] ?? 0);
        $rousaiRitu = $this->pickFirstNumericValue($rouhoRow, [
            'rousai_ritu',
            'rousai_rate',
            'rousai',
            'rousai_general',
            'general_rousai',
        ]);
        $jidouRitu = (float) ($syaho->jidou_kyuyo ?? 0);
        $target = (float) ($current->rouho_target_sum ?? 0);

        $staffShahoRows = $conn->table('dbo.mx_staff_shou')
            ->whereRaw('LTRIM(RTRIM([staff_id])) = ?', [$staffId])
            ->orderByDesc('raise_year')
            ->get()
            ->map(static fn ($row): array => (array) $row)
            ->all();
        $staffShaho = $this->pickStaffShahoByMonth($staffShahoRows, $year, $month);

        $division = trim((string) ($staff->staff_division ?? ''));
        $hasKoyou = ((int) ($staff->koyou ?? 0)) === 1;
        $storeCode = trim((string) ($staff->section ?? ''));

        $isExcluded =
            $staffId === '001'
            || mb_strpos($division, '保育事業部') !== false
            || mb_strpos($division, '鍼灸整骨院') !== false
            || !$hasKoyou;

        $koyou = 0;
        $koyouOffice = 0;
        if (!$isExcluded && $generalSt > 0 && $target > 0) {
            $koyou = $this->roundInsuranceAmount($target * ($generalSt / 1000.0));
        }
        if (!$isExcluded && ($generalOf + $generalSt) > 0 && $target > 0) {
            $total = $this->roundInsuranceAmount($target * (($generalOf + $generalSt) / 1000.0));
            $koyouOffice = max(0, $total - $koyou);
        }

        $jidouBase = $this->pickFirstNumericValue($staffShaho, [
            'kounen_monthly_amo',
            'kounen_h',
            'kounen',
        ]);
        $jidouOffice = ($jidouRitu > 0 && $jidouBase > 0)
            ? (int) floor($jidouBase * ($jidouRitu / 1000.0))
            : 0;

        $rousaiBase = floor($target / 1000.0) * 1000.0;
        $rousaiRate = $storeCode === '003' ? 3.5 : $rousaiRitu;
        $rousaiOffice = ($rousaiRate > 0 && $rousaiBase > 0)
            ? (int) floor($rousaiBase * ($rousaiRate / 1000.0))
            : 0;

        return $conn->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_sho_no', (int) $current->kyuyo_sho_no)
            ->update([
                'koyou' => $koyou,
                'koyou_office' => $koyouOffice,
                'jidou_office' => $jidouOffice,
                'rousai_office' => $rousaiOffice,
            ]);
    }

    public function recalculateBonus(string $staffId, string $paymentDate): int
    {
        $staffId = trim($staffId);
        $paymentDate = trim($paymentDate);
        if ($staffId === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $paymentDate) !== 1) {
            return 0;
        }

        $conn = DB::connection('sqlsrv_payroll');

        $current = $conn->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 1)
            ->whereRaw('CONVERT(date, [supply_month]) = ?', [$paymentDate])
            ->whereRaw('LTRIM(RTRIM([kyuyo_staff_id])) = ?', [$staffId])
            ->orderByDesc('kyuyo_sho_no')
            ->first();

        if (!$current || !isset($current->kyuyo_sho_no)) {
            return 0;
        }

        $staff = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->whereRaw('LTRIM(RTRIM([staff_id])) = ?', [$staffId])
            ->first(['staff_division', 'koyou', 'section']);

        $companyId = $this->resolveCompanyId($staffId);

        $rouhoQuery = $conn->table('dbo.mx_rouho')
            ->whereNotNull('rou_apply_date')
            ->where('rou_apply_date', '<=', $paymentDate . ' 23:59:59');
        if ($companyId !== '' && $this->hasPayrollColumn('mx_rouho', 'office_no')) {
            $rouhoQuery->whereRaw('LTRIM(RTRIM(CAST(office_no AS nvarchar(50)))) = ?', [$companyId]);
        }
        $rouho = $rouhoQuery
            ->orderByDesc('rou_apply_date')
            ->first();

        $syahoQuery = $conn->table('dbo.mx_syaho')
            ->whereNotNull('jidou_apply_date')
            ->where('jidou_apply_date', '<=', $paymentDate . ' 23:59:59');
        if ($companyId !== '' && $this->hasPayrollColumn('mx_syaho', 'office_no')) {
            $syahoQuery->whereRaw('LTRIM(RTRIM(CAST(office_no AS nvarchar(50)))) = ?', [$companyId]);
        }
        $syaho = $syahoQuery
            ->orderByDesc('jidou_apply_date')
            ->first(['jidou_shoyo']);

        $rouhoRow = (array) ($rouho ?? []);
        $generalSt = (float) ($rouhoRow['general_st'] ?? 0);
        $generalOf = (float) ($rouhoRow['general_of'] ?? 0);
        $rousaiRitu = $this->pickFirstNumericValue($rouhoRow, [
            'rousai_ritu',
            'rousai_rate',
            'rousai',
            'rousai_general',
            'general_rousai',
        ]);
        $jidouRitu = (float) ($syaho->jidou_shoyo ?? 0);
        $target = (float) ($current->rouho_target_sum ?? 0);

        $staffShahoRows = $conn->table('dbo.mx_staff_shou')
            ->whereRaw('LTRIM(RTRIM([staff_id])) = ?', [$staffId])
            ->orderByDesc('raise_year')
            ->get()
            ->map(static fn ($row): array => (array) $row)
            ->all();
        $year = (int) substr($paymentDate, 0, 4);
        $month = (int) substr($paymentDate, 5, 2);
        $staffShaho = $this->pickStaffShahoByMonth($staffShahoRows, $year, $month);

        $division = trim((string) ($staff->staff_division ?? ''));
        $hasKoyou = ((int) ($staff->koyou ?? 0)) === 1;
        $storeCode = trim((string) ($staff->section ?? ''));

        $isExcluded =
            $staffId === '001'
            || mb_strpos($division, '菫晁ご莠区･ｭ驛ｨ') !== false
            || mb_strpos($division, '骰ｼ轣ｸ謨ｴ鬪ｨ髯｢') !== false
            || !$hasKoyou;

        $koyou = 0;
        $koyouOffice = 0;
        if (!$isExcluded && $generalSt > 0 && $target > 0) {
            $koyou = $this->roundInsuranceAmount($target * ($generalSt / 1000.0));
        }
        if (!$isExcluded && ($generalOf + $generalSt) > 0 && $target > 0) {
            $total = $this->roundInsuranceAmount($target * (($generalOf + $generalSt) / 1000.0));
            $koyouOffice = max(0, $total - $koyou);
        }

        $jidouBase = floor($target / 1000.0) * 1000.0;
        $jidouOffice = ($jidouRitu > 0 && $jidouBase > 0)
            ? (int) floor($jidouBase * ($jidouRitu / 1000.0))
            : 0;

        $rousaiBase = floor($target / 1000.0) * 1000.0;
        $rousaiRate = $storeCode === '003' ? 3.5 : $rousaiRitu;
        $rousaiOffice = ($rousaiRate > 0 && $rousaiBase > 0)
            ? (int) floor($rousaiBase * ($rousaiRate / 1000.0))
            : 0;

        return $conn->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_sho_no', (int) $current->kyuyo_sho_no)
            ->update([
                'koyou' => $koyou,
                'koyou_office' => $koyouOffice,
                'jidou_office' => $jidouOffice,
                'rousai_office' => $rousaiOffice,
            ]);
    }

    private function roundInsuranceAmount(float $raw): int
    {
        $floor = (int) floor($raw);
        $fraction = $raw - $floor;

        return $fraction <= 0.5 ? $floor : (int) round($raw, 0, PHP_ROUND_HALF_UP);
    }

    private function hasPayrollColumn(string $table, string $column): bool
    {
        try {
            return Schema::connection('sqlsrv_payroll')->hasColumn($table, $column)
                || Schema::connection('sqlsrv_payroll')->hasColumn('dbo.' . $table, $column);
        } catch (\Throwable) {
            return false;
        }
    }

    private function resolveCompanyId(string $staffId): string
    {
        $row = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs as s')
            ->leftJoin('dbo.mx_stores as st', 'st.store_code', '=', 's.section')
            ->whereRaw('LTRIM(RTRIM(s.staff_id)) = ?', [$staffId])
            ->first(['st.company_id']);

        return trim((string) ($row->company_id ?? ''));
    }

    /**
     * @param array<string,mixed> $row
     * @param list<string> $keys
     */
    private function pickFirstNumericValue(array $row, array $keys): float
    {
        foreach ($keys as $key) {
            $value = $row[$key] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return 0.0;
    }

    /**
     * @param list<array<string,mixed>> $rows
     * @return array<string,mixed>
     */
    private function pickStaffShahoByMonth(array $rows, int $year, int $month): array
    {
        if ($rows === []) {
            return [];
        }

        $targetSep = $this->targetSeptember($year, $month);
        if ($targetSep === null) {
            return $rows[0];
        }

        $anchor = null;
        foreach ($rows as $row) {
            $date = $this->toDate($row['raise_year'] ?? null);
            if ($date === null) {
                continue;
            }

            if ((int) $date->format('Y') === (int) $targetSep->format('Y') && (int) $date->format('n') === 9) {
                return $row;
            }

            if ($date <= $targetSep) {
                $anchor = $row;
                break;
            }
        }

        return $anchor ?? $rows[0];
    }

    private function targetSeptember(int $year, int $month): ?\DateTimeImmutable
    {
        if ($year < 2000 || $month < 1 || $month > 12) {
            return null;
        }

        $targetYear = $month >= 10 ? $year : ($year - 1);

        return new \DateTimeImmutable(sprintf('%04d-09-30 23:59:59', $targetYear));
    }

    private function toDate(mixed $value): ?\DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        $timestamp = strtotime($text);
        if ($timestamp === false) {
            return null;
        }

        return (new \DateTimeImmutable())->setTimestamp($timestamp);
    }
}

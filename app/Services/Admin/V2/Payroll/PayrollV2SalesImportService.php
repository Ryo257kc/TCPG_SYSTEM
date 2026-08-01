<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;

class PayrollV2SalesImportService
{
    public function __construct(
        private readonly PayrollV2UpdateService $updateService,
    ) {}

    /**
     * 売上取込ボタンで使う往診売上集計の正本。
     *
     * 給与月の前月分を hv_nippou から集計し、給与明細の売上列へ入れる形に整える。
     *
     * @param list<string> $staffIds
     * @return array<string,array<string,int|float>>
     */
    public function summaries(array $staffIds, int $payrollYear, int $payrollMonth): array
    {
        $staffIds = $this->normalizeStaffIds($staffIds);
        if ($staffIds === [] || $payrollYear < 2000 || $payrollMonth < 1 || $payrollMonth > 12) {
            return [];
        }

        $summaries = [];
        foreach ($this->totalsByStaff($staffIds, $payrollYear, $payrollMonth) as $staffId => $row) {
            $summaries[$staffId] = [
                'peple_num' => (int) ($row['people_count'] ?? 0),
                'km' => $this->num($row['distance_total'] ?? 0),
                'kitazaike' => (int) round($this->num($row['kitazaike_total'] ?? 0)),
                'higashi_kakogawa' => (int) round($this->num($row['higashi_kakogawa_total'] ?? 0)),
                'tsubasa_harima' => (int) round($this->num($row['harima_total'] ?? 0)),
                'sakura_hari' => (int) round($this->num($row['sakura_total'] ?? 0)),
                'orita_hari' => (int) round($this->num($row['orita_total'] ?? 0)),
                'miyamoto_hari' => (int) round($this->num($row['miyamoto_total'] ?? 0)),
                'yokoi_hari' => (int) round($this->num($row['yokoi_total'] ?? 0)),
                'own_cost' => (int) round($this->num($row['private_fee_total'] ?? 0)),
                'unpaid_amo' => (int) round($this->num($row['uncollected_total'] ?? 0)),
            ];
        }

        return $summaries;
    }

    /**
     * @param list<string> $staffIds
     * @return list<array<string,int|float|string>>
     */
    public function detailRows(array $staffIds, int $payrollYear, int $payrollMonth): array
    {
        $staffIds = $this->normalizeStaffIds($staffIds);
        if ($staffIds === [] || $payrollYear < 2000 || $payrollMonth < 1 || $payrollMonth > 12) {
            return [];
        }

        [$salesYear, $salesMonth] = $this->salesYearMonth($payrollYear, $payrollMonth);
        $fromDate = sprintf('%04d-%02d-01', $salesYear, $salesMonth);
        $nextDate = date('Y-m-d', strtotime(date('Y-m-t', strtotime($fromDate)) . ' +1 day'));

        $rows = DB::connection('sqlsrv')
            ->table('dbo.hv_nippou')
            ->selectRaw("
                LTRIM(RTRIM(CAST(staff_name as nvarchar(20)))) as staff_id,
                CONVERT(date, treatment_date) as treatment_date,
                SUM(ISNULL(uncollected_amount, 0)) as uncollected_total,
                COUNT(CASE WHEN is_no_treatment = 0 THEN patient_id ELSE NULL END) as patient_count,
                COUNT(CASE WHEN is_same_day_duplicate = 1 THEN patient_id ELSE NULL END) as duplicate_count,
                SUM(ISNULL(distance, 0)) as distance_total,
                SUM(ISNULL(private_fee, 0)) as private_fee_total,
                SUM(CASE WHEN daily_report_store_name IN (N'北在家', N'社内施術', N'柔整') THEN ISNULL(sales_amount, 0) * 100 - ISNULL(private_fee, 0) ELSE 0 END) as kitazaike_total,
                SUM(CASE WHEN daily_report_store_name = N'東加古川' THEN ISNULL(sales_amount, 0) * 100 - ISNULL(private_fee, 0) ELSE 0 END) as higashi_kakogawa_total,
                SUM(CASE WHEN daily_report_store_name = N'播磨町' THEN ISNULL(sales_amount, 0) * 100 - ISNULL(private_fee, 0) ELSE 0 END) as harima_total,
                SUM(CASE WHEN daily_report_store_name = N'さくら鍼灸' THEN ISNULL(sales_amount, 0) * 100 - ISNULL(private_fee, 0) ELSE 0 END) as sakura_total,
                SUM(CASE WHEN daily_report_store_name = N'織田鍼灸' THEN ISNULL(sales_amount, 0) * 100 - ISNULL(private_fee, 0) ELSE 0 END) as orita_total,
                SUM(CASE WHEN daily_report_store_name = N'宮本鍼灸' THEN ISNULL(sales_amount, 0) * 100 - ISNULL(private_fee, 0) ELSE 0 END) as miyamoto_total,
                SUM(CASE WHEN daily_report_store_name = N'横井鍼灸' THEN ISNULL(sales_amount, 0) * 100 - ISNULL(private_fee, 0) ELSE 0 END) as yokoi_total
            ")
            ->where('treatment_date', '>=', $fromDate)
            ->where('treatment_date', '<', $nextDate)
            ->whereIn(DB::raw('LTRIM(RTRIM(CAST(staff_name as nvarchar(20))))'), $staffIds)
            ->groupByRaw('LTRIM(RTRIM(CAST(staff_name as nvarchar(20)))), CONVERT(date, treatment_date)')
            ->orderByRaw('LTRIM(RTRIM(CAST(staff_name as nvarchar(20))))')
            ->orderByRaw('CONVERT(date, treatment_date)')
            ->get();

        return $rows->map(function ($row): array {
            $patientCount = (int) $this->num($row->patient_count ?? 0);
            $duplicateCount = (int) $this->num($row->duplicate_count ?? 0);
            $kitazaikeTotal = $this->num($row->kitazaike_total ?? 0);
            $higashiKakogawaTotal = $this->num($row->higashi_kakogawa_total ?? 0);
            $harimaTotal = $this->num($row->harima_total ?? 0);
            $sakuraTotal = $this->num($row->sakura_total ?? 0);
            $oritaTotal = $this->num($row->orita_total ?? 0);
            $miyamotoTotal = $this->num($row->miyamoto_total ?? 0);
            $yokoiTotal = $this->num($row->yokoi_total ?? 0);
            $privateFeeTotal = $this->num($row->private_fee_total ?? 0);
            $uncollectedTotal = $this->num($row->uncollected_total ?? 0);
            $storeTotal = $kitazaikeTotal + $higashiKakogawaTotal + $harimaTotal + $privateFeeTotal + $uncollectedTotal;
            $storeTotalWithHari = $this->homeVisitSalesTotal([
                'kitazaike' => $kitazaikeTotal,
                'higashi_kakogawa' => $higashiKakogawaTotal,
                'tsubasa_harima' => $harimaTotal,
                'sakura_hari' => $sakuraTotal,
                'orita_hari' => $oritaTotal,
                'miyamoto_hari' => $miyamotoTotal,
                'yokoi_hari' => $yokoiTotal,
                'own_cost' => $privateFeeTotal,
                'unpaid_amo' => $uncollectedTotal,
            ]);

            return [
                'staff_id' => trim((string) ($row->staff_id ?? '')),
                'treatment_date' => (string) ($row->treatment_date ?? ''),
                'patient_count' => $patientCount,
                'duplicate_count' => $duplicateCount,
                'people_count' => max(0, $patientCount - $duplicateCount),
                'distance_total' => $this->num($row->distance_total ?? 0),
                'kitazaike_total' => $kitazaikeTotal,
                'higashi_kakogawa_total' => $higashiKakogawaTotal,
                'harima_total' => $harimaTotal,
                'sakura_total' => $sakuraTotal,
                'orita_total' => $oritaTotal,
                'miyamoto_total' => $miyamotoTotal,
                'yokoi_total' => $yokoiTotal,
                'private_fee_total' => $privateFeeTotal,
                'uncollected_total' => $uncollectedTotal,
                'store_total' => $storeTotal,
                'store_total_with_hari' => $storeTotalWithHari,
            ];
        })->filter(static fn(array $row): bool => $row['staff_id'] !== '')->values()->all();
    }

    /**
     * @param list<string> $staffIds
     * @return array<string,array<string,int|float|string>>
     */
    public function totalsByStaff(array $staffIds, int $payrollYear, int $payrollMonth): array
    {
        $totals = [];
        foreach ($this->detailRows($staffIds, $payrollYear, $payrollMonth) as $row) {
            $staffId = (string) $row['staff_id'];
            if (!isset($totals[$staffId])) {
                $totals[$staffId] = [
                    'staff_id' => $staffId,
                    'people_count' => 0,
                    'distance_total' => 0.0,
                    'kitazaike_total' => 0.0,
                    'higashi_kakogawa_total' => 0.0,
                    'harima_total' => 0.0,
                    'sakura_total' => 0.0,
                    'orita_total' => 0.0,
                    'miyamoto_total' => 0.0,
                    'yokoi_total' => 0.0,
                    'private_fee_total' => 0.0,
                    'uncollected_total' => 0.0,
                    'store_total' => 0.0,
                    'store_total_with_hari' => 0.0,
                ];
            }

            foreach (array_keys($totals[$staffId]) as $key) {
                if ($key === 'staff_id') {
                    continue;
                }
                $totals[$staffId][$key] += $row[$key];
            }
        }

        return $totals;
    }

    /** @param array<string,mixed> $row */
    public function homeVisitSalesTotal(array $row): float
    {
        return $this->num($row['kitazaike'] ?? $row['kitazaike_total'] ?? 0)
            + $this->num($row['higashi_kakogawa'] ?? $row['higashi_kakogawa_total'] ?? 0)
            + $this->num($row['tsubasa_harima'] ?? $row['harima_total'] ?? 0)
            + $this->num($row['sakura_hari'] ?? $row['sakura_total'] ?? 0)
            + $this->num($row['orita_hari'] ?? $row['orita_total'] ?? 0)
            + $this->num($row['miyamoto_hari'] ?? $row['miyamoto_total'] ?? 0)
            + $this->num($row['yokoi_hari'] ?? $row['yokoi_total'] ?? 0)
            + $this->num($row['own_cost'] ?? $row['private_fee_total'] ?? 0)
            + $this->num($row['unpaid_amo'] ?? $row['uncollected_total'] ?? 0);
    }

    /**
     * @param list<string> $staffIds
     * @return array{updated:int,locked:int,missing:int,no_data:int}
     */
    public function reflect(array $staffIds, int $payrollYear, int $payrollMonth, string $companyName = ''): array
    {
        $staffIds = $this->normalizeStaffIds($staffIds);
        if ($staffIds === [] || $payrollYear < 2000 || $payrollMonth < 1 || $payrollMonth > 12) {
            return ['updated' => 0, 'locked' => 0, 'missing' => 0, 'no_data' => 0];
        }

        $summaries = $this->summaries($staffIds, $payrollYear, $payrollMonth);
        $payrollRows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$payrollYear])
            ->whereRaw('MONTH([supply_month]) = ?', [$payrollMonth])
            ->whereIn(DB::raw('LTRIM(RTRIM([kyuyo_staff_id]))'), $staffIds)
            ->get(['kyuyo_staff_id', 'edit_lock']);

        $payrollMap = [];
        foreach ($payrollRows as $row) {
            $staffId = trim((string) ($row->kyuyo_staff_id ?? ''));
            if ($staffId !== '' && !isset($payrollMap[$staffId])) {
                $payrollMap[$staffId] = (int) ($row->edit_lock ?? 0);
            }
        }

        $result = ['updated' => 0, 'locked' => 0, 'missing' => 0, 'no_data' => 0];
        foreach ($staffIds as $staffId) {
            if (!array_key_exists($staffId, $payrollMap)) {
                $result['missing']++;
                continue;
            }
            if ((int) $payrollMap[$staffId] === 1) {
                $result['locked']++;
                continue;
            }
            if (!isset($summaries[$staffId])) {
                $result['no_data']++;
                continue;
            }

            $this->updateService->save($staffId, $payrollYear, $payrollMonth, $summaries[$staffId], $companyName);
            $result['updated']++;
        }

        return $result;
    }

    /** @param list<mixed> $staffIds @return list<string> */
    private function normalizeStaffIds(array $staffIds): array
    {
        $out = [];
        foreach ($staffIds as $staffId) {
            $value = trim((string) $staffId);
            if ($value !== '') {
                $out[$value] = true;
            }
        }

        return array_keys($out);
    }

    /** @return array{0:int,1:int} */
    private function salesYearMonth(int $payrollYear, int $payrollMonth): array
    {
        if ($payrollMonth <= 1) {
            return [$payrollYear - 1, 12];
        }

        return [$payrollYear, $payrollMonth - 1];
    }

    private function num(mixed $value): float
    {
        if ($value === null) {
            return 0.0;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = str_replace([',', ' '], '', trim((string) $value));
        return is_numeric($value) ? (float) $value : 0.0;
    }
}

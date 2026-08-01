<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;

class PayrollV2HomeVisitAllowanceService
{
    private const MANAGER_IDS = ['002', '013'];

    public function __construct(
        private readonly PayrollV2UpdateService $updateService,
        private readonly PayrollV2SalesImportService $salesImportService,
    ) {}

    /**
     * 往診手当ボタンの正本。
     *
     * 歩合手当と管理手当だけを計算し、保存は PayrollV2UpdateService::save() に任せる。
     * 往診売上の内訳式を変える場合は homeVisitSalesTotal() を見る。
     *
     * @return array{updated:int,commission_allowance:int,manager_allowance:int,home_visit_sales:int}
     */
    public function calculate(string $staffId, int $year, int $month, string $companyName = ''): array
    {
        $staffId = $this->normalizeStaffId($staffId);
        if ($staffId === '' || $year < 2000 || $month < 1 || $month > 12) {
            return ['updated' => 0, 'commission_allowance' => 0, 'manager_allowance' => 0, 'home_visit_sales' => 0];
        }

        $staff = $this->staff($staffId);
        $payrollRows = $this->payrollRows($year, $month, $companyName);
        if (!isset($payrollRows[$staffId])) {
            return ['updated' => 0, 'commission_allowance' => 0, 'manager_allowance' => 0, 'home_visit_sales' => 0];
        }

        $homeVisitSales = $this->homeVisitSalesTotal($payrollRows[$staffId]);
        $commissionAllowance = $this->commissionAllowance($homeVisitSales, $staff);
        $managerAllowance = $this->managerAllowance($staffId, $homeVisitSales, $payrollRows);

        $updated = $this->updateService->save($staffId, $year, $month, [
            'allowance_amo_3' => $managerAllowance,
            'allowance_amo_4' => $commissionAllowance,
        ], $companyName);

        return [
            'updated' => $updated,
            'commission_allowance' => $commissionAllowance,
            'manager_allowance' => $managerAllowance,
            'home_visit_sales' => (int) round($homeVisitSales, 0),
        ];
    }

    /** @return array<string,mixed> */
    private function staff(string $staffId): array
    {
        return (array) (DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->select(['staff_id', 'staff_division', 'percentage_1', 'percentage_2'])
            ->whereRaw("RIGHT('000' + LTRIM(RTRIM(CAST(staff_id as nvarchar(50)))), 3) = ?", [$staffId])
            ->first() ?? []);
    }

    /** @return array<string,array<string,mixed>> */
    private function payrollRows(int $year, int $month, string $companyName): array
    {
        $query = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month])
            ->select([
                DB::raw("RIGHT('000' + LTRIM(RTRIM(CAST(kyuyo_staff_id as nvarchar(50)))), 3) as staff_id"),
                'kitazaike',
                'higashi_kakogawa',
                'tsubasa_harima',
                'sakura_hari',
                'orita_hari',
                'miyamoto_hari',
                'yokoi_hari',
                'own_cost',
                'unpaid_amo',
            ]);

        $companyName = trim($companyName);
        $companyStaffIds = $this->companyStaffIds($companyName);
        if ($companyName !== '' && $companyStaffIds === []) {
            return [];
        }
        if ($companyStaffIds !== []) {
            $query->whereIn(DB::raw("RIGHT('000' + LTRIM(RTRIM(CAST(kyuyo_staff_id as nvarchar(50)))), 3)"), $companyStaffIds);
        }

        $rows = [];
        foreach ($query->get() as $row) {
            $id = trim((string) ($row->staff_id ?? ''));
            if ($id !== '') {
                $rows[$id] = (array) $row;
            }
        }

        return $rows;
    }

    /** @return list<string> */
    private function companyStaffIds(string $companyName): array
    {
        $companyName = trim($companyName);
        if ($companyName === '') {
            return [];
        }

        return DB::connection('sqlsrv')
            ->table('dbo.mx_staffs as s')
            ->leftJoin('dbo.mx_stores as st', 'st.store_code', '=', 's.section')
            ->leftJoin('dbo.mx_companies as c', 'c.company_id', '=', 'st.company_id')
            ->where('c.company_name', $companyName)
            ->whereNotNull('s.staff_id')
            ->pluck('s.staff_id')
            ->map(fn($value): string => $this->normalizeStaffId((string) $value))
            ->filter(static fn(string $value): bool => $value !== '')
            ->values()
            ->all();
    }

    /** @param array<string,mixed> $row */
    private function homeVisitSalesTotal(array $row): float
    {
        return $this->salesImportService->homeVisitSalesTotal($row);
    }

    /** @param array<string,mixed> $staff */
    private function commissionAllowance(float $homeVisitSales, array $staff): int
    {
        $division = trim((string) ($staff['staff_division'] ?? ''));
        if (str_contains($division, '業務委託')) {
            $rate = $this->num($staff['percentage_1'] ?? 0) + $this->num($staff['percentage_2'] ?? 0);
            return (int) round($homeVisitSales * $rate, 0);
        }

        if ($homeVisitSales < 600000) {
            return 0;
        }

        return (int) floor(($homeVisitSales - 600000) * 0.3 / 2);
    }

    /** @param array<string,array<string,mixed>> $payrollRows */
    private function managerAllowance(string $staffId, float $homeVisitSales, array $payrollRows): int
    {
        $allowance = 0.0;

        if (in_array($staffId, self::MANAGER_IDS, true) && $homeVisitSales > 1000000) {
            foreach ($payrollRows as $rowStaffId => $row) {
                if (in_array($rowStaffId, self::MANAGER_IDS, true)) {
                    continue;
                }

                $targetSales = $this->homeVisitSalesTotal($row);
                if ($targetSales > 600000) {
                    $allowance += $targetSales * 0.02;
                }
            }
        }

        return (int) floor($allowance);
    }

    private function normalizeStaffId(string $staffId): string
    {
        $staffId = trim($staffId);
        if ($staffId !== '' && preg_match('/^\d+$/', $staffId) === 1) {
            return str_pad($staffId, 3, '0', STR_PAD_LEFT);
        }

        return $staffId;
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

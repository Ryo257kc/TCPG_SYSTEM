<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;

class PayrollV2RecalculateService
{
    public function __construct(
        private readonly PayrollV2KihonService $kihonService,
        private readonly PayrollV2ShahoService $shahoService,
        private readonly PayrollV2ResidentService $residentService,
        private readonly PayrollV2AllowanceLabelService $allowanceLabelService,
        private readonly PayrollV2FuyoService $fuyoService,
        private readonly PayrollV2UpdateService $updateService,
        private readonly PayrollV2SocialInsuranceAmountService $socialInsuranceAmountService,
    ) {}


    // 給与の再計算
    /**
     * 給与明細の「再計算」ボタンの入口。
     *
     * 基本情報・社保・住民税・扶養・交通費を給与明細へ反映する。
     * 支給合計、控除合計、課税/非課税などの最終合計は PayrollV2UpdateService を正本にする。
     */
    public function recalculate(string $staffId, int $year, int $month, ?string $companyName = null): int
    {
        $staffId = trim($staffId);
        if ($staffId === '' || $year < 2000 || $month < 1 || $month > 12) {
            return 0;
        }

        $companyId = $this->resolveCompanyId($staffId, $companyName);
        $companyName = $this->normalizeCompanyName($staffId, $companyName);
        $birthday = $this->loadBirthday($staffId);
        $paymentDate = $this->loadPaymentDate($staffId, $year, $month);
        $currentSummary = $this->loadCurrentSummary($staffId, $year, $month);

        $kihon = $this->kihonService->map($year, $month)[$staffId] ?? [];
        $shaho = $this->shahoService->map($year, $month)[$staffId] ?? [];
        $resident = $this->residentService->map($year, $month)[$staffId] ?? [];

        $shaho = array_merge(
            (array) $shaho,
            $this->socialInsuranceAmountService->loadRates($companyId, $paymentDate)
        );

        $resident = $this->residentService->map($year, $month)[$staffId] ?? [];

        $payload = array_merge(
            $this->zeroPayload(),
            $this->kihonToSummary((array) $kihon, (array) $currentSummary),
            $this->shahoToSummary((array) $shaho, $birthday, $year, $month),
            $this->residentToSummary((array) $resident),
            $this->commutingToSummary($staffId, (array) $kihon, (array) $currentSummary, $paymentDate),
            $this->manualAllowanceToSummary((array) $currentSummary),
            $this->manualDeductionToSummary((array) $currentSummary),
            ['fuyo_sum' => $this->fuyoService->resolveByPaymentDate($staffId, $paymentDate)],
        );
        $updated = 0;
        $updated += (int) $this->updateService->save($staffId, $year, $month, $payload, $companyName);
        // 残業・雇用保険・所得税は PayrollV2CalculationFlowService 側で同じ順序で計算する。
        $updated += (int) $this->updateService->refreshTotals($staffId, $year, $month, $companyName);

        return $updated;
    }

    public function recalculatePayrollMasterOnly(string $staffId, int $year, int $month, ?string $companyName = null): int
    {
        $staffId = trim($staffId);
        if ($staffId === '' || $year < 2000 || $month < 1 || $month > 12) {
            return 0;
        }

        $companyName = $this->normalizeCompanyName($staffId, $companyName);
        $paymentDate = $this->loadPaymentDate($staffId, $year, $month);
        $currentSummary = $this->loadCurrentSummary($staffId, $year, $month);
        $kihon = $this->kihonService->map($year, $month)[$staffId] ?? [];

        $payload = array_merge(
            $this->zeroPayload(),
            $this->kihonToSummary((array) $kihon, (array) $currentSummary),
            $this->commutingToSummary($staffId, (array) $kihon, (array) $currentSummary, $paymentDate),
            $this->manualAllowanceToSummary((array) $currentSummary),
            $this->manualDeductionToSummary((array) $currentSummary),
        );

        $updated = 0;
        $updated += (int) $this->updateService->save($staffId, $year, $month, $payload, $companyName);
        $updated += (int) $this->updateService->refreshTotals($staffId, $year, $month, $companyName);

        return $updated;
    }


    // 社保料率取得
    /** @return array{kenpo_rate:float,kaigo_rate:float,kounen_rate:float} */
    public function refreshBasicSalaryFromAttendance(string $staffId, int $year, int $month, ?string $companyName = null): int
    {
        $staffId = trim($staffId);
        if ($staffId === '' || $year < 2000 || $month < 1 || $month > 12) {
            return 0;
        }

        $kihon = $this->kihonService->map($year, $month)[$staffId] ?? [];
        $currentSummary = $this->loadCurrentSummary($staffId, $year, $month);
        $paymentDate = $this->loadPaymentDate($staffId, $year, $month);
        $payload = array_merge(
            $this->basicSalaryPayload((array) $kihon, (array) $currentSummary),
            $this->commutingToSummary($staffId, (array) $kihon, (array) $currentSummary, $paymentDate),
        );

        if ($payload === []) {
            return 0;
        }

        $companyName = $this->normalizeCompanyName($staffId, $companyName);

        return (int) $this->updateService->save($staffId, $year, $month, $payload, $companyName);
    }

    /** @return array<string,int|float> */
    private function zeroPayload(): array
    {
        $payload = [];

        foreach ($this->allowanceLabelService->entries() as $entry) {
            $key = trim((string) ($entry['key'] ?? ''));
            if ($key !== '') {
                $payload[$key] = 0;
            }
        }

        foreach (range(1, 17) as $i) {
            $payload['allowance_amo_' . $i] = 0;
        }

        foreach (
            [
                'basic_salary',
                'kenpo',
                'kaigo',
                'kounen',
                'koyou',
                'income_tax',
                'resident_tax',
                'rent_cost',
                'adjustment_cost',
                'koujyo_1',
                'koyou_office',
                'jidou_office',
                'rousai_office',
                'taxation_sum',
                'not_taxation_sum',
                'supply_sum',
                'syaho_sum',
                'deduction_sum',
                'syaho_deduction_sum',
                'kotei_sum',
                'yakuin_sum',
                'rouho_target_sum',
                'syaho_target_sum',
            ] as $key
        ) {
            $payload[$key] = 0;
        }

        return $payload;
    }

    // スタッフ基本マスタ
    /** @param array<string,mixed> $kihon @return array<string,int|float> */
    private function kihonToSummary(array $kihon, array $currentSummary = []): array
    {
        $monthlySalary = $this->num($kihon['monthly_salary'] ?? 0);
        $executiveRemu = $this->num($kihon['executive_remu'] ?? 0);
        $hourlyRate = $this->hourlyRate($kihon);
        $basicSalary = $this->basicSalaryAmount($kihon, $currentSummary);

        return [
            'basic_salary' => $basicSalary,
            'allowance_amo_1' => $monthlySalary > 0
                ? $monthlySalary
                : $hourlyRate,
            'allowance_amo_2' => $executiveRemu,
            'allowance_amo_16' => $this->num($kihon['position_allow'] ?? 0),
            'allowance_amo_13' => $this->num($kihon['duties_allow'] ?? 0),
            'allowance_amo_11' => $this->num($kihon['qualification_allow'] ?? 0),
            'allowance_amo_12' => $this->num($kihon['claim_allow'] ?? 0),
            'allowance_amo_5' => $this->num($kihon['adjustment_add'] ?? 0),
            'allowance_amo_14' => $this->num($kihon['rent_pay'] ?? 0),
            'allowance_amo_17' => $this->num($kihon['fixed_overtime'] ?? 0),
            'absence_deduction' => $this->num($kihon['adjustment_pay'] ?? 0),
        ];
    }

    // 基本給と時間給を給与明細へ反映する。
    /** @param array<string,mixed> $kihon @param array<string,mixed> $currentSummary @return array<string,int|float> */
    private function basicSalaryPayload(array $kihon, array $currentSummary): array
    {
        $monthlySalary = $this->num($kihon['monthly_salary'] ?? 0);
        $hourlyRate = $this->hourlyRate($kihon);

        if ($monthlySalary <= 0 && $hourlyRate <= 0) {
            return [];
        }

        return [
            'basic_salary' => $this->basicSalaryAmount($kihon, $currentSummary),
            'allowance_amo_1' => $monthlySalary > 0 ? $monthlySalary : $hourlyRate,
        ];
    }

    /** @param array<string,mixed> $kihon @param array<string,mixed> $currentSummary */
    private function basicSalaryAmount(array $kihon, array $currentSummary): int
    {
        $monthlySalary = $this->num($kihon['monthly_salary'] ?? 0);
        if ($monthlySalary > 0) {
            return (int) round($monthlySalary, 0);
        }

        $hourlyRate = $this->hourlyRate($kihon);
        $workTime = $this->num($currentSummary['work_time'] ?? 0);
        // 要確認：work_timeは残業・休日出勤の時間も含む総時間のため、そのまま時給を掛けると
        // 残業手当・休日出勤手当（PayrollV2OvertimeDeductionServiceで満額別計算）と二重払いに
        // なっていた。通常時間分だけ基本給とし、残業・休日出勤分はそちらの手当だけで払う
        // （2026-08-17）。
        $overtimeHours = $this->num($currentSummary['overtime'] ?? 0);
        $holidayHours = $this->num($currentSummary['work_time_num'] ?? 0);
        $regularHours = max(0.0, $workTime - $overtimeHours - $holidayHours);

        return (int) round($hourlyRate * $regularHours, 0);
    }

    /** @param array<string,mixed> $kihon */
    private function hourlyRate(array $kihon): float
    {
        $adjusted = $this->num($kihon['hourly_salary'] ?? 0);
        if ($adjusted > 0) {
            return $adjusted;
        }

        return $this->num($kihon['hourly_pay'] ?? 0);
    }

    private function commutingToSummary(string $staffId, array $kihon, array $currentSummary, string $paymentDate): array
    {
        $staff = $this->loadCommutingStaff($staffId);
        $fixedCommuting = $this->num($kihon['traffic_pay'] ?? 0);
        if ($fixedCommuting > 0) {
            return [
                'allowance_amo_6' => (int) round($fixedCommuting, 0),
                'allowance_amo_10' => 0,
            ];
        }

        $workDays = $this->num($currentSummary['work_in_num'] ?? 0);
        $dailyTraffic = $this->num($staff['traffic_day'] ?? 0);
        $carKm = $this->num($staff['car_km'] ?? 0);
        $commutingAmount = max(0.0, $workDays * $dailyTraffic);
        if (str_contains(trim((string) ($staff['staff_division'] ?? '')), '業務委託')) {
            return [
                'allowance_amo_6' => (int) round($commutingAmount, 0),
                'allowance_amo_10' => 0,
            ];
        }

        $taxFreeLimit = $this->commutingTaxFreeLimit($carKm, $paymentDate);
        $nonTaxable = min($commutingAmount, $taxFreeLimit);
        $taxable = max(0.0, $commutingAmount - $taxFreeLimit);

        return [
            'allowance_amo_6' => (int) round($nonTaxable, 0),
            'allowance_amo_10' => (int) round($taxable, 0),
        ];
    }

    /** @param array<string,mixed> $currentSummary @return array{allowance_amo_4:int} */
    private function manualAllowanceToSummary(array $currentSummary): array
    {
        return [
            'allowance_amo_4' => (int) round($this->num($currentSummary['allowance_amo_4'] ?? 0), 0),
        ];
    }

    /** @param array<string,mixed> $currentSummary @return array{adjustment_cost:int,koujyo_1:int} */
    private function manualDeductionToSummary(array $currentSummary): array
    {
        return [
            'adjustment_cost' => (int) round($this->num($currentSummary['adjustment_cost'] ?? 0), 0),
            'koujyo_1' => (int) round($this->num($currentSummary['koujyo_1'] ?? 0), 0),
        ];
    }

    /** @return array<string,mixed> */
    private function loadCommutingStaff(string $staffId): array
    {
        return (array) (DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->whereRaw('LTRIM(RTRIM(CAST(staff_id as nvarchar(50)))) = ?', [$staffId])
            ->first(['car_km', 'traffic_day', 'staff_division']) ?? []);
    }

    private function commutingTaxFreeLimit(float $oneWayKm, string $paymentDate): int
    {
        if ($oneWayKm < 2) {
            return 0;
        }

        $limits = $paymentDate >= '2025-04-01'
            ? [
                [2, 10, 4200],
                [10, 15, 7300],
                [15, 25, 13500],
                [25, 35, 19700],
                [35, 45, 25900],
                [45, 55, 32300],
                [55, PHP_FLOAT_MAX, 38700],
            ]
            : [
                [2, 10, 4200],
                [10, 15, 7100],
                [15, 25, 12900],
                [25, 35, 18700],
                [35, 45, 24400],
                [45, 55, 28000],
                [55, PHP_FLOAT_MAX, 31600],
            ];

        foreach ($limits as [$min, $max, $amount]) {
            if ($oneWayKm >= $min && $oneWayKm < $max) {
                return $amount;
            }
        }

        return 0;
    }

    private function shahoToSummary(array $shaho, ?\DateTimeImmutable $birthday, int $year, int $month): array
    {
        return $this->socialInsuranceAmountService->payrollSummary($shaho, $birthday, $year, $month);
    }


    /** @param array<string,mixed> $resident @return array<string,int|float> */
    private function residentToSummary(array $resident): array
    {
        return [
            'resident_tax' => $this->num($resident['resident_tax'] ?? 0),
        ];
    }

    private function normalizeCompanyName(string $staffId, ?string $companyName): string
    {
        $name = trim((string) $companyName);
        if ($name !== '') {
            return $name;
        }

        $row = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs as s')
            ->leftJoin('dbo.mx_stores as st', 'st.store_code', '=', 's.section')
            ->leftJoin('dbo.mx_companies as c', 'c.company_id', '=', 'st.company_id')
            ->whereRaw('LTRIM(RTRIM(s.staff_id)) = ?', [$staffId])
            ->first(['c.company_name']);

        return trim((string) ($row->company_name ?? ''));
    }

    private function resolveCompanyId(string $staffId, ?string $companyName): int
    {
        $row = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs as s')
            ->leftJoin('dbo.mx_stores as st', 'st.store_code', '=', 's.section')
            ->leftJoin('dbo.mx_companies as c', 'c.company_id', '=', 'st.company_id')
            ->whereRaw('LTRIM(RTRIM(s.staff_id)) = ?', [$staffId])
            ->first(['c.company_id']);

        return (int) ($row->company_id ?? 0);
    }

    private function loadBirthday(string $staffId): ?\DateTimeImmutable
    {
        $row = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->whereRaw('LTRIM(RTRIM(staff_id)) = ?', [$staffId])
            ->first(['birthday']);

        return $this->toDate($row->birthday ?? null);
    }


    // 給与計算
    /** @return array<string,mixed> */
    private function loadCurrentSummary(string $staffId, int $year, int $month): array
    {
        return (array) (DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month])
            ->whereRaw('LTRIM(RTRIM([kyuyo_staff_id])) = ?', [$staffId])
            ->orderByDesc('kyuyo_sho_no')
            ->first(['work_in_num', 'work_time', 'overtime', 'work_time_num', 'allowance_amo_4', 'adjustment_cost', 'koujyo_1']) ?? []);
    }

    private function loadPaymentDate(string $staffId, int $year, int $month): string
    {
        $row = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month])
            ->whereRaw('LTRIM(RTRIM([kyuyo_staff_id])) = ?', [$staffId])
            ->orderByDesc('kyuyo_sho_no')
            ->first(['supply_month']);

        $value = $this->toDate($row->supply_month ?? null);
        if ($value === null) {
            return sprintf('%04d-%02d-01', $year, $month);
        }

        return $value->format('Y-m-d');
    }

    // 賞与計算
    private function loadBonusPaymentDate(string $staffId, int $year, int $month): string
    {
        $row = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 1)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month])
            ->whereRaw('LTRIM(RTRIM([kyuyo_staff_id])) = ?', [$staffId])
            ->orderByDesc('kyuyo_sho_no')
            ->first(['supply_month']);

        $value = $this->toDate($row->supply_month ?? null);

        if ($value === null) {
            return sprintf('%04d-%02d-01', $year, $month);
        }

        return $value->format('Y-m-d');
    }


    private function toDate(mixed $value): ?\DateTimeImmutable
    {
        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        if ($value === null) {
            return null;
        }

        $s = trim((string) $value);
        if ($s === '') {
            return null;
        }

        $ts = strtotime($s);
        if ($ts === false) {
            return null;
        }

        return (new \DateTimeImmutable())->setTimestamp($ts);
    }

    private function num(mixed $v): float
    {
        if ($v === null) {
            return 0.0;
        }
        if (is_int($v) || is_float($v)) {
            return (float) $v;
        }

        $s = trim((string) $v);
        if ($s === '') {
            return 0.0;
        }

        $s = str_replace([',', ' '], '', $s);
        return is_numeric($s) ? (float) $s : 0.0;
    }
}

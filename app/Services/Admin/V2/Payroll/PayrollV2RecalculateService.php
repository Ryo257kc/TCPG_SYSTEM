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
        private readonly PayrollV2UpdateService $updateService,
        private readonly PayrollV2OvertimeDeductionService $overtimeDeductionService,
        private readonly PayrollV2EmploymentInsuranceService $employmentInsuranceService,
        private readonly PayrollV2IncomeTaxService $incomeTaxService,
    ) {
    }

    public function recalculate(string $staffId, int $year, int $month, ?string $companyName = null): int
    {
        $staffId = trim($staffId);
        if ($staffId === '' || $year < 2000 || $month < 1 || $month > 12) {
            return 0;
        }

        $companyName = $this->normalizeCompanyName($staffId, $companyName);

        $kihon = $this->kihonService->map($year, $month)[$staffId] ?? [];
        $shaho = $this->shahoService->map($year, $month)[$staffId] ?? [];
        $resident = $this->residentService->map($year, $month)[$staffId] ?? [];

        $payload = $this->zeroPayload();
        $payload = array_merge(
            $payload,
            $this->kihonToSummary((array) $kihon),
            $this->shahoToSummary((array) $shaho),
            $this->residentToSummary((array) $resident)
        );

        $updated = 0;
        $updated += (int) $this->updateService->save($staffId, $year, $month, $payload, $companyName);
        $updated += (int) $this->overtimeDeductionService->recalculate($staffId, $year, $month, $companyName);
        $updated += (int) $this->employmentInsuranceService->recalculate($staffId, $year, $month);
        $updated += (int) $this->incomeTaxService->recalculate($staffId, $year, $month);
        $updated += (int) $this->updateService->refreshTotals($staffId, $year, $month, $companyName);

        return $updated;
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

        foreach ([
            'basic_salary',
            'officer_com',
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
        ] as $key) {
            $payload[$key] = 0;
        }

        return $payload;
    }

    /** @param array<string,mixed> $kihon @return array<string,int|float> */
    private function kihonToSummary(array $kihon): array
    {
        $monthlySalary = $this->num($kihon['monthly_salary'] ?? 0);
        $executiveRemu = $this->num($kihon['executive_remu'] ?? 0);

        return [
            'basic_salary' => $monthlySalary,
            'officer_com' => $executiveRemu,
            'allowance_amo_1' => $monthlySalary,
            'allowance_amo_2' => $executiveRemu,
            'allowance_amo_16' => $this->num($kihon['position_allow'] ?? 0),
            'allowance_amo_13' => $this->num($kihon['duties_allow'] ?? 0),
            'allowance_amo_11' => $this->num($kihon['qualification_allow'] ?? 0),
            'allowance_amo_12' => $this->num($kihon['claim_allow'] ?? 0),
            'allowance_amo_10' => $this->num($kihon['traffic_pay'] ?? 0),
            'allowance_amo_5' => $this->num($kihon['adjustment_add'] ?? 0),
            'allowance_amo_14' => $this->num($kihon['rent_subsidies'] ?? 0),
            'allowance_amo_6' => $this->num($kihon['rent_pay'] ?? 0),
            'allowance_amo_17' => $this->num($kihon['fixed_overtime'] ?? 0),
            'absence_deduction' => $this->num($kihon['adjustment_pay'] ?? 0),
        ];
    }

    /** @param array<string,mixed> $shaho @return array<string,int|float> */
    private function shahoToSummary(array $shaho): array
    {
        return [
            'kenpo' => $this->num($shaho['kenpo_amo'] ?? 0),
            'kaigo' => $this->num($shaho['kaigo_amo'] ?? 0),
            'kounen' => $this->num($shaho['kounen_amo'] ?? 0),
        ];
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

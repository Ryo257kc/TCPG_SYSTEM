<?php

namespace App\Services\Admin\Payroll;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class PayrollCalculationService
{
    /**
     * @return array{kihon:array<string,mixed>,syaho:array<string,mixed>,resident:array<string,mixed>}
     */
    public function resolveMasterInfoForDisplay(string $staffId, string $payrollDate): array
    {
        $staffId = trim($staffId);
        $payrollDate = trim($payrollDate);
        if ($staffId === '' || $payrollDate === '') {
            return ['kihon' => [], 'syaho' => [], 'resident' => []];
        }

        $companyCode = $this->resolveStaffCompanyCode($staffId);
        return $this->resolvePayrollMasterInfo($staffId, $payrollDate, $companyCode);
    }

    /**
     * @return array{status?:string,error?:string,query:array<string,string>}
     */
    public function execute(Request $request, string $action): array
    {
        return match ($action) {
            'recalcDefaults' => $this->recalcDefaults($request),
            'recalcKoyou' => $this->recalcKoyou($request),
            'recalcPremiumDeduction' => $this->recalcPremiumDeduction($request),
            'recalcIncomeTax' => $this->recalcIncomeTax($request),
            default => throw new RuntimeException('Unsupported calculation action: ' . $action),
        };
    }

    /**
     * @return array{status:string,query:array<string,string>}
     */
    private function recalcDefaults(Request $request): array
    {
        [$entry, $payload, $staffId, $payrollDate, $query] = $this->loadTargetPayload($request);

        $companyCode = $this->resolveStaffCompanyCode($staffId);
        $masterInfo = $this->resolvePayrollMasterInfo($staffId, $payrollDate, $companyCode);
        $updatedKeys = $this->applyPayrollMastersToPayload($payload, $masterInfo, $staffId);

        $this->applyComputedTotals($payload, $staffId);
        $this->applyEmploymentInsurance($payload, $staffId, $payrollDate);
        $this->applyComputedTotals($payload, $staffId);
        $this->applyIncomeTaxFromRules($payload, $staffId);
        $this->applyComputedTotals($payload, $staffId);

        $this->savePayload((array) $entry, $payload);

        return ['status' => 'Defaults applied: ' . count($updatedKeys) . ' fields.', 'query' => $query];
    }

    /**
     * @return array{status:string,query:array<string,string>}
     */
    private function recalcKoyou(Request $request): array
    {
        [$entry, $payload, $staffId, $payrollDate, $query] = $this->loadTargetPayload($request);

        $this->applyComputedTotals($payload, $staffId);
        $this->applyEmploymentInsurance($payload, $staffId, $payrollDate);
        $this->applyComputedTotals($payload, $staffId);

        $this->savePayload((array) $entry, $payload);

        return ['status' => 'Employment insurance recalculated.', 'query' => $query];
    }

    /**
     * @return array{status:string,query:array<string,string>}
     */
    private function recalcPremiumDeduction(Request $request): array
    {
        [$entry, $payload, $staffId, $payrollDate, $query] = $this->loadTargetPayload($request);

        $this->applyPremiumDeductionFromAttendance($payload, $staffId);
        $this->applyComputedTotals($payload, $staffId);

        $this->savePayload((array) $entry, $payload);

        return ['status' => 'Premium/Deduction recalculated.', 'query' => $query];
    }

    /**
     * @return array{status:string,query:array<string,string>}
     */
    private function recalcIncomeTax(Request $request): array
    {
        [$entry, $payload, $staffId, $payrollDate, $query] = $this->loadTargetPayload($request);

        $this->applyComputedTotals($payload, $staffId);
        $this->applyIncomeTaxFromRules($payload, $staffId);
        $this->applyComputedTotals($payload, $staffId);

        $this->savePayload((array) $entry, $payload);

        return ['status' => 'Income tax recalculated.', 'query' => $query];
    }

    /**
     * @return array{0:object,1:array,2:string,3:string,4:array<string,string>}
     */
    private function loadTargetPayload(Request $request): array
    {
        $validated = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'target_staff_id' => ['nullable', 'string', 'max:50'],
            'staff_id' => ['nullable', 'string', 'max:50'],
            'company_id' => ['nullable', 'string', 'max:200'],
            'row' => ['nullable', 'string', 'max:100'],
        ]);

        [$year, $month] = [(int) substr((string) $validated['month'], 0, 4), (int) substr((string) $validated['month'], 5, 2)];
        $staffId = trim((string) ($validated['target_staff_id'] ?? ''));
        if ($staffId === '') {
            $staffId = trim((string) ($validated['staff_id'] ?? ''));
        }
        if ($staffId === '') {
            throw new RuntimeException('Target staff required.');
        }

        $entry = $this->findPayrollEntry($year, $month, $staffId);
        if ($entry === null) {
            throw new RuntimeException('Payroll row not found.');
        }

        $lockCol = $this->payrollColumn('is_edit_locked', 'edit_lock');
        if ((int) ($entry->{$lockCol} ?? 0) === 1) {
            throw new RuntimeException('Locked payroll cannot be recalculated.');
        }

        $payloadCol = $this->payrollColumn('raw_payload', null);
        if ($payloadCol === null) {
            throw new RuntimeException('raw_payload column not found.');
        }

        $payload = $this->decodePayload($entry->{$payloadCol} ?? null);
        $payrollDate = sprintf('%04d-%02d-01', $year, $month);

        $query = array_filter([
            'month' => (string) ($validated['month'] ?? ''),
            'company_id' => trim((string) ($validated['company_id'] ?? '')),
            'staff_id' => $staffId,
            'row' => (string) ($validated['row'] ?? ''),
        ], static fn ($v) => $v !== '');

        return [$entry, $payload, $staffId, $payrollDate, $query];
    }

    private function savePayload(array $entry, array $payload): void
    {
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if (!is_string($encoded)) {
            throw new RuntimeException('Payload encode failed.');
        }

        $table = $this->payrollTable();
        $entryIdCol = $this->payrollColumn('payroll_entry_id', 'kyuyo_sho_no');
        $payloadCol = $this->payrollColumn('raw_payload', null);
        if ($payloadCol === null) {
            throw new RuntimeException('raw_payload column not found.');
        }

        $update = [$payloadCol => $encoded];
        if (Schema::connection('sqlsrv_payroll')->hasColumn($table, 'updated_at')) {
            $update['updated_at'] = now('Asia/Tokyo');
        }

        DB::connection('sqlsrv_payroll')
            ->table($table)
            ->where($entryIdCol, (int) ($entry[$entryIdCol] ?? 0))
            ->update($update);
    }

    private function findPayrollEntry(int $year, int $month, string $staffId): ?object
    {
        $table = $this->payrollTable();
        $bonusCol = $this->payrollColumn('is_bonus', 'bonus');
        $staffCol = $this->payrollColumn('staff_code', 'kyuyo_staff_id');
        $entryIdCol = $this->payrollColumn('payroll_entry_id', 'kyuyo_sho_no');

        return DB::connection('sqlsrv_payroll')
            ->table($table)
            ->where($bonusCol, 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month])
            ->whereRaw('LTRIM(RTRIM(' . $this->wrap($staffCol) . ')) = ?', [$staffId])
            ->orderBy($entryIdCol, 'desc')
            ->first();
    }

    private function decodePayload(mixed $payload): array
    {
        if (!is_string($payload) || trim($payload) === '') {
            return [];
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function resolvePayrollMasterInfo(string $staffId, string $selectedMonthDate, string $companyCode): array
    {
        $monthNo = (int) date('n', strtotime($selectedMonthDate));
        $residentSlot = $monthNo >= 6 ? ($monthNo - 5) : ($monthNo + 7);
        $residentColumn = 'resident_tax' . $residentSlot;
        $cutoffDate = $selectedMonthDate;

        $result = [
            'kihon' => [],
            'syaho' => [],
            'resident' => [],
        ];

        try {
            $kihonTable = $this->resolvePayrollMasterTable([
                'dbo.m_kihon', 'm_kihon',
                'dbo.mx_kihon', 'mx_kihon',
                'dbo.t_kihon', 't_kihon',
            ]);
            if ($kihonTable !== null) {
                $staffCodeCol = $this->payrollTableHasColumn($kihonTable, 'staff_code') ? 'staff_code' : 'staff_id';
                $idOrderCol = $this->payrollTableHasColumn($kihonTable, 'kihon_no') ? 'kihon_no' : null;

                $q = DB::connection('sqlsrv_payroll')
                    ->table($kihonTable)
                    ->whereRaw('LTRIM(RTRIM(' . $this->wrap($staffCodeCol) . ')) = ?', [$staffId])
                    ->whereDate('decision_date', '<', $cutoffDate)
                    ->orderBy('decision_date', 'desc');
                if ($idOrderCol !== null) {
                    $q->orderBy($idOrderCol, 'desc');
                }
                $kihon = $q->first();

                if ($kihon !== null) {
                    $result['kihon'] = [
                        'monthly_salary' => $this->rowValue($kihon, 'monthly_salary'),
                        'executive_remu' => $this->rowValue($kihon, 'executive_remu'),
                        'hourly_salary' => $this->rowValue($kihon, 'hourly_salary'),
                        'hourly_pay' => $this->rowValue($kihon, 'hourly_pay'),
                        'position_allow' => $this->rowValue($kihon, 'position_allow'),
                        'duties_allow' => $this->rowValue($kihon, 'duties_allow'),
                        'qualification_allow' => $this->rowValue($kihon, 'qualification_allow'),
                        'claim_allow' => $this->rowValue($kihon, 'claim_allow'),
                        'traffic_pay' => $this->rowValue($kihon, 'traffic_pay'),
                        'adjustment_add' => $this->rowValue($kihon, 'adjustment_add'),
                        'rent_subsidies' => $this->rowValue($kihon, 'rent_subsidies'),
                        'rent_pay' => $this->rowValue($kihon, 'rent_pay'),
                        'adjustment_pay' => $this->rowValue($kihon, 'adjustment_pay'),
                        'fixed_overtime' => $this->rowValue($kihon, 'fixed_overtime'),
                    ];
                }
            }
        } catch (\Throwable) {
            $result['kihon'] = [];
        }

        try {
            $residentTable = $this->resolvePayrollMasterTable([
                'dbo.m_resident', 'm_resident',
                'dbo.mx_resident', 'mx_resident',
                'dbo.t_resident', 't_resident',
            ]);
            if ($residentTable !== null) {
                $staffCodeCol = $this->payrollTableHasColumn($residentTable, 'staff_code') ? 'staff_code' : 'staff_id';
                $idOrderCol = $this->payrollTableHasColumn($residentTable, 'resident_no') ? 'resident_no' : null;

                $q = DB::connection('sqlsrv_payroll')
                    ->table($residentTable)
                    ->whereRaw('LTRIM(RTRIM(' . $this->wrap($staffCodeCol) . ')) = ?', [$staffId])
                    ->whereDate('target_month', '<=', $selectedMonthDate)
                    ->orderBy('target_month', 'desc');
                if ($idOrderCol !== null) {
                    $q->orderBy($idOrderCol, 'desc');
                }
                $resident = $q->first();

                if ($resident !== null) {
                    $residentTax = $this->rowValue($resident, $residentColumn);
                    $result['resident'] = [
                        'resident_tax_month' => $residentTax,
                    ];
                }
            }
        } catch (\Throwable) {
            $result['resident'] = [];
        }

        try {
            $staffShouTable = $this->resolvePayrollMasterTable([
                'dbo.m_staff_social_insurances', 'm_staff_social_insurances',
                'dbo.m_staff_shou', 'm_staff_shou',
                'dbo.mx_staff_shou', 'mx_staff_shou',
                'dbo.t_staff_shou', 't_staff_shou',
            ]);
            if ($staffShouTable !== null) {
                $staffCodeCol = $this->payrollTableHasColumn($staffShouTable, 'staff_code') ? 'staff_code' : 'staff_id';
                $idOrderCol = $this->payrollTableHasColumn($staffShouTable, 'staff_social_insurance_id')
                    ? 'staff_social_insurance_id'
                    : ($this->payrollTableHasColumn($staffShouTable, 'staff_shou_no') ? 'staff_shou_no' : null);

                $q = DB::connection('sqlsrv_payroll')
                    ->table($staffShouTable)
                    ->whereRaw('LTRIM(RTRIM(' . $this->wrap($staffCodeCol) . ')) = ?', [$staffId])
                    ->whereDate('raise_year', '<', $cutoffDate)
                    ->orderBy('raise_year', 'desc');
                if ($idOrderCol !== null) {
                    $q->orderBy($idOrderCol, 'desc');
                }
                $staffShou = $q->first();

                if ($staffShou !== null) {
                    $result['syaho'] = [
                        'kenpo_amo' => $this->rowValue($staffShou, 'kenpo_amo'),
                        'kaigo_amo' => $this->rowValue($staffShou, 'kaigo_amo'),
                        'kounen_amo' => $this->rowValue($staffShou, 'kounen_amo'),
                    ];
                }
            }
        } catch (\Throwable) {
            $result['syaho'] = [];
        }

        return $result;
    }

    private function applyPayrollMastersToPayload(array &$payload, array $masterInfo, string $staffId): array
    {
        $updated = [];
        $set = function (string $payloadKey, mixed $value) use (&$payload, &$updated): void {
            if ($value === null) {
                return;
            }
            $text = trim((string) $value);
            if ($text === '') {
                return;
            }
            $payload[$payloadKey] = $this->normalizeNumericInput($text);
            $updated[] = $payloadKey;
        };

        $kihon = (array) ($masterInfo['kihon'] ?? []);
        $basePay = $this->resolveBasePayForEmployment($staffId, $kihon);
        $set('basic_salary', $basePay);
        $set('allowance_amo_1', $basePay);
        $set('allowance_amo_2', $kihon['executive_remu'] ?? null);
        $set('allowance_amo_16', $kihon['position_allow'] ?? null);
        $set('allowance_amo_13', $kihon['duties_allow'] ?? null);
        $set('allowance_amo_11', $kihon['qualification_allow'] ?? null);
        $set('allowance_amo_12', $kihon['claim_allow'] ?? null);
        $set('allowance_amo_10', $kihon['traffic_pay'] ?? null);
        $set('allowance_amo_5', $kihon['adjustment_add'] ?? null);
        $set('allowance_amo_14', $kihon['rent_subsidies'] ?? null);
        $set('rent_cost', $kihon['rent_pay'] ?? null);
        $set('adjustment_cost', $kihon['adjustment_pay'] ?? null);
        $set('allowance_amo_15', $kihon['fixed_overtime'] ?? null);

        $syaho = (array) ($masterInfo['syaho'] ?? []);
        $set('kenpo', $syaho['kenpo_amo'] ?? null);
        $set('kaigo', $syaho['kaigo_amo'] ?? null);
        $set('kounen', $syaho['kounen_amo'] ?? null);

        $resident = (array) ($masterInfo['resident'] ?? []);
        $set('resident_tax', $resident['resident_tax_month'] ?? null);

        return array_values(array_unique($updated));
    }

    private function resolveBasePayForEmployment(string $staffId, array $kihon): mixed
    {
        $staffId = trim($staffId);
        $employment = '';

        if ($staffId !== '') {
            try {
                $table = $this->staffTable();
                $staffIdColumn = $this->staffColumn('staff_code', 'staff_id');
                $selects = ['staff_division'];
                if (Schema::connection('sqlsrv')->hasColumn($table, 'employment_status')) {
                    $selects[] = 'employment_status';
                }
                $row = DB::connection('sqlsrv')
                    ->table($table)
                    ->whereRaw('LTRIM(RTRIM(' . $this->wrap($staffIdColumn) . ')) = ?', [$staffId])
                    ->first($selects);

                $employment = trim((string) ($this->rowValue($row, 'staff_division') ?? ''));
                if ($employment === '') {
                    $employment = trim((string) ($this->rowValue($row, 'employment_status') ?? ''));
                }
            } catch (\Throwable) {
                $employment = '';
            }
        }

        if (mb_strpos($employment, 'パート') !== false) {
            return $kihon['hourly_pay'] ?? ($kihon['hourly_salary'] ?? null);
        }

        return $kihon['monthly_salary'] ?? null;
    }

    private function applyPremiumDeductionFromAttendance(array &$payload, string $staffId): void
    {
        $isHourly = mb_strpos($this->resolveStaffDivision($staffId), 'パート') !== false;
        $overtimeHours = max(0.0, $this->payloadNumber($payload, 'overtime'));
        $nightHours = max(0.0, $this->payloadNumber($payload, 'night_over_time'));
        $holidayHours = max(0.0, $this->payloadNumber($payload, 'work_horiday_num'));
        $lateHours = max(0.0, $this->payloadNumber($payload, 'late_time'));
        $absenceDays = max(0.0, $this->payloadNumber($payload, 'absence_num'));

        $hourlyRate = 0.0;
        $deductionHourlyRate = 0.0;

        if ($isHourly) {
            $hourlyRate = max(
                0.0,
                $this->payloadNumber($payload, 'allowance_amo_1'),
                $this->payloadNumber($payload, 'basic_salary')
            );
            $deductionHourlyRate = $hourlyRate;
        } else {
            $bases = $this->resolvePremiumDeductionBases($payload, $staffId);
            $workingHours = $this->resolveMonthlyWorkingHours($staffId, $payload);
            if ($workingHours > 0) {
                $hourlyRate = max(0.0, $bases['premium_base'] / $workingHours);
                $deductionHourlyRate = max(0.0, $bases['deduction_base'] / $workingHours);
            }
        }

        $overtimePay = round($hourlyRate * 1.25 * $overtimeHours, 0, PHP_ROUND_HALF_UP);
        $nightPay = round($hourlyRate * 0.25 * $nightHours, 0, PHP_ROUND_HALF_UP);
        $holidayPay = round($hourlyRate * 1.25 * $holidayHours, 0, PHP_ROUND_HALF_UP);
        $lateDeduction = round($deductionHourlyRate * $lateHours, 0, PHP_ROUND_HALF_UP);
        $absenceDeduction = round($deductionHourlyRate * 8.0 * $absenceDays, 0, PHP_ROUND_HALF_UP);

        $payload['allowance_amo_8'] = $this->normalizeNumericInput($overtimePay);
        $payload['allowance_amo_9'] = $this->normalizeNumericInput($nightPay);
        $payload['allowance_amo_7'] = $this->normalizeNumericInput($holidayPay);
        $payload['late_deduction'] = $this->normalizeNumericInput($lateDeduction > 0 ? -$lateDeduction : 0);
        $payload['absence_deduction'] = $this->normalizeNumericInput($absenceDeduction > 0 ? -$absenceDeduction : 0);
    }

    private function applyIncomeTaxFromRules(array &$payload, string $staffId): void
    {
        $staffId = trim($staffId);
        if ($staffId === '') {
            return;
        }

        $syahoAfter = max(0.0, $this->payloadNumber($payload, 'taxation_sum') - $this->payloadNumber($payload, 'syaho_sum'));
        $payload['syaho_deduction_sum'] = $this->normalizeNumericInput($syahoAfter);

        $taxTable = trim((string) ($this->resolveStaffTaxAmount($staffId) ?: '甲欄'));
        if ($taxTable !== '甲欄') {
            return;
        }

        if ($syahoAfter < 88000) {
            $payload['income_tax'] = 0;
            return;
        }

        $salaryDeduction = 0.0;
        if ($syahoAfter <= 135416) {
            $salaryDeduction = 45834;
        } elseif ($syahoAfter <= 149999) {
            $salaryDeduction = floor($syahoAfter * 0.4) - 8333;
        } elseif ($syahoAfter <= 299999) {
            $salaryDeduction = floor($syahoAfter * 0.3 + 6667);
        } elseif ($syahoAfter <= 549999) {
            $salaryDeduction = floor($syahoAfter * 0.2 + 36667);
        } elseif ($syahoAfter <= 708330) {
            $salaryDeduction = floor($syahoAfter * 0.1 + 91667);
        } else {
            $salaryDeduction = 162500;
        }

        $kiso = 0.0;
        if ($syahoAfter <= 2162499) {
            $kiso = 40000;
        } elseif ($syahoAfter <= 2204166) {
            $kiso = 26667;
        } elseif ($syahoAfter <= 2245833) {
            $kiso = 13334;
        }

        $fuyoCount = max(0.0, $this->payloadNumber($payload, 'fuyo_sum'));
        $fuyoDeduction = ($fuyoCount * 31667) + $kiso;
        $taxableBase = max(0.0, $syahoAfter - $salaryDeduction - $fuyoDeduction);

        $incomeTax = 0.0;
        if ($taxableBase <= 162500) {
            $incomeTax = round(($taxableBase * 0.05105) / 10, 0, PHP_ROUND_HALF_UP) * 10;
        } elseif ($taxableBase <= 275000) {
            $incomeTax = round((($taxableBase * 0.1021) - 8296) / 10, 0, PHP_ROUND_HALF_UP) * 10;
        } elseif ($taxableBase <= 579166) {
            $incomeTax = round((($taxableBase * 0.2042) - 36374) / 10, 0, PHP_ROUND_HALF_UP) * 10;
        } elseif ($taxableBase <= 750000) {
            $incomeTax = round((($taxableBase * 0.23483) - 54113) / 10, 0, PHP_ROUND_HALF_UP) * 10;
        } elseif ($taxableBase <= 1500000) {
            $incomeTax = round((($taxableBase * 0.33693) - 130688) / 10, 0, PHP_ROUND_HALF_UP) * 10;
        } elseif ($taxableBase <= 3333333) {
            $incomeTax = round((($taxableBase * 0.4084) - 237893) / 10, 0, PHP_ROUND_HALF_UP) * 10;
        } else {
            $incomeTax = round((($taxableBase * 0.45945) - 408061) / 10, 0, PHP_ROUND_HALF_UP) * 10;
        }

        if (mb_strpos($this->resolveStaffDivision($staffId), '業務委託') !== false) {
            $incomeTax = 0.0;
        }

        $payload['income_tax'] = $this->normalizeNumericInput(max(0.0, $incomeTax));
    }

    private function applyComputedTotals(array &$payload, string $staffId = ''): void
    {
        $syahoSum = $this->payloadNumber($payload, 'kenpo')
            + $this->payloadNumber($payload, 'kaigo')
            + $this->payloadNumber($payload, 'kounen')
            + $this->payloadNumber($payload, 'koyou');
        $payload['syaho_sum'] = $this->normalizeNumericInput($syahoSum);

        $supplySum = 0.0;
        $supplySum += $this->payloadNumber($payload, 'basic_salary');
        $supplySum += $this->payloadNumber($payload, 'officer_com');
        for ($i = 2; $i <= 17; $i++) {
            $supplySum += $this->payloadNumber($payload, 'allowance_amo_' . $i);
        }
        $supplySum += $this->payloadNumber($payload, 'traffic_addition');
        $supplySum += $this->payloadNumber($payload, 'leave_allowance');
        $supplySum += $this->payloadNumber($payload, 'late_deduction');
        $supplySum += $this->payloadNumber($payload, 'absence_deduction');

        $targets = $this->computeTargetsFromAllowanceSettings($payload, $staffId, $supplySum);
        $taxable = $targets['taxable'];
        $rouhoTarget = $targets['rouho_target'];
        $syahoTarget = $targets['syaho_target'];
        $nontaxable = max(0.0, $supplySum - $taxable);

        $payload['taxation_sum'] = $this->normalizeNumericInput($taxable);
        $payload['not_taxation_sum'] = $this->normalizeNumericInput($nontaxable);
        $payload['supply_sum'] = $this->normalizeNumericInput($supplySum);
        $payload['pay_total'] = $this->normalizeNumericInput($supplySum);
        $payload['rouho_target_sum'] = $this->normalizeNumericInput($rouhoTarget);
        $payload['syaho_target_sum'] = $this->normalizeNumericInput($syahoTarget);

        $otherTotal = 0.0;
        foreach (['adjustment_year_end', 'cost_liquidation', 'adjustment_cost'] as $key) {
            $otherTotal += $this->payloadNumber($payload, $key);
        }
        $payload['other_total'] = $this->normalizeNumericInput($otherTotal);

        $deductionSum = $syahoSum
            + $this->payloadNumber($payload, 'income_tax')
            + $this->payloadNumber($payload, 'resident_tax');
        $payload['deduction_sum'] = $this->normalizeNumericInput($deductionSum);
        $payload['deduction_total'] = $this->normalizeNumericInput($deductionSum);

        $netPay = $supplySum - $deductionSum + $otherTotal;
        $payload['supply_deduction_sum'] = $this->normalizeNumericInput($netPay);
        $payload['net_pay'] = $this->normalizeNumericInput($netPay);
        $payload['transfer_amo'] = $this->normalizeNumericInput($netPay);
    }

    private function applyEmploymentInsurance(array &$payload, string $staffId, string $payrollMonthDate): void
    {
        $staffId = trim($staffId);
        $monthDate = trim($payrollMonthDate);
        if ($staffId === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $monthDate)) {
            return;
        }

        $isBonus = $this->payloadNumber($payload, 'bonus') > 0
            || in_array(strtolower((string) ($payload['bonus'] ?? '')), ['true', '1'], true);
        $base = $isBonus
            ? $this->payloadNumber($payload, 'bonus_amo')
            : $this->payloadNumber($payload, 'rouho_target_sum');

        $ratePerMille = $this->resolveLaborInsuranceRate($monthDate);
        if ($ratePerMille <= 0 || $base <= 0) {
            $payload['koyou'] = 0;
            return;
        }

        $staffDivision = $this->resolveStaffDivision($staffId);
        if ($staffId === '001' || $staffDivision === '業務委託') {
            $payload['koyou'] = 0;
            return;
        }

        $koyou = round($base * ($ratePerMille / 1000), 0, PHP_ROUND_HALF_UP);
        $payload['koyou'] = $this->normalizeNumericInput($koyou);
    }

    private function resolveLaborInsuranceRate(string $payrollMonthDate): float
    {
        static $cache = [];
        if (isset($cache[$payrollMonthDate])) {
            return $cache[$payrollMonthDate];
        }

        $conn = DB::connection('sqlsrv_payroll');
        $candidates = [
            ['dbo.m_labor_insurance_rates', 'apply_date', 'general_employee_rate'],
            ['m_labor_insurance_rates', 'apply_date', 'general_employee_rate'],
            ['dbo.t_rouho', 'rou_apply_date', 'general_st'],
            ['t_rouho', 'rou_apply_date', 'general_st'],
        ];

        foreach ($candidates as [$table, $dateCol, $rateCol]) {
            try {
                if (!Schema::connection('sqlsrv_payroll')->hasTable($table)) {
                    continue;
                }
                if (!Schema::connection('sqlsrv_payroll')->hasColumn($table, $dateCol) || !Schema::connection('sqlsrv_payroll')->hasColumn($table, $rateCol)) {
                    continue;
                }
                $row = $conn->table($table)
                    ->whereRaw('CONVERT(date, ' . $this->wrap($dateCol) . ') <= ?', [$payrollMonthDate])
                    ->orderBy($dateCol, 'desc')
                    ->first([$rateCol]);
                if ($row !== null) {
                    $rate = (float) ($row->{$rateCol} ?? 0);
                    if ($rate > 0) {
                        $cache[$payrollMonthDate] = $rate;
                        return $rate;
                    }
                }
            } catch (\Throwable) {
                // no-op
            }
        }

        $cache[$payrollMonthDate] = 0.0;

        return 0.0;
    }

    private function resolveStaffDivision(string $staffId): string
    {
        static $cache = [];
        $staffId = trim($staffId);
        if ($staffId === '') {
            return '';
        }
        if (array_key_exists($staffId, $cache)) {
            return $cache[$staffId];
        }

        try {
            $table = $this->staffTable();
            $staffIdColumn = $this->staffColumn('staff_code', 'staff_id');
            $row = DB::connection('sqlsrv')
                ->table($table)
                ->whereRaw('LTRIM(RTRIM(' . $this->wrap($staffIdColumn) . ')) = ?', [$staffId])
                ->first(['staff_division']);
            $cache[$staffId] = trim((string) ($row->staff_division ?? ''));
            return $cache[$staffId];
        } catch (\Throwable) {
            $cache[$staffId] = '';
            return '';
        }
    }

    private function resolveStaffTaxAmount(string $staffId): string
    {
        $staffId = trim($staffId);
        if ($staffId === '') {
            return '';
        }

        try {
            $table = $this->staffTable();
            $staffIdColumn = $this->staffColumn('staff_code', 'staff_id');
            $row = DB::connection('sqlsrv')
                ->table($table)
                ->whereRaw('LTRIM(RTRIM(' . $this->wrap($staffIdColumn) . ')) = ?', [$staffId])
                ->first(['tax_amount']);
            return trim((string) ($row->tax_amount ?? ''));
        } catch (\Throwable) {
            return '';
        }
    }

    private function resolvePremiumDeductionBases(array $payload, string $staffId): array
    {
        $staffId = trim($staffId);
        $companyCode = $this->resolveStaffCompanyCode($staffId);
        $defs = $this->resolveAllowanceDefinitionsOrdered($companyCode);

        $premiumBase = 0.0;
        $deductionBase = 0.0;

        foreach ($defs as $def) {
            $key = trim((string) ($def['amount_column_key'] ?? ''));
            if ($key === '') {
                $slotNo = (int) ($def['slot_no'] ?? 0);
                if ($slotNo > 0) {
                    $key = 'allowance_amo_' . $slotNo;
                }
            }
            if ($key === '') {
                continue;
            }

            $amount = $this->payloadNumber($payload, $key);
            if ((int) ($def['warimasi_kiso'] ?? 0) === 1) {
                $premiumBase += $amount;
            }
            if ((int) ($def['koujyo_kiso'] ?? 0) === 1) {
                $deductionBase += $amount;
            }
        }

        if ($premiumBase <= 0) {
            $premiumBase = max(
                0.0,
                $this->payloadNumber($payload, 'allowance_amo_1'),
                $this->payloadNumber($payload, 'basic_salary')
            );
        }
        if ($deductionBase <= 0) {
            $deductionBase = max(
                0.0,
                $this->payloadNumber($payload, 'allowance_amo_1'),
                $this->payloadNumber($payload, 'basic_salary')
            );
        }

        return [
            'premium_base' => $premiumBase,
            'deduction_base' => $deductionBase,
        ];
    }

    private function resolveMonthlyWorkingHours(string $staffId, array $payload): float
    {
        $staffId = trim($staffId);
        if ($staffId !== '') {
            try {
                $table = $this->staffTable();
                $staffIdColumn = $this->staffColumn('staff_code', 'staff_id');
                $row = DB::connection('sqlsrv')
                    ->table($table)
                    ->whereRaw('LTRIM(RTRIM(' . $this->wrap($staffIdColumn) . ')) = ?', [$staffId])
                    ->first(['year_working_time']);

                $value = (float) ($this->normalizeNumericInput($row->year_working_time ?? 0));
                if ($value > 0) {
                    return $value;
                }
            } catch (\Throwable) {
                // no-op
            }
        }

        $fallback = $this->payloadNumber($payload, 'year_working_time');

        return $fallback > 0 ? $fallback : 0.0;
    }

    private function computeTargetsFromAllowanceSettings(array $payload, string $staffId, float $supplySum): array
    {
        $staffId = trim($staffId);
        if ($staffId === '') {
            return [
                'taxable' => max(0.0, $this->payloadNumber($payload, 'taxation_sum')),
                'rouho_target' => max(0.0, $this->payloadNumber($payload, 'rouho_target_sum')),
                'syaho_target' => max(0.0, $this->payloadNumber($payload, 'syaho_target_sum')),
            ];
        }

        $companyCode = $this->resolveStaffCompanyCode($staffId);
        $defs = $this->resolveAllowanceDefinitionsOrdered($companyCode);
        if ($defs === []) {
            return [
                'taxable' => max(0.0, $this->payloadNumber($payload, 'taxation_sum')),
                'rouho_target' => max(0.0, $this->payloadNumber($payload, 'rouho_target_sum')),
                'syaho_target' => max(0.0, $this->payloadNumber($payload, 'syaho_target_sum')),
            ];
        }

        $taxable = 0.0;
        $rouhoTarget = 0.0;
        $syahoTarget = 0.0;

        foreach ($defs as $def) {
            $key = trim((string) ($def['amount_column_key'] ?? ''));
            if ($key === '') {
                $slotNo = (int) ($def['slot_no'] ?? 0);
                if ($slotNo > 0) {
                    $key = 'allowance_amo_' . $slotNo;
                }
            }
            if ($key === '') {
                continue;
            }

            $amount = $this->payloadNumber($payload, $key);
            if ((int) ($def['tax_target'] ?? 0) === 1) {
                $taxable += $amount;
            }
            if ((int) ($def['rou_target'] ?? 0) === 1) {
                $rouhoTarget += $amount;
            }
            if ((int) ($def['syaho_target'] ?? 0) === 1) {
                $syahoTarget += $amount;
            }
        }

        $taxable = max(0.0, min($taxable, $supplySum));
        $rouhoTarget = max(0.0, min($rouhoTarget, $supplySum));
        $syahoTarget = max(0.0, min($syahoTarget, $supplySum));

        return [
            'taxable' => $taxable,
            'rouho_target' => $rouhoTarget,
            'syaho_target' => $syahoTarget,
        ];
    }

    private function resolveAllowanceDefinitionsOrdered(?string $companyCode): array
    {
        $companyCode = trim((string) ($companyCode ?? ''));
        if ($companyCode === '') {
            return [];
        }

        try {
            $table = $this->resolveAllowanceTable();
            if ($table === null) {
                return [];
            }

            $columns = $this->tableColumns('sqlsrv_payroll', $table);
            if ($columns === []) {
                return [];
            }

            $hasSlotNo = in_array('slot_no', $columns, true);
            $hasAmountKey = in_array('amount_column_key', $columns, true);
            $hasDisplayOrder = in_array('display_order', $columns, true);
            $hasTaxTarget = in_array('tax_target', $columns, true);
            $hasSyahoTarget = in_array('syaho_target', $columns, true);
            $hasRouTarget = in_array('rou_target', $columns, true);
            $hasWarimasiKiso = in_array('warimasi_kiso', $columns, true);
            $hasKoujyoKiso = in_array('koujyo_kiso', $columns, true);

            $rows = DB::connection('sqlsrv_payroll')
                ->table($table)
                ->whereRaw('LTRIM(RTRIM(office_name)) = ?', [$companyCode])
                ->whereNotNull('allowance_name')
                ->whereRaw("LTRIM(RTRIM(allowance_name)) <> ''")
                ->orderByRaw($hasDisplayOrder ? 'display_order asc, allowance_no asc' : 'allowance_no asc')
                ->get([
                    'allowance_no',
                    'allowance_name',
                    DB::raw($hasSlotNo ? 'slot_no' : 'NULL as slot_no'),
                    DB::raw($hasAmountKey ? 'amount_column_key' : 'NULL as amount_column_key'),
                    DB::raw($hasDisplayOrder ? 'display_order' : 'NULL as display_order'),
                    DB::raw($hasTaxTarget ? 'tax_target' : '0 as tax_target'),
                    DB::raw($hasSyahoTarget ? 'syaho_target' : '0 as syaho_target'),
                    DB::raw($hasRouTarget ? 'rou_target' : '0 as rou_target'),
                    DB::raw($hasWarimasiKiso ? 'warimasi_kiso' : '0 as warimasi_kiso'),
                    DB::raw($hasKoujyoKiso ? 'koujyo_kiso' : '0 as koujyo_kiso'),
                ]);

            $defs = [];
            foreach ($rows as $row) {
                $slotNo = (int) ($row->slot_no ?? 0);
                $amountKey = trim((string) ($row->amount_column_key ?? ''));
                if ($slotNo <= 0 && $amountKey === '') {
                    continue;
                }

                $defs[] = [
                    'allowance_no' => (int) ($row->allowance_no ?? 0),
                    'slot_no' => $slotNo,
                    'allowance_name' => trim((string) ($row->allowance_name ?? '')),
                    'amount_column_key' => $amountKey,
                    'display_order' => (int) ($row->display_order ?? 0),
                    'tax_target' => (int) ($row->tax_target ?? 0),
                    'syaho_target' => (int) ($row->syaho_target ?? 0),
                    'rou_target' => (int) ($row->rou_target ?? 0),
                    'warimasi_kiso' => (int) ($row->warimasi_kiso ?? 0),
                    'koujyo_kiso' => (int) ($row->koujyo_kiso ?? 0),
                ];
            }

            return $defs;
        } catch (\Throwable) {
            return [];
        }
    }

    private function resolveStaffCompanyCode(string $staffId): string
    {
        $staffId = trim($staffId);
        if ($staffId === '') {
            return '';
        }

        try {
            $staffTable = $this->staffTable();
            $storeTable = $this->storeTable();
            $companyTable = $this->companyTable();
            $staffStoreColumn = $this->staffColumn('store_code', 'section');
            $staffIdColumn = $this->staffColumn('staff_code', 'staff_id');

            $row = DB::connection('sqlsrv')
                ->table($staffTable . ' as ms')
                ->leftJoin($storeTable . ' as st', 'ms.' . $staffStoreColumn, '=', 'st.store_code')
                ->leftJoin($companyTable . ' as mc', 'st.company_name', '=', 'mc.company_name')
                ->whereRaw('LTRIM(RTRIM(ms.' . $staffIdColumn . ')) = ?', [$staffId])
                ->first([
                    DB::raw('mc.company_code as mc_company_code'),
                    DB::raw('mc.company_id as mc_company_id'),
                    DB::raw('st.company_name as st_company_name'),
                ]);

            if ($row === null) {
                return '';
            }

            foreach (['mc_company_code', 'mc_company_id', 'st_company_name'] as $col) {
                $value = trim((string) ($row->{$col} ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        } catch (\Throwable) {
            return '';
        }

        return '';
    }

    private function resolvePayrollMasterTable(array $candidates): ?string
    {
        foreach ($candidates as $table) {
            try {
                if (Schema::connection('sqlsrv_payroll')->hasTable($table)) {
                    return $table;
                }
            } catch (\Throwable) {
                // no-op
            }
        }

        return null;
    }

    private function payrollTableHasColumn(string $table, string $column): bool
    {
        try {
            return Schema::connection('sqlsrv_payroll')->hasColumn($table, $column);
        } catch (\Throwable) {
            return false;
        }
    }

    private function rowValue(mixed $row, string $column): mixed
    {
        if (is_object($row) && property_exists($row, $column)) {
            return $row->{$column};
        }

        if (is_array($row) && array_key_exists($column, $row)) {
            return $row[$column];
        }

        return null;
    }

    private function resolveAllowanceTable(): ?string
    {
        foreach (['dbo.mx_allowance', 'mx_allowance', 'dbo.m_allowance', 'm_allowance', 'dbo.t_allowance', 't_allowance'] as $table) {
            try {
                if (Schema::connection('sqlsrv_payroll')->hasTable($table)) {
                    return $table;
                }
            } catch (\Throwable) {
                // no-op
            }
        }

        return null;
    }

    private function payrollTable(): string
    {
        foreach (['dbo.m_payroll_entries', 'm_payroll_entries', 'dbo.mx_kyuyo_shou', 'mx_kyuyo_shou'] as $table) {
            if (Schema::connection('sqlsrv_payroll')->hasTable($table)) {
                return $table;
            }
        }

        return 'dbo.m_payroll_entries';
    }

    private function payrollColumn(string $preferred, ?string $fallback): string
    {
        $table = $this->payrollTable();
        if (Schema::connection('sqlsrv_payroll')->hasColumn($table, $preferred)) {
            return $preferred;
        }
        if ($fallback !== null && Schema::connection('sqlsrv_payroll')->hasColumn($table, $fallback)) {
            return $fallback;
        }

        return $preferred;
    }

    private function staffTable(): string
    {
        foreach (['dbo.mx_staffs', 'mx_staffs', 'dbo.m_staffs', 'm_staffs'] as $table) {
            if (Schema::connection('sqlsrv')->hasTable($table)) {
                return $table;
            }
        }

        return 'dbo.m_staffs';
    }

    private function storeTable(): string
    {
        foreach (['dbo.mx_stores', 'mx_stores', 'dbo.m_stores', 'm_stores'] as $table) {
            if (Schema::connection('sqlsrv')->hasTable($table)) {
                return $table;
            }
        }

        return 'dbo.m_stores';
    }

    private function companyTable(): string
    {
        foreach (['dbo.mx_companies', 'mx_companies', 'dbo.m_companies', 'm_companies'] as $table) {
            if (Schema::connection('sqlsrv')->hasTable($table)) {
                return $table;
            }
        }

        return 'dbo.m_companies';
    }

    private function staffColumn(string $preferred, string $fallback): string
    {
        $table = $this->staffTable();
        if (Schema::connection('sqlsrv')->hasColumn($table, $preferred)) {
            return $preferred;
        }
        if (Schema::connection('sqlsrv')->hasColumn($table, $fallback)) {
            return $fallback;
        }

        return $preferred;
    }

    private function tableColumns(string $connection, string $table): array
    {
        try {
            return Schema::connection($connection)->getColumnListing($table);
        } catch (\Throwable) {
            return [];
        }
    }

    private function normalizeNumericInput(mixed $value): mixed
    {
        if ($value === null) {
            return 0;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return 0;
        }

        $normalized = str_replace([',', ' ', '　'], '', $text);
        if (!preg_match('/^-?\d+(\.\d+)?$/', $normalized)) {
            return $text;
        }

        return str_contains($normalized, '.') ? (float) $normalized : (int) $normalized;
    }

    private function payloadNumber(array $payload, string $key): float
    {
        if (!array_key_exists($key, $payload)) {
            return 0.0;
        }

        $normalized = $this->normalizeNumericInput($payload[$key]);
        if (is_int($normalized) || is_float($normalized)) {
            return (float) $normalized;
        }
        if (is_string($normalized)) {
            $s = str_replace([',', ' ', '　'], '', trim($normalized));
            if ($s !== '' && is_numeric($s)) {
                return (float) $s;
            }
        }

        return 0.0;
    }

    private function wrap(string $column): string
    {
        return '[' . str_replace(']', ']]', $column) . ']';
    }
}

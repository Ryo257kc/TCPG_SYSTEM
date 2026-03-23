<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PayrollV2BonusSocialInsuranceService
{
    public function __construct(
        private readonly PayrollV2ShahoService $shahoService,
    ) {
    }

    public function recalculate(string $staffId, string $paymentDate): int
    {
        $staffId = trim($staffId);
        $paymentDate = trim($paymentDate);

        if ($staffId === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $paymentDate) !== 1) {
            return 0;
        }

        $row = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 1)
            ->whereRaw('LTRIM(RTRIM([kyuyo_staff_id])) = ?', [$staffId])
            ->whereRaw('CONVERT(date, [supply_month]) = ?', [$paymentDate])
            ->orderByDesc('kyuyo_sho_no')
            ->first(['kyuyo_sho_no', 'bonus_amo']);

        if (!$row || !isset($row->kyuyo_sho_no)) {
            return 0;
        }

        $year = (int) substr($paymentDate, 0, 4);
        $month = (int) substr($paymentDate, 5, 2);
        $companyId = $this->resolveCompanyId($staffId);
        $bonusRates = $this->loadBonusRates($companyId, $paymentDate);
        $birthday = $this->loadBirthday($staffId);

        $gross = $this->num($row->bonus_amo ?? 0);
        $currentStandard = floor($gross / 1000) * 1000;
        $targets = $this->resolveTargetStandards($staffId, (int) $row->kyuyo_sho_no, $paymentDate, $currentStandard);

        $kenpo = $this->insuranceAmount($targets['kenpo_target_standard'], $bonusRates['kenpo_shoyo'] ?? null);
        $kaigo = $this->shouldApplyKaigo($birthday, $year, $month)
            ? $this->insuranceAmount($targets['kenpo_target_standard'], $bonusRates['kaigo_shoyo'] ?? null)
            : 0;
        $kounen = $this->insuranceAmount($targets['kounen_target_standard'], $bonusRates['kou_shoyo'] ?? null);

        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_sho_no', (int) $row->kyuyo_sho_no)
            ->update([
                'kenpo' => $kenpo,
                'kaigo' => $kaigo,
                'kounen' => $kounen,
                'syaho_sum' => $kenpo + $kaigo + $kounen + $this->num($this->loadCurrentValue((int) $row->kyuyo_sho_no, 'koyou')),
            ]);
    }

    /** @return array{kenpo_target_standard:float,kounen_target_standard:float} */
    private function resolveTargetStandards(string $staffId, int $currentRowId, string $paymentDate, float $currentStandard): array
    {
        $selectedTs = strtotime($paymentDate);
        if ($selectedTs === false) {
            return [
                'kenpo_target_standard' => 0.0,
                'kounen_target_standard' => 0.0,
            ];
        }

        $sameMonthStart = date('Y-m-01', $selectedTs);
        $sameMonthEnd = date('Y-m-t', $selectedTs);
        $fiscalStart = ((int) date('n', $selectedTs) >= 4)
            ? date('Y-04-01', $selectedTs)
            : date('Y-04-01', strtotime('-1 year', $selectedTs));

        $historyRows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->select(['kyuyo_sho_no', 'supply_month', 'bonus_amo'])
            ->where('bonus', 1)
            ->whereRaw('LTRIM(RTRIM([kyuyo_staff_id])) = ?', [$staffId])
            ->whereNotNull('supply_month')
            ->whereRaw('CONVERT(date, [supply_month]) >= ?', [$fiscalStart])
            ->whereRaw('CONVERT(date, [supply_month]) <= ?', [$sameMonthEnd])
            ->where('kyuyo_sho_no', '<>', $currentRowId)
            ->get();

        $sameMonthOtherStandard = 0.0;
        $fiscalPrior = 0.0;

        foreach ($historyRows as $historyRow) {
            $historyDate = trim((string) ($historyRow->supply_month ?? ''));
            $historyTs = strtotime($historyDate);
            if ($historyTs === false) {
                continue;
            }

            $historyStandard = floor($this->num($historyRow->bonus_amo ?? 0) / 1000) * 1000;
            $historyYmd = date('Y-m-d', $historyTs);

            if ($historyYmd < $paymentDate) {
                $fiscalPrior += $historyStandard;
            }

            if ($historyYmd >= $sameMonthStart && $historyYmd <= $sameMonthEnd) {
                $sameMonthOtherStandard += $historyStandard;
            }
        }

        $kenpoCapRemaining = max(5730000 - $fiscalPrior, 0);
        $kenpoTargetStandard = min($currentStandard, $kenpoCapRemaining);

        $kounenCapRemaining = max(1500000 - $sameMonthOtherStandard, 0);
        $kounenTargetStandard = min($currentStandard, $kounenCapRemaining);

        return [
            'kenpo_target_standard' => $kenpoTargetStandard,
            'kounen_target_standard' => $kounenTargetStandard,
        ];
    }

    private function insuranceAmount(float $targetStandard, mixed $rate): int
    {
        $rateNum = $this->num($rate);
        if ($targetStandard <= 0 || $rateNum <= 0) {
            return 0;
        }

        return (int) ceil($targetStandard * ($rateNum / 1000));
    }

    /** @return array{kenpo_shoyo:float,kaigo_shoyo:float,kou_shoyo:float} */
    private function loadBonusRates(string $companyId, string $paymentDate): array
    {
        $query = DB::connection('sqlsrv_payroll')->table('dbo.mx_syaho');
        if ($companyId !== '' && $this->hasPayrollColumn('mx_syaho', 'office_no')) {
            $query->whereRaw('LTRIM(RTRIM(CAST(office_no AS nvarchar(50)))) = ?', [$companyId]);
        }

        $rows = $query
            ->orderByDesc('syaho_no')
            ->get([
                'kenpo_apply_date',
                'kenpo_shoyo',
                'kaigo_apply_date',
                'kaigo_shoyo',
                'kou_apply_date',
                'kou_shoyo',
            ]);

        return [
            'kenpo_shoyo' => $this->pickApplicableRate($rows, 'kenpo_apply_date', 'kenpo_shoyo', $paymentDate),
            'kaigo_shoyo' => $this->pickApplicableRate($rows, 'kaigo_apply_date', 'kaigo_shoyo', $paymentDate),
            'kou_shoyo' => $this->pickApplicableRate($rows, 'kou_apply_date', 'kou_shoyo', $paymentDate),
        ];
    }

    private function pickApplicableRate(iterable $rows, string $applyDateColumn, string $rateColumn, string $paymentDate): float
    {
        foreach ($rows as $row) {
            $applyDate = trim((string) ($row->{$applyDateColumn} ?? ''));
            $rate = $this->num($row->{$rateColumn} ?? null);
            if ($rate <= 0) {
                continue;
            }

            if ($applyDate === '' || substr($applyDate, 0, 10) <= $paymentDate) {
                return $rate;
            }
        }

        return 0.0;
    }

    private function loadCurrentValue(int $rowId, string $column): mixed
    {
        $row = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_sho_no', $rowId)
            ->first([$column]);

        return $row?->{$column} ?? 0;
    }

    private function loadBirthday(string $staffId): ?\DateTimeImmutable
    {
        $row = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->whereRaw('LTRIM(RTRIM(staff_id)) = ?', [$staffId])
            ->first(['birthday']);

        return $this->toDate($row->birthday ?? null);
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

    private function hasPayrollColumn(string $table, string $column): bool
    {
        try {
            return Schema::connection('sqlsrv_payroll')->hasColumn($table, $column)
                || Schema::connection('sqlsrv_payroll')->hasColumn('dbo.' . $table, $column);
        } catch (\Throwable) {
            return false;
        }
    }

    private function shouldApplyKaigo(?\DateTimeImmutable $birthday, int $year, int $month): bool
    {
        if ($birthday === null) {
            return false;
        }

        $start = $birthday->modify('+40 years')->modify('-1 day');
        $targetMonthStart = new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month));

        return $targetMonthStart >= $start->modify('first day of this month')->setTime(0, 0);
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

    private function num(mixed $value): float
    {
        if ($value === null) {
            return 0.0;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return 0.0;
        }

        $text = str_replace([',', ' '], '', $text);
        return is_numeric($text) ? (float) $text : 0.0;
    }
}

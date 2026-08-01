<?php

namespace App\Services\Admin\V2\Payroll;

use App\Services\Admin\V2\Attendance\AttendanceV2ConfirmedStateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PayrollV2UpdateService
{
    public function __construct(
        private readonly AttendanceV2ConfirmedStateService $confirmedStateService,
    ) {}

    /**
     * 給与明細の値を保存し、同じ更新内で最終合計も再計算する。
     *
     * 手入力保存や各種反映サービスから呼ぶ入口。ここを通すと rebuildTotals() の合計式も必ず反映される。
     *
     * @param array<string,mixed> $values
     */
    public function save(string $staffId, int $year, int $month, array $values, ?string $companyName = null, bool $bonus = false): int
    {
        $staffId = trim($staffId);
        if ($staffId === '' || $year < 2000 || $month < 1 || $month > 12) {
            return 0;
        }

        $payload = $this->sanitizePayload($values);
        if ($payload === []) {
            return 0;
        }

        $row = $this->targetRow($staffId, $year, $month, $bonus);

        if (!$row || !isset($row->kyuyo_sho_no)) {
            return 0;
        }

        $rowId = (int) $row->kyuyo_sho_no;
        $current = (array) (DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_sho_no', $rowId)
            ->first() ?? []);

        $merged = array_merge($current, $payload);
        $commutingCorrections = $this->fixedCommutingCorrections($merged, $payload);
        $outsourceCorrections = $this->outsourcePayrollCorrections($merged);
        $merged = array_merge($merged, $commutingCorrections, $outsourceCorrections);
        $derived = $this->rebuildTotals($merged, $companyName);
        $updatePayload = array_merge($payload, $commutingCorrections, $outsourceCorrections, $derived);

        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_sho_no', $rowId)
            ->update($updatePayload);
    }

    /**
     * 既存の給与明細行を読み直して、最終合計だけを再計算する。
     *
     * 雇用保険・所得税など、明細の一部だけを直接更新した後の仕上げとして使う。
     */
    public function refreshTotals(string $staffId, int $year, int $month, ?string $companyName = null, bool $bonus = false): int
    {
        $staffId = trim($staffId);
        if ($staffId === '' || $year < 2000 || $month < 1 || $month > 12) {
            return 0;
        }

        $row = $this->targetRow($staffId, $year, $month, $bonus);
        if (!$row || !isset($row->kyuyo_sho_no)) {
            return 0;
        }

        $rowId = (int) $row->kyuyo_sho_no;
        $current = (array) (DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_sho_no', $rowId)
            ->first() ?? []);

        if ($current === []) {
            return 0;
        }

        $outsourceCorrections = $this->outsourcePayrollCorrections($current);
        $current = array_merge($current, $outsourceCorrections);
        $derived = $this->rebuildTotals($current, $companyName);

        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_sho_no', $rowId)
            ->update(array_merge($outsourceCorrections, $derived));
    }

    /**
     * 賞与明細の値を保存し、同じ更新内で賞与用の最終合計も再計算する。
     *
     * 賞与の合計式を変えるときは rebuildBonusTotals() を正本として見る。
     *
     * @param array<string,mixed> $values
     */
    public function saveBonus(string $staffId, int $year, int $month, array $values): int
    {
        $staffId = trim($staffId);
        if ($staffId === '' || $year < 2000 || $month < 1 || $month > 12) {
            return 0;
        }

        $payload = $this->sanitizePayload($values);
        $row = $this->targetRow($staffId, $year, $month, true);
        if (!$row || !isset($row->kyuyo_sho_no)) {
            return 0;
        }

        $rowId = (int) $row->kyuyo_sho_no;
        $current = (array) (DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_sho_no', $rowId)
            ->first() ?? []);

        $merged = array_merge($current, $payload);
        $outsourceCorrections = $this->outsourcePayrollCorrections($merged);
        $merged = array_merge($merged, $outsourceCorrections);
        $derived = $this->rebuildBonusTotals($merged);
        $updatePayload = array_merge($payload, $outsourceCorrections, $derived);

        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_sho_no', $rowId)
            ->update($updatePayload);
    }

    public function refreshBonusTotals(string $staffId, int $year, int $month): int
    {
        return $this->saveBonus($staffId, $year, $month, []);
    }

    public function isAttendanceChecked(string $staffId, int $year, int $month): bool
    {
        $staffId = trim($staffId);
        if ($staffId === '' || $year < 2000 || $month < 1 || $month > 12) {
            return false;
        }

        [$attendanceYear, $attendanceMonth] = $month <= 1
            ? [$year - 1, 12]
            : [$year, $month - 1];

        $map = $this->confirmedStateService->mapByStaffIds([$staffId], $attendanceYear, $attendanceMonth);

        return (bool) ($map[$staffId] ?? false);
    }

    public function setPayrollConfirmed(string $staffId, int $year, int $month, bool $confirmed, bool $bonus = false): int
    {
        $staffId = trim($staffId);
        if ($staffId === '' || $year < 2000 || $month < 1 || $month > 12) {
            return 0;
        }

        $row = $this->targetRow($staffId, $year, $month, $bonus);
        if (!$row || !isset($row->kyuyo_sho_no)) {
            return 0;
        }

        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_sho_no', (int) $row->kyuyo_sho_no)
            ->update([
                'edit_lock' => $confirmed ? 1 : 0,
            ]);
    }

    /** @param array<string,mixed> $values @return array<string,mixed> */
    private function sanitizePayload(array $values): array
    {
        $allowed = array_flip($this->updatableColumns());
        $aliases = $this->columnAliases();
        $textColumns = ['kyuyo_memo'];
        $negativeEarningColumns = ['late_deduction', 'absence_deduction', 'leave_allowance'];
        $out = [];

        foreach ($values as $key => $raw) {
            $k = trim((string) $key);
            if ($k === '') {
                continue;
            }

            $column = $aliases[$k] ?? $k;
            if (!isset($allowed[$column])) {
                continue;
            }

            if (in_array($column, $textColumns, true)) {
                $out[$column] = trim((string) $raw);
                continue;
            }

            $v = trim((string) $raw);
            if ($v === '') {
                $out[$column] = null;
                continue;
            }

            $v = str_replace([',', ' '], '', $v);
            if (!is_numeric($v)) {
                continue;
            }

            $amount = str_contains($v, '.') ? (float) $v : (int) $v;
            if (in_array($column, $negativeEarningColumns, true)) {
                $amount = -abs($amount);
            }
            $out[$column] = $amount;
        }

        return $out;
    }

    /** @return array<string,string> */
    private function columnAliases(): array
    {
        return [
            'work_holiday_num' => 'work_horiday_num',
            'holiday_true' => 'horiday_true',
            'holiday_true_num' => 'horiday_true_num',
        ];
    }

    // 給与計算フォーム
    /**
     * 給与明細の最終合計を作る正本。
     *
     * 支給合計、控除合計、課税/非課税、社保控除後、固定賃金・労保対象・社保対象はここで再計算する。
     * 給与明細を更新する処理は、最後に save() または refreshTotals() を通してこの式に集約する。
     *
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function rebuildTotals(array $row, ?string $companyName = null): array
    {
        $meta = $this->allowanceMetaMap($companyName);

        $manualYakuin = ['allowance_amo_2'];
        $taxableFallbackAllowanceKeys = ['allowance_amo_4'];
        $negativeEarningKeys = ['late_deduction', 'absence_deduction', 'leave_allowance'];

        $earningKeys = [];
        foreach (array_keys($meta) as $key) {
            $earningKeys[$key] = true;
        }

        $taxable = 0.0;
        $nonTaxable = 0.0;
        $kotei = 0.0;
        $rouhoTarget = 0.0;
        $syahoTarget = 0.0;
        $yakuin = 0.0;

        foreach (array_keys($earningKeys) as $key) {
            $amount = $this->num($row[$key] ?? 0);
            if (in_array($key, $negativeEarningKeys, true)) {
                $amount = -abs($amount);
            }
            if (abs($amount) < 0.0000001) {
                continue;
            }

            $flags = $meta[$key] ?? null;
            if (!$flags) {
                continue;
            }

            $taxTarget = (int) ($flags['tax_target'] ?? 0);
            $koteiWage = (int) ($flags['kotei_wage'] ?? 0);
            $rouTarget = (int) ($flags['rou_target'] ?? 0);
            $syahoTargetFlag = (int) ($flags['syaho_target'] ?? 0);

            if (
                in_array($key, $taxableFallbackAllowanceKeys, true)
                && $taxTarget !== 1
                && $koteiWage !== 1
                && $rouTarget !== 1
                && $syahoTargetFlag !== 1
            ) {
                $taxTarget = 1;
                $rouTarget = 1;
                $syahoTargetFlag = 1;
            }

            if ($taxTarget !== 1 && $koteiWage !== 1 && $rouTarget !== 1 && $syahoTargetFlag !== 1) {
                continue;
            }

            if ($taxTarget === 1) {
                $taxable += $amount;
            } else {
                $nonTaxable += $amount;
            }

            if ($koteiWage === 1) {
                $kotei += $amount;
            }
            if ($rouTarget === 1) {
                $rouhoTarget += $amount;
            }
            if ($syahoTargetFlag === 1) {
                $syahoTarget += $amount;
            }
            if (in_array($key, $manualYakuin, true)) {
                $yakuin += $amount;
            }
        }

        $syaho = $this->socialInsuranceSum($row);
        $deduction = $this->deductionSum($row, $syaho);

        $taxable = max(0.0, round($taxable, 0));
        $nonTaxable = max(0.0, round($nonTaxable, 0));
        if ($this->isOutsourcePayrollRow($row)) {
            $rouhoTarget = 0.0;
            $syahoTarget = 0.0;
        }

        return [
            'taxation_sum' => (int) $taxable,
            'not_taxation_sum' => (int) $nonTaxable,
            'supply_sum' => (int) round($taxable + $nonTaxable, 0),
            'kotei_sum' => (int) round(max(0.0, $kotei), 0),
            'rouho_target_sum' => (int) round(max(0.0, $rouhoTarget), 0),
            'syaho_target_sum' => (int) round(max(0.0, $syahoTarget), 0),
            'yakuin_sum' => (int) round(max(0.0, $yakuin), 0),
            'syaho_sum' => (int) round(max(0.0, $syaho), 0),
            'deduction_sum' => (int) round(max(0.0, $deduction), 0),
            'syaho_deduction_sum' => (int) round(max(0.0, $taxable - $syaho), 0),
        ];
    }

    /**
     * 賞与明細の最終合計を作る正本。
     *
     * 月給と同じく、賞与側も支給合計・控除合計・社保控除後をここに集約する。
     *
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function rebuildBonusTotals(array $row): array
    {
        $bonusAmount = $this->num($row['bonus_amo'] ?? 0);
        $taxationSum = max(0.0, round($bonusAmount, 0));
        $notTaxationSum = 0.0;
        $supplySum = $taxationSum + $notTaxationSum;
        $isOutsource = $this->isOutsourcePayrollRow($row);
        $rouhoTargetSum = $isOutsource ? 0.0 : $taxationSum;
        $syahoSum = $isOutsource ? 0.0 : $this->socialInsuranceSum($row);
        $deductionSum = $this->deductionSum($row, $syahoSum);

        return [
            'taxation_sum' => (int) $taxationSum,
            'not_taxation_sum' => (int) $notTaxationSum,
            'supply_sum' => (int) $supplySum,
            'rouho_target_sum' => (int) $rouhoTargetSum,
            'syaho_sum' => (int) round($syahoSum, 0),
            'deduction_sum' => (int) round($deductionSum, 0),
            'syaho_deduction_sum' => (int) round(max(0.0, $taxationSum - $syahoSum), 0),
        ];
    }

    /** @param array<string,mixed> $row */
    private function socialInsuranceSum(array $row): float
    {
        return $this->num($row['kenpo'] ?? 0)
            + $this->num($row['kaigo'] ?? 0)
            + $this->num($row['child_support_funds'] ?? 0)
            + $this->num($row['kounen'] ?? 0)
            + $this->num($row['koyou'] ?? 0);
    }

    /** @param array<string,mixed> $row */
    private function deductionSum(array $row, float $socialInsuranceSum): float
    {
        return $socialInsuranceSum
            + $this->num($row['income_tax'] ?? 0)
            + $this->num($row['resident_tax'] ?? 0)
            + $this->num($row['rent_cost'] ?? 0)
            + $this->num($row['adjustment_cost'] ?? 0)
            + $this->num($row['koujyo_1'] ?? 0);
    }
    /** @return array<string,array{tax_target:int,kotei_wage:int,rou_target:int,syaho_target:int}> */
    private function allowanceMetaMap(?string $companyName = null): array
    {
        static $cached = [];
        $cacheKey = trim((string) $companyName);
        if (isset($cached[$cacheKey])) {
            return $cached[$cacheKey];
        }

        $query = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_allowance')
            ->select('amount_column_key', 'tax_target', 'kotei_wage', 'rou_target', 'syaho_target')
            ->whereNotNull('amount_column_key')
            ->whereRaw("LTRIM(RTRIM(amount_column_key)) <> ''");

        $officeNames = $this->resolveOfficeNamesFromCompanyName($companyName);
        if ($officeNames !== []) {
            $query->whereIn(DB::raw("LTRIM(RTRIM(CAST(office_name as nvarchar(50))))"), $officeNames);
        }

        $rows = $query->get();

        $map = [];
        foreach ($rows as $row) {
            $key = trim((string) ($row->amount_column_key ?? ''));
            if ($key === '') {
                continue;
            }

            $current = $map[$key] ?? ['tax_target' => 0, 'kotei_wage' => 0, 'rou_target' => 0, 'syaho_target' => 0];
            $map[$key] = [
                'tax_target' => max($current['tax_target'], (((int) ($row->tax_target ?? 0)) === 1 ? 1 : 0)),
                'kotei_wage' => max($current['kotei_wage'], (((int) ($row->kotei_wage ?? 0)) === 1 ? 1 : 0)),
                'rou_target' => max($current['rou_target'], (((int) ($row->rou_target ?? 0)) === 1 ? 1 : 0)),
                'syaho_target' => max($current['syaho_target'], (((int) ($row->syaho_target ?? 0)) === 1 ? 1 : 0)),
            ];
        }

        $cached[$cacheKey] = $map;
        return $cached[$cacheKey];
    }

    /** @return list<string> */
    private function resolveOfficeNamesFromCompanyName(?string $companyName): array
    {
        $name = trim((string) $companyName);
        if ($name === '') {
            return [];
        }

        $rows = DB::connection('sqlsrv')
            ->table('dbo.mx_companies')
            ->select('company_id')
            ->whereRaw('LTRIM(RTRIM(company_name)) = ?', [$name])
            ->get();

        $candidates = [];
        foreach ($rows as $row) {
            $value = trim((string) ($row->company_id ?? ''));
            if ($value !== '') {
                $candidates[$value] = true;
            }
        }

        return array_keys($candidates);
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

    /** @param array<string,mixed> $row @return array<string,int> */
    private function fixedCommutingCorrections(array $row, array $manualPayload = []): array
    {
        $staffId = trim((string) ($row['kyuyo_staff_id'] ?? ''));
        $paymentDate = $this->toDate($row['supply_month'] ?? null);
        if ($staffId === '' || $paymentDate === null) {
            return [];
        }

        $kihon = $this->kihonForPaymentDate($staffId, $paymentDate);
        $fixedCommuting = $this->num($kihon['traffic_pay'] ?? 0);
        if ($fixedCommuting <= 0) {
            return [];
        }
        if (array_key_exists('allowance_amo_6', $manualPayload) || array_key_exists('allowance_amo_10', $manualPayload)) {
            return [];
        }

        return [
            'allowance_amo_6' => (int) round($fixedCommuting, 0),
            'allowance_amo_10' => 0,
        ];
    }

    /** @param array<string,mixed> $row @return array<string,int> */
    private function outsourcePayrollCorrections(array $row): array
    {
        if (!$this->isOutsourcePayrollRow($row)) {
            return [];
        }

        return [
            'kenpo' => 0,
            'kaigo' => 0,
            'kounen' => 0,
            'koyou' => 0,
            'income_tax' => 0,
            'koyou_office' => 0,
            'jidou_office' => 0,
            'rousai_office' => 0,
            'child_support_funds' => 0,
            'rouho_target_sum' => 0,
            'syaho_target_sum' => 0,
        ];
    }

    /** @param array<string,mixed> $row */
    private function isOutsourcePayrollRow(array $row): bool
    {
        $staffId = trim((string) ($row['kyuyo_staff_id'] ?? ''));
        if ($staffId === '') {
            return false;
        }

        $division = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->whereRaw('LTRIM(RTRIM([staff_id])) = ?', [$staffId])
            ->value('staff_division');

        return str_contains(trim((string) $division), '業務委託');
    }

    /** @return array<string,mixed> */
    private function kihonForPaymentDate(string $staffId, \DateTimeImmutable $paymentDate): array
    {
        $targetDate = $paymentDate->modify('first day of this month')->format('Y-m-d');
        $base = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kihon')
            ->whereRaw('LTRIM(RTRIM(CAST(staff_id as nvarchar(50)))) = ?', [$staffId]);

        $row = (clone $base)
            ->whereRaw('CONVERT(date, decision_date) < ?', [$targetDate])
            ->orderByDesc('decision_date')
            ->first();

        if (!$row) {
            $row = $base->orderByDesc('decision_date')->first();
        }

        return (array) ($row ?? []);
    }

    private function toDate(mixed $value): ?\DateTimeImmutable
    {
        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return null;
        }

        $timestamp = strtotime($text);
        if ($timestamp === false) {
            return null;
        }

        return (new \DateTimeImmutable())->setTimestamp($timestamp);
    }

    /** @return list<string> */
    private function updatableColumns(): array
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $columns = Schema::connection('sqlsrv_payroll')->getColumnListing('mx_kyuyo_shou');
        $deny = [
            'kyuyo_sho_no',
            'kyuyo_staff_id',
            'supply_month',
            'bonus',
            'link',
        ];

        $cached = array_values(array_filter(
            array_map(static fn($c) => (string) $c, $columns),
            static fn($c) => !in_array($c, $deny, true)
        ));

        return $cached;
    }

    private function targetRow(string $staffId, int $year, int $month, bool $bonus = false): ?object
    {
        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', $bonus ? 1 : 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month])
            ->whereRaw('LTRIM(RTRIM([kyuyo_staff_id])) = ?', [$staffId])
            ->orderByDesc('kyuyo_sho_no')
            ->first(['kyuyo_sho_no']);
    }
}

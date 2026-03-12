<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PayrollV2UpdateService
{
    /** @param array<string,mixed> $values */
    public function save(string $staffId, int $year, int $month, array $values, ?string $companyName = null): int
    {
        $staffId = trim($staffId);
        if ($staffId === '' || $year < 2000 || $month < 1 || $month > 12) {
            return 0;
        }

        $payload = $this->sanitizePayload($values);
        if ($payload === []) {
            return 0;
        }

        $row = $this->targetRow($staffId, $year, $month);

        if (!$row || !isset($row->kyuyo_sho_no)) {
            return 0;
        }

        $rowId = (int) $row->kyuyo_sho_no;
        $current = (array) (DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_sho_no', $rowId)
            ->first() ?? []);

        $merged = array_merge($current, $payload);
        $derived = $this->rebuildTotals($merged, $companyName);
        $updatePayload = array_merge($payload, $derived);

        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_sho_no', $rowId)
            ->update($updatePayload);
    }

    public function refreshTotals(string $staffId, int $year, int $month, ?string $companyName = null): int
    {
        $staffId = trim($staffId);
        if ($staffId === '' || $year < 2000 || $month < 1 || $month > 12) {
            return 0;
        }

        $row = $this->targetRow($staffId, $year, $month);
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

        $derived = $this->rebuildTotals($current, $companyName);

        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_sho_no', $rowId)
            ->update($derived);
    }

    public function markAttendanceChecked(string $staffId, int $year, int $month, string $checkedBy): int
    {
        return $this->setAttendanceChecked($staffId, $year, $month, true, $checkedBy);
    }

    public function setAttendanceChecked(string $staffId, int $year, int $month, bool $checked, string $checkedBy): int
    {
        $staffId = trim($staffId);
        if ($staffId === '' || $year < 2000 || $month < 1 || $month > 12) {
            return 0;
        }

        $row = $this->targetRow($staffId, $year, $month);
        if (!$row || !isset($row->kyuyo_sho_no)) {
            return 0;
        }

        $payload = [
            'attendance_checked' => $checked ? 1 : 0,
            'attendance_checked_at' => $checked ? now() : null,
            'attendance_checked_by' => $checked ? trim($checkedBy) : null,
        ];

        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_sho_no', (int) $row->kyuyo_sho_no)
            ->update($payload);
    }

    public function isAttendanceChecked(string $staffId, int $year, int $month): bool
    {
        $staffId = trim($staffId);
        if ($staffId === '' || $year < 2000 || $month < 1 || $month > 12) {
            return false;
        }

        $row = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month])
            ->whereRaw('LTRIM(RTRIM([kyuyo_staff_id])) = ?', [$staffId])
            ->orderByDesc('kyuyo_sho_no')
            ->first(['attendance_checked']);

        return ((int) ($row->attendance_checked ?? 0)) === 1;
    }

    public function setPayrollConfirmed(string $staffId, int $year, int $month, bool $confirmed): int
    {
        $staffId = trim($staffId);
        if ($staffId === '' || $year < 2000 || $month < 1 || $month > 12) {
            return 0;
        }

        $row = $this->targetRow($staffId, $year, $month);
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
        $textColumns = ['kyuyo_memo'];
        $out = [];

        foreach ($values as $key => $raw) {
            $k = trim((string) $key);
            if ($k === '' || !isset($allowed[$k])) {
                continue;
            }

            if (in_array($k, $textColumns, true)) {
                $out[$k] = trim((string) $raw);
                continue;
            }

            $v = trim((string) $raw);
            if ($v === '') {
                $out[$k] = null;
                continue;
            }

            $v = str_replace([',', ' '], '', $v);
            if (!is_numeric($v)) {
                continue;
            }

            $out[$k] = str_contains($v, '.') ? (float) $v : (int) $v;
        }

        return $out;
    }

    /** @param array<string,mixed> $row @return array<string,mixed> */
    private function rebuildTotals(array $row, ?string $companyName = null): array
    {
        $meta = $this->allowanceMetaMap($companyName);

        $manualYakuin = ['officer_com', 'executive_reward', 'allowance_amo_2'];
        $deductionLikeAllowanceKeys = ['late_deduction', 'absence_deduction', 'leave_allowance'];

        $earningKeys = [];
        foreach (array_keys($meta) as $key) {
            if (in_array($key, $deductionLikeAllowanceKeys, true)) {
                continue;
            }
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

        $syaho = $this->num($row['kenpo'] ?? 0)
            + $this->num($row['kaigo'] ?? 0)
            + $this->num($row['kounen'] ?? 0)
            + $this->num($row['koyou'] ?? 0);

        $deduction = $syaho
            + $this->num($row['income_tax'] ?? 0)
            + $this->num($row['resident_tax'] ?? 0)
            + $this->num($row['rent_cost'] ?? 0)
            + $this->num($row['adjustment_cost'] ?? 0)
            + $this->num($row['koujyo_1'] ?? 0);

        $taxable = max(0.0, round($taxable, 0));
        $nonTaxable = max(0.0, round($nonTaxable, 0));

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
            ->select('company_id', 'company_code')
            ->whereRaw('LTRIM(RTRIM(company_name)) = ?', [$name])
            ->get();

        $candidates = [];
        foreach ($rows as $row) {
            foreach (['company_id', 'company_code'] as $col) {
                $value = trim((string) ($row->{$col} ?? ''));
                if ($value !== '') {
                    $candidates[$value] = true;
                }
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
            'attendance_checked',
            'attendance_checked_at',
            'attendance_checked_by',
            'link',
        ];

        $cached = array_values(array_filter(
            array_map(static fn ($c) => (string) $c, $columns),
            static fn ($c) => !in_array($c, $deny, true)
        ));

        return $cached;
    }

    private function targetRow(string $staffId, int $year, int $month): ?object
    {
        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month])
            ->whereRaw('LTRIM(RTRIM([kyuyo_staff_id])) = ?', [$staffId])
            ->orderByDesc('kyuyo_sho_no')
            ->first(['kyuyo_sho_no']);
    }
}

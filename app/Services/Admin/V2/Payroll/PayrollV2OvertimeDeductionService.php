<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;

class PayrollV2OvertimeDeductionService
{
    public function __construct(
        private readonly PayrollV2KihonService $kihonService,
        private readonly PayrollV2UpdateService $updateService,
    ) {
    }

    /**
     * 残業手当・深夜手当・休日勤務手当・遅早控除・欠勤控除の正本。
     *
     * 手当名ごとの保存先列は mx_allowance の amount_column_key を使う。
     * 計算後は PayrollV2UpdateService::refreshTotals() で最終合計を作り直す。
     */
    public function recalculate(string $staffId, int $year, int $month, ?string $companyName = null): int
    {
        $staffId = trim($staffId);
        if ($staffId === '' || $year < 2000 || $month < 1 || $month > 12) {
            return 0;
        }

        $row = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month])
            ->whereRaw('LTRIM(RTRIM([kyuyo_staff_id])) = ?', [$staffId])
            ->orderByDesc('kyuyo_sho_no')
            ->first();

        if (!$row || !isset($row->kyuyo_sho_no)) {
            return 0;
        }

        $summary = (array) $row;
        $companyName = $this->normalizeCompanyName($staffId, $companyName);

        $staff = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->whereRaw('LTRIM(RTRIM([staff_id])) = ?', [$staffId])
            ->first(['staff_division', 'year_working_time']);

        $division = trim((string) ($staff->staff_division ?? ''));
        $isOfficer = mb_strpos($division, '鬮ｯ貅ｷ遘√・・ｽ繝ｻ・ｹ鬮ｯ・ｷ繝ｻ・ｩ郢晢ｽｻ繝ｻ・｡') !== false || mb_strpos($division, '鬮ｯ貅ｷ遘√・・ｽ繝ｻ・ｹ鬮ｯ・ｷ繝ｻ・ｩ郢晢ｽｻ繝ｻ・｡鬮ｯ諛ｶ・ｽ・｣郢晢ｽｻ繝ｻ・ｱ鬯ｯ・ｩ雋翫ｑ・ｽ・ｽ繝ｻ・ｬ') !== false;

        if ($isOfficer) {
            // Officers do not receive overtime or deduction calculations.
            $payload = $this->buildPayload(0, 0, 0, 0, 0, $summary, $companyName);
            $updated = (int) DB::connection('sqlsrv_payroll')
                ->table('dbo.mx_kyuyo_shou')
                ->where('kyuyo_sho_no', (int) $row->kyuyo_sho_no)
                ->update($payload);

            $updated += (int) $this->updateService->refreshTotals($staffId, $year, $month, $companyName);
            return $updated;
        }

        $kihon = $this->kihonService->map($year, $month)[$staffId] ?? [];

        $hourlyRate = $this->num($kihon['hourly_salary'] ?? 0);
        if ($hourlyRate <= 0) {
            $hourlyRate = $this->num($kihon['hourly_pay'] ?? 0);
        }

        $monthlySalary = $this->num($kihon['monthly_salary'] ?? 0);
        $monthlySetWorkTime = $this->num($staff->year_working_time ?? 0);

        $overtimeHours = $this->num($summary['overtime'] ?? 0);
        $nightHours = $this->num($summary['night_over_time'] ?? 0);
        $holidayHours = $this->num($summary['work_time_num'] ?? 0);
        $lateHours = $this->num($summary['late_time'] ?? 0);
        $absenceDays = $this->num($summary['absence_num'] ?? 0);

        $isHourly =
            str_contains($division, hex2bin('E38391E383BCE38388'))
            || str_contains($division, hex2bin('E382A2E383ABE38390E382A4E38388'))
            || ($hourlyRate > 0 && $monthlySalary <= 0);

        $overtimeAmount = 0.0;
        $nightAmount = 0.0;
        $holidayAmount = 0.0;
        $lateDeduction = 0.0;
        $absenceDeduction = 0.0;

        if ($isHourly) {
            $overtimeAmount = $hourlyRate * 1.25 * $overtimeHours;
            $nightAmount = $hourlyRate * 0.25 * $nightHours;
            $holidayAmount = $hourlyRate * 1.25 * $holidayHours;
        } else {
            [$warimasiBase, $koujyoBase] = $this->baseSums($summary, $companyName);
            if ($monthlySetWorkTime > 0) {
                $warimasiUnit = $warimasiBase / $monthlySetWorkTime;
                $koujyoUnit = $koujyoBase / $monthlySetWorkTime;

                $overtimeAmount = $warimasiUnit * 1.25 * $overtimeHours;
                $nightAmount = $warimasiUnit * 0.25 * $nightHours;
                $holidayAmount = $warimasiUnit * 1.25 * $holidayHours;

                $lateDeduction = $koujyoUnit * $lateHours;
                $absenceDeduction = $koujyoUnit * 8 * $absenceDays;
            }
        }

        $payload = $this->buildPayload(
            $overtimeAmount,
            $nightAmount,
            $holidayAmount,
            $lateDeduction,
            $absenceDeduction,
            $summary,
            $companyName
        );

        $updated = (int) DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_sho_no', (int) $row->kyuyo_sho_no)
            ->update($payload);

        $updated += (int) $this->updateService->refreshTotals($staffId, $year, $month, $companyName);

        return $updated;
    }

    /** @param array<string,mixed> $summary @return array{warimasi_base:float,koujyo_base:float} */
    public function referenceBases(array $summary, ?string $companyName = null): array
    {
        [$warimasiBase, $koujyoBase] = $this->baseSums($summary, trim((string) $companyName));

        return [
            'warimasi_base' => $warimasiBase,
            'koujyo_base' => $koujyoBase,
        ];
    }

    /** @param array<string,mixed> $summary @return array{0:float,1:float} */
    private function baseSums(array $summary, string $companyName): array
    {
        $warimasiBase = 0.0;
        $koujyoBase = 0.0;

        foreach ($this->allowanceFlags($companyName) as $key => $flags) {
            $amount = $this->num($summary[$key] ?? 0);
            if ($amount == 0.0) {
                continue;
            }
            if ((int) ($flags['warimasi_kiso'] ?? 0) === 1) {
                $warimasiBase += $amount;
            }
            if ((int) ($flags['koujyo_kiso'] ?? 0) === 1) {
                $koujyoBase += $amount;
            }
        }

        return [$warimasiBase, $koujyoBase];
    }

    /** @return array<string,array{warimasi_kiso:int,koujyo_kiso:int}> */
    private function allowanceFlags(string $companyName): array
    {
        $query = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_allowance')
            ->select('amount_column_key', 'warimasi_kiso', 'koujyo_kiso')
            ->whereNotNull('amount_column_key')
            ->whereRaw("LTRIM(RTRIM(amount_column_key)) <> ''");

        $offices = $this->resolveOfficeNamesFromCompanyName($companyName);
        if ($offices !== []) {
            $query->whereIn(DB::raw("LTRIM(RTRIM(CAST(office_name as nvarchar(50))))"), $offices);
        }

        $map = [];
        foreach ($query->get() as $row) {
            $key = trim((string) ($row->amount_column_key ?? ''));
            if ($key === '') {
                continue;
            }
            $prev = $map[$key] ?? ['warimasi_kiso' => 0, 'koujyo_kiso' => 0];
            $map[$key] = [
                'warimasi_kiso' => max($prev['warimasi_kiso'], (((int) ($row->warimasi_kiso ?? 0)) === 1 ? 1 : 0)),
                'koujyo_kiso' => max($prev['koujyo_kiso'], (((int) ($row->koujyo_kiso ?? 0)) === 1 ? 1 : 0)),
            ];
        }

        return $map;
    }

    /** @param array<string,mixed> $summary @return array<string,int> */
    private function buildPayload(
        float $overtimeAmount,
        float $nightAmount,
        float $holidayAmount,
        float $lateDeduction,
        float $absenceDeduction,
        array $summary,
        string $companyName
    ): array {
        $targets = $this->targetKeys($companyName);

        $payload = [];
        $payload[$targets['overtime']] = $this->toInt($overtimeAmount);
        $payload[$targets['night']] = $this->toInt($nightAmount);
        $payload[$targets['holiday']] = $this->toInt($holidayAmount);
        $payload[$targets['late']] = $this->toNegativeInt($lateDeduction);
        $payload[$targets['absence']] = $this->toNegativeInt($absenceDeduction);

        // Avoid invalid-column update if key is not an actual summary column.
        $safe = [];
        foreach ($payload as $key => $value) {
            if (array_key_exists($key, $summary)) {
                $safe[$key] = $value;
            }
        }

        return $safe;
    }

    /** @return array{overtime:string,night:string,holiday:string,late:string,absence:string} */
    private function targetKeys(string $companyName): array
    {
        $query = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_allowance')
            ->select('allowance_name', 'amount_column_key', 'display_order', 'allowance_no')
            ->whereNotNull('allowance_name')
            ->whereNotNull('amount_column_key')
            ->whereRaw("LTRIM(RTRIM(amount_column_key)) <> ''");

        $offices = $this->resolveOfficeNamesFromCompanyName($companyName);
        if ($offices !== []) {
            $query->whereIn(DB::raw("LTRIM(RTRIM(CAST(office_name as nvarchar(50))))"), $offices);
        }

        $rows = $query
            ->orderByRaw('CASE WHEN display_order IS NULL THEN 1 ELSE 0 END')
            ->orderBy('display_order')
            ->orderBy('allowance_no')
            ->get();

        $byName = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row->allowance_name ?? ''));
            $key = trim((string) ($row->amount_column_key ?? ''));
            if ($name === '' || $key === '' || isset($byName[$name])) {
                continue;
            }
            $byName[$name] = $key;
        }

        return [
            'overtime' => $byName[hex2bin('E699AEE9809AE6AE8BE6A5AD')] ?? 'allowance_amo_8',
            'night' => $byName[hex2bin('E6B7B1E5A49CE6AE8BE6A5AD')] ?? 'allowance_amo_9',
            'holiday' => $byName[hex2bin('E4BC91E697A5E58BA4E58B99')] ?? 'allowance_amo_7',
            'late' => $byName[hex2bin('E98185E697A9E68EA7E999A4')] ?? 'late_deduction',
            'absence' => $byName[hex2bin('E6ACA0E58BA4E68EA7E999A4')] ?? 'absence_deduction',
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

    /** @return list<string> */
    private function resolveOfficeNamesFromCompanyName(string $companyName): array
    {
        $name = trim($companyName);
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

    private function toInt(float $v): int
    {
        return (int) round(max(0.0, $v), 0, PHP_ROUND_HALF_UP);
    }

    private function toNegativeInt(float $v): int
    {
        return -1 * (int) round(max(0.0, $v), 0, PHP_ROUND_HALF_UP);
    }
}

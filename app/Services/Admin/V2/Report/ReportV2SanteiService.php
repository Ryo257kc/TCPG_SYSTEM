<?php

namespace App\Services\Admin\V2\Report;

use Illuminate\Support\Facades\DB;

class ReportV2SanteiService
{
    /** @return list<int> */
    public function availableYears(): array
    {
        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->selectRaw('YEAR([supply_month]) as report_year')
            ->where('bonus', 0)
            ->whereNotNull('supply_month')
            ->whereRaw('MONTH([supply_month]) between 4 and 6')
            ->groupByRaw('YEAR([supply_month])')
            ->orderByRaw('YEAR([supply_month]) desc')
            ->pluck('report_year')
            ->map(static fn ($year): int => (int) $year)
            ->filter(static fn (int $year): bool => $year > 0)
            ->values()
            ->all();
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function companyOptions(int $year): array
    {
        $staffIds = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 0)
            ->whereNotNull('supply_month')
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) between 4 and 6')
            ->pluck('kyuyo_staff_id')
            ->map(static fn ($value): string => trim((string) $value))
            ->filter(static fn (string $value): bool => $value !== '')
            ->unique()
            ->values()
            ->all();

        if ($staffIds === []) {
            return [];
        }

        return DB::connection('sqlsrv')
            ->table('dbo.mx_staffs as s')
            ->leftJoin('dbo.mx_stores as st', 'st.store_code', '=', 's.section')
            ->leftJoin('dbo.mx_companies as c', 'c.company_id', '=', 'st.company_id')
            ->whereIn(DB::raw('LTRIM(RTRIM(s.staff_id))'), $staffIds)
            ->whereNotNull('c.company_name')
            ->whereRaw('LTRIM(RTRIM(c.company_name)) <> ?', [''])
            ->distinct()
            ->orderBy('c.company_name')
            ->pluck('c.company_name')
            ->map(static fn ($value): string => trim((string) $value))
            ->filter(static fn (string $value): bool => $value !== '')
            ->values()
            ->all();
    }

    public function rows(int $year, string $companyName = ''): array
    {
        $payrollRows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->select([
                'kyuyo_staff_id',
                'supply_month',
                'work_in_num',
                'absence_num',
                'horiday_true',
                'taxation_sum',
                'not_taxation_sum',
                'supply_sum',
                'syaho_target_sum',
            ])
            ->where('bonus', 0)
            ->whereNotNull('supply_month')
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) between 4 and 6')
            ->orderBy('kyuyo_staff_id')
            ->orderBy('supply_month')
            ->get();

        $staffIds = $payrollRows
            ->pluck('kyuyo_staff_id')
            ->map(static fn ($value): string => trim((string) $value))
            ->filter(static fn (string $value): bool => $value !== '')
            ->unique()
            ->values()
            ->all();

        if ($staffIds === []) {
            return [];
        }

        $staffRowsQuery = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs as s')
            ->leftJoin('dbo.mx_stores as st', 'st.store_code', '=', 's.section')
            ->leftJoin('dbo.mx_companies as c', 'c.company_id', '=', 'st.company_id')
            ->select([
                's.staff_id',
                's.staff_name',
                's.birthday',
                's.sex',
                's.staff_division',
                's.employment',
                's.syaho',
                's.syaho_seiri_num',
                's.tai_date',
                'st.store_name',
                'c.company_name',
                'c.health_office_code',
                'c.company_address',
                'c.tel',
                'c.ceo_title',
                'c.ceo_name',
            ])
            ->whereIn(DB::raw('LTRIM(RTRIM(s.staff_id))'), $staffIds);

        if ($companyName !== '') {
            $staffRowsQuery->where('c.company_name', $companyName);
        }

        $staffRows = $staffRowsQuery->get();

        $staffMap = [];
        foreach ($staffRows as $staffRow) {
            $staffId = trim((string) ($staffRow->staff_id ?? ''));
            if ($staffId === '') {
                continue;
            }
            $staffMap[$staffId] = [
                'staff_id' => $staffId,
                'staff_name' => trim((string) ($staffRow->staff_name ?? '')),
                'birthday' => $this->dateValue($staffRow->birthday ?? null),
                'birthday_wareki' => $this->warekiCode($staffRow->birthday ?? null),
                'sex' => trim((string) ($staffRow->sex ?? '')),
                'staff_division' => trim((string) ($staffRow->staff_division ?? '')),
                'employment' => trim((string) ($staffRow->employment ?? '')),
                'syaho' => (int) ($staffRow->syaho ?? 0),
                'syaho_seiri_num' => trim((string) ($staffRow->syaho_seiri_num ?? '')),
                'syaho_seiri_num_padded' => str_pad(trim((string) ($staffRow->syaho_seiri_num ?? '')), 2, '0', STR_PAD_LEFT),
                'tai_date' => $this->dateValue($staffRow->tai_date ?? null),
                'store_name' => trim((string) ($staffRow->store_name ?? '')),
                'company_name' => trim((string) ($staffRow->company_name ?? '')),
                'company_name_formatted' => str_replace('㈱', '株式会社　', trim((string) ($staffRow->company_name ?? ''))),
                'health_office_code' => trim((string) ($staffRow->health_office_code ?? '')),
                'company_address' => trim((string) ($staffRow->company_address ?? '')),
                'tel' => trim((string) ($staffRow->tel ?? '')),
                'ceo_title' => trim((string) ($staffRow->ceo_title ?? '')),
                'ceo_name' => trim((string) ($staffRow->ceo_name ?? '')),
            ];
        }

        $kihonRows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kihon')
            ->select([
                'staff_id',
                'decision_date',
                'monthly_salary',
                'hourly_pay',
                'hourly_salary',
            ])
            ->whereIn(DB::raw('LTRIM(RTRIM(staff_id))'), $staffIds)
            ->orderBy('staff_id')
            ->orderByDesc('decision_date')
            ->get();

        $kihonCutoff = sprintf('%04d-06-30', $year);
        $kihonMap = [];
        foreach ($kihonRows as $kihonRow) {
            $staffId = trim((string) ($kihonRow->staff_id ?? ''));
            if ($staffId === '' || isset($kihonMap[$staffId])) {
                continue;
            }

            $decisionDate = trim((string) ($kihonRow->decision_date ?? ''));
            if ($decisionDate !== '' && strtotime($decisionDate) !== false && date('Y-m-d', strtotime($decisionDate)) > $kihonCutoff) {
                continue;
            }

            $kihonMap[$staffId] = [
                'monthly_salary' => $this->floatValue($kihonRow->monthly_salary ?? null),
                'hourly_pay' => $this->floatValue($kihonRow->hourly_pay ?? null),
                'hourly_salary' => $this->floatValue($kihonRow->hourly_salary ?? null),
            ];
        }

        $shahoRows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_staff_shou')
            ->select([
                'staff_id',
                'raise_year',
                'kenpo_monthly_amo',
                'kounen_monthly_amo',
                'kenpo_toukyu',
                'kounen_toukyu',
            ])
            ->whereIn(DB::raw('LTRIM(RTRIM(staff_id))'), $staffIds)
            ->orderBy('staff_id')
            ->orderByDesc('raise_year')
            ->get();

        $juneCutoff = sprintf('%04d-06-30', $year);
        $shahoMap = [];
        foreach ($shahoRows as $shahoRow) {
            $staffId = trim((string) ($shahoRow->staff_id ?? ''));
            if ($staffId === '' || isset($shahoMap[$staffId])) {
                continue;
            }

            $raiseYear = trim((string) ($shahoRow->raise_year ?? ''));
            if ($raiseYear !== '' && strtotime($raiseYear) !== false && date('Y-m-d', strtotime($raiseYear)) > $juneCutoff) {
                continue;
            }

            $shahoMap[$staffId] = [
                'kenpo_monthly_amo' => $this->floatValue($shahoRow->kenpo_monthly_amo ?? null),
                'kounen_monthly_amo' => $this->floatValue($shahoRow->kounen_monthly_amo ?? null),
                'kenpo_toukyu' => $shahoRow->kenpo_toukyu,
                'kounen_toukyu' => $shahoRow->kounen_toukyu,
            ];
        }

        $grouped = [];
        foreach ($payrollRows as $payrollRow) {
            $staffId = trim((string) ($payrollRow->kyuyo_staff_id ?? ''));
            if ($staffId === '' || !isset($staffMap[$staffId])) {
                continue;
            }

            $staff = $staffMap[$staffId];
            if ($staff['syaho_seiri_num'] === '' || $staff['syaho'] !== 1) {
                continue;
            }

            $month = (int) date('n', strtotime((string) $payrollRow->supply_month));
            if (!in_array($month, [4, 5, 6], true)) {
                continue;
            }

            if (!isset($grouped[$staffId])) {
                $grouped[$staffId] = [
                    'staff_id' => $staffId,
                    'staff_name' => $staff['staff_name'],
                    'syaho_seiri_num' => $staff['syaho_seiri_num'],
                    'syaho_seiri_num_padded' => $staff['syaho_seiri_num_padded'],
                    'birthday' => $staff['birthday'],
                    'birthday_wareki' => $staff['birthday_wareki'],
                    'tai_date' => $staff['tai_date'],
                    'sex' => $staff['sex'],
                    'employment' => $staff['employment'],
                    'staff_division' => $staff['staff_division'],
                    'store_name' => $staff['store_name'],
                    'company_name' => $staff['company_name'],
                    'company_name_formatted' => $staff['company_name_formatted'],
                    'health_office_code' => $staff['health_office_code'],
                    'company_address' => $staff['company_address'],
                    'tel' => $staff['tel'],
                    'ceo_title' => $staff['ceo_title'],
                    'ceo_name' => $staff['ceo_name'],
                    'months' => [
                        4 => $this->emptyMonthRow(4),
                        5 => $this->emptyMonthRow(5),
                        6 => $this->emptyMonthRow(6),
                    ],
                ];
            }

            $grouped[$staffId]['months'][$month] = [
                'month' => $month,
                'payment_date' => $this->dateValue($payrollRow->supply_month ?? null),
                'basis_days' => $this->basisDays($payrollRow, $staff, $kihonMap[$staffId] ?? []),
                'taxation_sum' => $this->floatValue($payrollRow->taxation_sum ?? null),
                'not_taxation_sum' => $this->floatValue($payrollRow->not_taxation_sum ?? null),
                'supply_sum' => $this->floatValue($payrollRow->supply_sum ?? null),
                'syaho_target_sum' => $this->floatValue($payrollRow->syaho_target_sum ?? null),
            ];
        }

        $result = [];
        foreach ($grouped as $staffId => $row) {
            $targets = array_filter($row['months'], static fn (array $monthRow): bool => ($monthRow['basis_days'] ?? 0) > 0);
            $averageBase = count($targets) > 0
                ? array_sum(array_map(static fn (array $monthRow): float => (float) ($monthRow['syaho_target_sum'] ?? 0), $targets)) / count($targets)
                : 0.0;

            $row['average_syaho_target'] = $averageBase;
            $row['month_count'] = count($targets);
            $row['shaho_info'] = $shahoMap[$staffId] ?? [
                'kenpo_monthly_amo' => null,
                'kounen_monthly_amo' => null,
                'kenpo_toukyu' => null,
                'kounen_toukyu' => null,
            ];
            $result[] = $row;
        }

        usort($result, static function (array $a, array $b): int {
            return [$a['company_name'], $a['syaho_seiri_num_padded'], $a['staff_id']]
                <=> [$b['company_name'], $b['syaho_seiri_num_padded'], $b['staff_id']];
        });

        return $result;
    }

    /** @param array<string,mixed> $staff @param array<string,mixed> $kihon */
    private function basisDays(object $payrollRow, array $staff, array $kihon): float
    {
        $paymentRaw = trim((string) ($payrollRow->supply_month ?? ''));
        $paymentTs = $paymentRaw !== '' ? strtotime($paymentRaw) : false;
        if ($paymentTs === false) {
            return 0.0;
        }

        $targetMonth = (new \DateTimeImmutable(date('Y-m-01', $paymentTs)))->modify('-1 month');
        $calendarDays = (int) $targetMonth->format('t');
        $absenceDays = $this->floatValue($payrollRow->absence_num ?? null);
        $workInDays = $this->floatValue($payrollRow->work_in_num ?? null);
        $paidLeaveDays = $this->floatValue($payrollRow->horiday_true ?? null);

        $division = trim((string) ($staff['staff_division'] ?? ''));
        $employment = trim((string) ($staff['employment'] ?? ''));
        $monthlySalary = $this->floatValue($kihon['monthly_salary'] ?? null);
        $hourlyPay = $this->floatValue($kihon['hourly_pay'] ?? null);
        $hourlySalary = $this->floatValue($kihon['hourly_salary'] ?? null);

        $isHourlyOrDaily =
            str_contains($division, 'パート')
            || str_contains($division, 'アルバイト')
            || str_contains($division, '日給')
            || str_contains($division, '時給')
            || str_contains($employment, '日給')
            || str_contains($employment, '時給')
            || (($hourlyPay > 0 || $hourlySalary > 0) && $monthlySalary <= 0);

        if ($isHourlyOrDaily) {
            return round($workInDays + $paidLeaveDays, 1);
        }

        return round(max(0.0, $calendarDays - $absenceDays), 1);
    }

    /**
     * @return array{month:int,payment_date:?string,basis_days:float,taxation_sum:float,not_taxation_sum:float,supply_sum:float,syaho_target_sum:float}
     */
    private function emptyMonthRow(int $month): array
    {
        return [
            'month' => $month,
            'payment_date' => null,
            'basis_days' => 0.0,
            'taxation_sum' => 0.0,
            'not_taxation_sum' => 0.0,
            'supply_sum' => 0.0,
            'syaho_target_sum' => 0.0,
        ];
    }

    private function dateValue(mixed $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $timestamp = strtotime($raw);
        return $timestamp === false ? null : date('Y/m/d', $timestamp);
    }

    private function floatValue(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function warekiCode(mixed $birthday): string
    {
        $raw = trim((string) $birthday);
        if ($raw === '') {
            return '';
        }

        $timestamp = strtotime($raw);
        if ($timestamp === false) {
            return '';
        }

        $date = new \DateTimeImmutable(date('Y-m-d', $timestamp));
        $eras = [
            ['code' => '9', 'start' => new \DateTimeImmutable('2019-05-01')],
            ['code' => '7', 'start' => new \DateTimeImmutable('1989-01-08')],
            ['code' => '5', 'start' => new \DateTimeImmutable('1926-12-25')],
            ['code' => '3', 'start' => new \DateTimeImmutable('1912-07-30')],
            ['code' => '1', 'start' => new \DateTimeImmutable('1868-01-25')],
        ];

        foreach ($eras as $era) {
            if ($date < $era['start']) {
                continue;
            }

            $eraYear = ((int) $date->format('Y')) - ((int) $era['start']->format('Y')) + 1;
            return sprintf('%s-%02d%s', $era['code'], $eraYear, $date->format('md'));
        }

        return '';
    }
}

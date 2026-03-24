<?php

namespace App\Services\Admin\V2\Report;

use Illuminate\Support\Facades\DB;

class ReportV2LaborInsuranceService
{
    /** @return list<int> */
    public function availableYears(): array
    {
        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->selectRaw('CASE WHEN MONTH([supply_month]) >= 4 THEN YEAR([supply_month]) ELSE YEAR([supply_month]) - 1 END as fiscal_year')
            ->whereNotNull('supply_month')
            ->groupByRaw('CASE WHEN MONTH([supply_month]) >= 4 THEN YEAR([supply_month]) ELSE YEAR([supply_month]) - 1 END')
            ->orderByRaw('CASE WHEN MONTH([supply_month]) >= 4 THEN YEAR([supply_month]) ELSE YEAR([supply_month]) - 1 END desc')
            ->pluck('fiscal_year')
            ->map(static fn ($year): int => (int) $year)
            ->filter(static fn (int $year): bool => $year > 0)
            ->values()
            ->all();
    }

    /** @return list<string> */
    public function companyOptions(int $fiscalYear): array
    {
        $staffIds = $this->basePayrollQuery($fiscalYear)
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
            ->whereIn('s.staff_id', $staffIds)
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

    /**
     * @return array{
     *   filters: array<string,mixed>,
     *   monthly_rows: list<array<string,mixed>>,
     *   yearly_totals: array<string,float|int>,
     *   report_totals: array<string,float|int>
     * }
     */
    public function build(int $fiscalYear, string $companyName = ''): array
    {
        $payrollRows = $this->basePayrollQuery($fiscalYear)
            ->select([
                'kyuyo_staff_id',
                'supply_month',
                'bonus',
                'bonus_amo',
                'rouho_target_sum',
                'allowance_amo_2',
                'koyou',
            ])
            ->orderBy('supply_month')
            ->orderBy('kyuyo_staff_id')
            ->get();

        $staffIds = $payrollRows
            ->pluck('kyuyo_staff_id')
            ->map(static fn ($value): string => trim((string) $value))
            ->filter(static fn (string $value): bool => $value !== '')
            ->unique()
            ->values()
            ->all();

        if ($staffIds === []) {
            return $this->emptyResult($fiscalYear, $companyName);
        }

        $staffQuery = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs as s')
            ->leftJoin('dbo.mx_stores as st', 'st.store_code', '=', 's.section')
            ->leftJoin('dbo.mx_companies as c', 'c.company_id', '=', 'st.company_id')
            ->select([
                's.staff_id',
                's.staff_division',
                'st.store_name',
                'c.company_name',
            ])
            ->whereIn('s.staff_id', $staffIds);

        if ($companyName !== '') {
            $staffQuery->where('c.company_name', $companyName);
        }

        $staffMap = [];
        foreach ($staffQuery->get() as $staffRow) {
            $staffId = trim((string) ($staffRow->staff_id ?? ''));
            if ($staffId === '') {
                continue;
            }

            $staffMap[$staffId] = [
                'staff_division' => trim((string) ($staffRow->staff_division ?? '')),
                'company_name' => trim((string) ($staffRow->company_name ?? '')),
                'store_name' => trim((string) ($staffRow->store_name ?? '')),
            ];
        }

        $monthlyBuckets = [];
        $bonusBuckets = [];
        foreach ($this->fiscalMonths($fiscalYear) as $monthKey => $label) {
            $monthlyBuckets[$monthKey] = $this->emptyBucket($label, false, $monthKey);
        }

        foreach ($payrollRows as $payrollRow) {
            $staffId = trim((string) ($payrollRow->kyuyo_staff_id ?? ''));
            if ($staffId === '' || !isset($staffMap[$staffId])) {
                continue;
            }

            $division = $staffMap[$staffId]['staff_division'];
            if ($division === '業務委託' || $division === '役員') {
                continue;
            }

            $monthKey = $this->monthKey($payrollRow->supply_month ?? null);
            if ($monthKey === null || !isset($monthlyBuckets[$monthKey])) {
                continue;
            }

            $isBonus = (int) ($payrollRow->bonus ?? 0) === 1;
            if ($isBonus) {
                $label = $this->bonusLabel($payrollRow->supply_month ?? null);
                $bucketIndex = $monthKey . '|' . $label;
                if (!isset($bonusBuckets[$bucketIndex])) {
                    $bonusBuckets[$bucketIndex] = $this->emptyBucket($label, true, $monthKey);
                }
                $bucket = &$bonusBuckets[$bucketIndex];
            } else {
                $bucket = &$monthlyBuckets[$monthKey];
            }
            $bucket['sort_month'] = trim((string) ($payrollRow->supply_month ?? ''));
            $amount = $this->floatValue($payrollRow->rouho_target_sum ?? null);
            $employmentInsurance = $this->floatValue($payrollRow->koyou ?? null) > 0;
            $isExecutiveWorker = $division === '兼務役員';
            $isTemporary = !$employmentInsurance && $division !== '業務委託';
            $isRegular = $employmentInsurance && !$isExecutiveWorker;

            if ($isRegular) {
                $bucket['left_regular_amount'] += $amount;
                $bucket['left_regular_ids'][$staffId] = true;
                $bucket['right_regular_amount'] += $amount;
                $bucket['right_regular_ids'][$staffId] = true;
            }

            if ($isExecutiveWorker) {
                $bucket['left_executive_amount'] += $amount;
                $bucket['left_executive_ids'][$staffId] = true;
                if ($employmentInsurance) {
                    $bucket['right_executive_amount'] += $amount;
                    $bucket['right_executive_ids'][$staffId] = true;
                }
            }

            if ($isTemporary) {
                $bucket['left_temporary_amount'] += $amount;
                $bucket['left_temporary_ids'][$staffId] = true;
            }

            $bucket['left_total_amount'] =
                $bucket['left_regular_amount']
                + $bucket['left_executive_amount']
                + $bucket['left_temporary_amount'];
            $bucket['right_total_amount'] =
                $bucket['right_regular_amount']
                + $bucket['right_executive_amount'];
            unset($bucket);
        }

        $monthlyRows = [];
        $yearlyTotals = $this->emptyTotals();
        $yearlyCountSets = [
            'left_regular' => [],
            'left_executive' => [],
            'left_temporary' => [],
            'right_regular' => [],
            'right_executive' => [],
        ];
        foreach ($monthlyBuckets as $monthKey => $bucket) {
            $row = $this->finalizeBucket($bucket);
            $monthlyRows[] = $row;
            foreach (array_keys($yearlyTotals) as $totalKey) {
                if (str_ends_with($totalKey, '_count')) {
                    continue;
                }
                $yearlyTotals[$totalKey] += $row[$totalKey] ?? 0;
            }
            $this->mergeCountSets($yearlyCountSets, $bucket);

            foreach ($bonusBuckets as $bonusKey => $bonusBucket) {
                if (!str_starts_with($bonusKey, $monthKey . '|')) {
                    continue;
                }

                $bonusRow = $this->finalizeBucket($bonusBucket);
                $monthlyRows[] = $bonusRow;
                foreach (array_keys($yearlyTotals) as $totalKey) {
                    if (str_ends_with($totalKey, '_count')) {
                        continue;
                    }
                    $yearlyTotals[$totalKey] += $bonusRow[$totalKey] ?? 0;
                }
            }
        }

        $yearlyTotals['left_regular_count'] = count($yearlyCountSets['left_regular']);
        $yearlyTotals['left_executive_count'] = count($yearlyCountSets['left_executive']);
        $yearlyTotals['left_temporary_count'] = count($yearlyCountSets['left_temporary']);
        $yearlyTotals['right_regular_count'] = count($yearlyCountSets['right_regular']);
        $yearlyTotals['right_executive_count'] = count($yearlyCountSets['right_executive']);

        usort($monthlyRows, static function (array $a, array $b): int {
            return [
                $a['is_bonus'] ? 1 : 0,
                $a['sort_month'] ?? '',
                $a['label'] ?? '',
            ] <=> [
                $b['is_bonus'] ? 1 : 0,
                $b['sort_month'] ?? '',
                $b['label'] ?? '',
            ];
        });

        $reportTotals = [
            'workers_total' => (int) (
                $yearlyTotals['left_regular_count']
                + $yearlyTotals['left_executive_count']
                + $yearlyTotals['left_temporary_count']
            ),
            'wages_total' => (float) $yearlyTotals['left_total_amount'],
            'wages_total_truncated' => floor(((float) $yearlyTotals['left_total_amount']) / 1000) * 1000,
            'employment_workers' => (int) (
                $yearlyTotals['right_regular_count']
                + $yearlyTotals['right_executive_count']
            ),
            'employment_wages_total' => (float) $yearlyTotals['right_total_amount'],
            'employment_wages_total_truncated' => floor(((float) $yearlyTotals['right_total_amount']) / 1000) * 1000,
        ];

        return [
            'filters' => [
                'fiscal_year' => $fiscalYear,
                'company_name' => $companyName,
                'period_label' => sprintf('%d/04/01 - %d/03/31', $fiscalYear, $fiscalYear + 1),
            ],
            'monthly_rows' => $monthlyRows,
            'yearly_totals' => $yearlyTotals,
            'report_totals' => $reportTotals,
        ];
    }

    private function basePayrollQuery(int $fiscalYear)
    {
        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->whereNotNull('supply_month')
            ->whereRaw('[supply_month] >= ?', [sprintf('%04d-04-01', $fiscalYear)])
            ->whereRaw('[supply_month] < ?', [sprintf('%04d-04-01', $fiscalYear + 1)]);
    }

    /** @return array<string,string> */
    private function fiscalMonths(int $fiscalYear): array
    {
        $months = [];
        $date = new \DateTimeImmutable(sprintf('%04d-04-01', $fiscalYear));
        for ($i = 0; $i < 12; $i++) {
            $current = $date->modify(sprintf('+%d month', $i));
            $months[$current->format('Y-m')] = $current->format('Y年n月');
        }

        return $months;
    }

    /** @return array<string,mixed> */
    private function emptyBucket(string $label, bool $isBonus = false, string $monthKey = ''): array
    {
        return [
            'label' => $label,
            'sort_key' => $this->sortKeyFromLabel($label),
            'month_key' => $monthKey,
            'sort_month' => '',
            'is_bonus' => $isBonus,
            'left_regular_amount' => 0.0,
            'left_regular_ids' => [],
            'left_executive_amount' => 0.0,
            'left_executive_ids' => [],
            'left_temporary_amount' => 0.0,
            'left_temporary_ids' => [],
            'left_total_amount' => 0.0,
            'right_regular_amount' => 0.0,
            'right_regular_ids' => [],
            'right_executive_amount' => 0.0,
            'right_executive_ids' => [],
            'right_total_amount' => 0.0,
        ];
    }

    /** @return array<string,float|int> */
    private function emptyTotals(): array
    {
        return [
            'left_regular_amount' => 0.0,
            'left_regular_count' => 0,
            'left_executive_amount' => 0.0,
            'left_executive_count' => 0,
            'left_temporary_amount' => 0.0,
            'left_temporary_count' => 0,
            'left_total_amount' => 0.0,
            'right_regular_amount' => 0.0,
            'right_regular_count' => 0,
            'right_executive_amount' => 0.0,
            'right_executive_count' => 0,
            'right_total_amount' => 0.0,
        ];
    }

    /** @param array<string,array<string,bool>> $target @param array<string,mixed> $bucket */
    private function mergeCountSets(array &$target, array $bucket): void
    {
        foreach (($bucket['left_regular_ids'] ?? []) as $staffId => $value) {
            $target['left_regular'][(string) $staffId] = (bool) $value;
        }
        foreach (($bucket['left_executive_ids'] ?? []) as $staffId => $value) {
            $target['left_executive'][(string) $staffId] = (bool) $value;
        }
        foreach (($bucket['left_temporary_ids'] ?? []) as $staffId => $value) {
            $target['left_temporary'][(string) $staffId] = (bool) $value;
        }
        foreach (($bucket['right_regular_ids'] ?? []) as $staffId => $value) {
            $target['right_regular'][(string) $staffId] = (bool) $value;
        }
        foreach (($bucket['right_executive_ids'] ?? []) as $staffId => $value) {
            $target['right_executive'][(string) $staffId] = (bool) $value;
        }
    }

    /** @return array<string,mixed> */
    private function finalizeBucket(array $bucket): array
    {
        $bucket['left_regular_count'] = count($bucket['left_regular_ids']);
        $bucket['left_executive_count'] = count($bucket['left_executive_ids']);
        $bucket['left_temporary_count'] = count($bucket['left_temporary_ids']);
        $bucket['right_regular_count'] = count($bucket['right_regular_ids']);
        $bucket['right_executive_count'] = count($bucket['right_executive_ids']);

        unset(
            $bucket['left_regular_ids'],
            $bucket['left_executive_ids'],
            $bucket['left_temporary_ids'],
            $bucket['right_regular_ids'],
            $bucket['right_executive_ids']
        );

        return $bucket;
    }

    private function monthKey(mixed $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '' || strtotime($raw) === false) {
            return null;
        }

        return date('Y-m', strtotime($raw));
    }

    private function bonusLabel(mixed $value): string
    {
        $raw = trim((string) $value);
        if ($raw === '' || strtotime($raw) === false) {
            return '賞与';
        }

        $ts = strtotime($raw);
        return sprintf('賞与%s年 %s月', date('y', $ts), date('n', $ts));
    }

    private function sortKeyFromLabel(string $label): string
    {
        if (preg_match('/^(\d{4})年(\d{1,2})月$/u', $label, $matches) === 1) {
            return sprintf('%04d-%02d-0', (int) $matches[1], (int) $matches[2]);
        }
        if (preg_match('/^賞与(\d{2})年 (\d{1,2})月$/u', $label, $matches) === 1) {
            return sprintf('20%02d-%02d-1', (int) $matches[1], (int) $matches[2]);
        }

        return $label;
    }

    private function floatValue(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * @return array{
     *   filters: array<string,mixed>,
     *   monthly_rows: list<array<string,mixed>>,
     *   yearly_totals: array<string,float|int>,
     *   report_totals: array<string,float|int>
     * }
     */
    private function emptyResult(int $fiscalYear, string $companyName): array
    {
        return [
            'filters' => [
                'fiscal_year' => $fiscalYear,
                'company_name' => $companyName,
                'period_label' => sprintf('%d/04/01 - %d/03/31', $fiscalYear, $fiscalYear + 1),
            ],
            'monthly_rows' => [],
            'yearly_totals' => $this->emptyTotals(),
            'report_totals' => [
                'workers_total' => 0,
                'wages_total' => 0.0,
                'wages_total_truncated' => 0.0,
                'employment_workers' => 0,
                'employment_wages_total' => 0.0,
                'employment_wages_total_truncated' => 0.0,
            ],
        ];
    }
}

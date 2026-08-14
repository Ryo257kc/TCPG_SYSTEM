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
            ->map(static fn($year): int => (int) $year)
            ->filter(static fn(int $year): bool => $year > 0)
            ->values()
            ->all();
    }

    /** @return list<string> */
    public function companyOptions(int $fiscalYear): array
    {
        $staffIds = $this->basePayrollQuery($fiscalYear)
            ->pluck('kyuyo_staff_id')
            ->map(static fn($value): string => trim((string) $value))
            ->filter(static fn(string $value): bool => $value !== '')
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
            ->map(static fn($value): string => trim((string) $value))
            ->filter(static fn(string $value): bool => $value !== '')
            ->values()
            ->all();
    }

    /**
     * @return array{
     *   filters: array<string,mixed>,
     *   company_meta: array<string,mixed>,
     *   executive_worker_summary: list<array<string,string>>,
     *   monthly_rows: list<array<string,mixed>>,
     *   yearly_totals: array<string,float|int>,
     *   report_totals: array<string,float|int>,
     *   detail: array<string,mixed>|null,
     *   detail_map: array<string,array<string,mixed>>
     * }
     */
    public function build(
        int $fiscalYear,
        string $companyName = '',
        string $detailMonth = '',
        string $detailBucket = '',
        string $detailKind = 'regular',
    ): array {
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
            ->map(static fn($value): string => trim((string) $value))
            ->filter(static fn(string $value): bool => $value !== '')
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
                's.staff_name',
                's.staff_division',
                'st.store_name',
                'c.company_name',
            ])
            ->whereIn(DB::raw('LTRIM(RTRIM(s.staff_id))'), $staffIds);

        if ($companyName !== '') {
            $staffQuery->where('c.company_name', $companyName);
        }

        $staffMap = [];
        $companyIds = [];
        foreach ($staffQuery->get() as $staffRow) {
            $staffId = trim((string) ($staffRow->staff_id ?? ''));
            if ($staffId === '') {
                continue;
            }

            $staffMap[$staffId] = [
                'staff_name' => trim((string) ($staffRow->staff_name ?? '')),
                'staff_division' => trim((string) ($staffRow->staff_division ?? '')),
                'company_name' => trim((string) ($staffRow->company_name ?? '')),
                'store_name' => trim((string) ($staffRow->store_name ?? '')),
            ];
        }

        $companyMeta = $this->companyMeta($companyName);

        $monthlyBuckets = [];
        $bonusBuckets = [];
        $detailBuckets = [];
        $executiveWorkerSummary = [];
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
                $this->pushDetail(
                    $detailBuckets,
                    $monthKey,
                    $isBonus,
                    'left_regular',
                    $staffId,
                    $staffMap[$staffId],
                    $amount
                );
                $this->pushDetail(
                    $detailBuckets,
                    $monthKey,
                    $isBonus,
                    'right_regular',
                    $staffId,
                    $staffMap[$staffId],
                    $amount
                );
            }

            if ($isExecutiveWorker) {
                $bucket['left_executive_amount'] += $amount;
                $bucket['left_executive_ids'][$staffId] = true;
                if (!isset($executiveWorkerSummary[$staffId])) {
                    $executiveWorkerSummary[$staffId] = [
                        'staff_name' => $staffMap[$staffId]['staff_name'] ?? '',
                        'role_label' => '取締役',
                        'employment_label' => $employmentInsurance ? '有' : '無',
                    ];
                } elseif ($employmentInsurance) {
                    $executiveWorkerSummary[$staffId]['employment_label'] = '有';
                }
                $this->pushDetail(
                    $detailBuckets,
                    $monthKey,
                    $isBonus,
                    'left_executive',
                    $staffId,
                    $staffMap[$staffId],
                    $amount
                );
                if ($employmentInsurance) {
                    $bucket['right_executive_amount'] += $amount;
                    $bucket['right_executive_ids'][$staffId] = true;
                    $this->pushDetail(
                        $detailBuckets,
                        $monthKey,
                        $isBonus,
                        'right_executive',
                        $staffId,
                        $staffMap[$staffId],
                        $amount
                    );
                }
            }

            if ($isTemporary) {
                $bucket['left_temporary_amount'] += $amount;
                $bucket['left_temporary_ids'][$staffId] = true;
                $this->pushDetail(
                    $detailBuckets,
                    $monthKey,
                    $isBonus,
                    'left_temporary',
                    $staffId,
                    $staffMap[$staffId],
                    $amount
                );
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

        $countableRows = array_values(array_filter(
            $monthlyRows,
            static fn(array $row): bool => empty($row['is_bonus'])
        ));

        $workersSourceTotal = array_sum(array_map(
            static fn(array $row): int => (int) (
                ($row['left_regular_count'] ?? 0)
                + ($row['left_executive_count'] ?? 0)
                + ($row['left_temporary_count'] ?? 0)
            ),
            $countableRows
        ));
        $employmentWorkersSourceTotal = array_sum(array_map(
            static fn(array $row): int => (int) (
                ($row['right_regular_count'] ?? 0)
                + ($row['right_executive_count'] ?? 0)
            ),
            $countableRows
        ));

        $reportTotals = [
            'workers_source_total' => $workersSourceTotal,
            'workers_total' => $workersSourceTotal / 12,
            'wages_total' => (float) $yearlyTotals['left_total_amount'],
            'wages_total_truncated' => floor(((float) $yearlyTotals['left_total_amount']) / 1000) * 1000,
            'employment_workers_source_total' => $employmentWorkersSourceTotal,
            'employment_workers' => $employmentWorkersSourceTotal / 12,
            'employment_wages_total' => (float) $yearlyTotals['right_total_amount'],
            'employment_wages_total_truncated' => floor(((float) $yearlyTotals['right_total_amount']) / 1000) * 1000,
        ];

        return [
            'filters' => [
                'fiscal_year' => $fiscalYear,
                'company_name' => $companyName,
                'period_label' => sprintf('%d/04/01 - %d/03/31', $fiscalYear, $fiscalYear + 1),
            ],
            'company_meta' => $companyMeta,
            'executive_worker_summary' => array_values($executiveWorkerSummary),
            'monthly_rows' => $monthlyRows,
            'yearly_totals' => $yearlyTotals,
            'report_totals' => $reportTotals,
            'detail' => $this->buildDetail($detailBuckets, $detailMonth, $detailBucket, $detailKind),
            'detail_map' => $this->buildDetailMap($detailBuckets),
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

    /** @return array<string,mixed> */
    private function companyMeta(string $companyName): array
    {
        if (trim($companyName) === '') {
            return [
                'company_name' => '',
                'company_address' => '',
                'office_number' => '',
                'tel' => '',
                'labor_install_category' => '',
                'labor_insurance_number' => '',
                'labor_parts' => [
                    'prefecture' => '',
                    'jurisdiction' => '',
                    'office' => '',
                    'base' => '',
                    'branch' => '',
                ],
            ];
        }

        $row = DB::connection('sqlsrv')
            ->table('dbo.mx_companies as c')
            ->leftJoin('dbo.mx_stores as st', 'st.company_id', '=', 'c.company_id')
            ->select([
                'c.company_name',
                'c.company_address',
                'c.office_number',
                'c.tel',
                'c.labor_insurance_number',
                'c.labor_install_category',
            ])
            ->where('c.company_name', $companyName)
            ->first();

        $laborInsuranceNumber = trim((string) ($row->labor_insurance_number ?? ''));

        return [
            'company_name' => trim((string) ($row->company_name ?? '')),
            'company_address' => trim((string) ($row->company_address ?? '')),
            'office_number' => trim((string) ($row->office_number ?? '')),
            'tel' => trim((string) ($row->tel ?? '')),
            'labor_install_category' => trim((string) ($row->labor_install_category ?? '')),
            'labor_insurance_number' => $laborInsuranceNumber,
            'labor_parts' => $this->parseLaborInsuranceNumber($laborInsuranceNumber),
        ];
    }

    /** @return array{prefecture:string,jurisdiction:string,office:string,base:string,branch:string} */
    private function parseLaborInsuranceNumber(string $value): array
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if (strlen($digits) < 14) {
            $digits = str_pad($digits, 14, '0', STR_PAD_LEFT);
        }

        return [
            'prefecture' => substr($digits, 0, 2) ?: '',
            'jurisdiction' => substr($digits, 2, 1) ?: '',
            'office' => substr($digits, 3, 2) ?: '',
            'base' => substr($digits, 5, 6) ?: '',
            'branch' => substr($digits, 11, 3) ?: '',
        ];
    }

    /**
     * @param array<string,array<string,mixed>> $detailBuckets
     * @param array<string,string> $staff
     */
    private function pushDetail(
        array &$detailBuckets,
        string $monthKey,
        bool $isBonus,
        string $bucket,
        string $staffId,
        array $staff,
        float $amount,
    ): void {
        $detailKey = implode('|', [$isBonus ? 'bonus' : 'regular', $monthKey, $bucket]);
        if (!isset($detailBuckets[$detailKey])) {
            $detailBuckets[$detailKey] = [
                'kind' => $isBonus ? 'bonus' : 'regular',
                'month_key' => $monthKey,
                'bucket' => $bucket,
                'rows' => [],
            ];
        }

        if (!isset($detailBuckets[$detailKey]['rows'][$staffId])) {
            $detailBuckets[$detailKey]['rows'][$staffId] = [
                'staff_id' => $staffId,
                'staff_name' => $staff['staff_name'] ?? '',
                'staff_division' => $staff['staff_division'] ?? '',
                'company_name' => $staff['company_name'] ?? '',
                'store_name' => $staff['store_name'] ?? '',
                'amount' => 0.0,
            ];
        }

        $detailBuckets[$detailKey]['rows'][$staffId]['amount'] += $amount;
    }

    /**
     * @param array<string,array<string,mixed>> $detailBuckets
     * @return array<string,mixed>|null
     */
    private function buildDetail(
        array $detailBuckets,
        string $detailMonth,
        string $detailBucket,
        string $detailKind,
    ): ?array {
        $detailMonth = trim($detailMonth);
        $detailBucket = trim($detailBucket);
        $detailKind = $detailKind === 'bonus' ? 'bonus' : 'regular';

        if ($detailMonth === '' || $detailBucket === '') {
            return null;
        }

        $detailKey = implode('|', [$detailKind, $detailMonth, $detailBucket]);
        if (!isset($detailBuckets[$detailKey])) {
            return null;
        }

        $detail = $detailBuckets[$detailKey];
        $rows = array_values($detail['rows']);
        usort($rows, static function (array $a, array $b): int {
            return [$a['staff_name'], $a['staff_id']] <=> [$b['staff_name'], $b['staff_id']];
        });

        return [
            'month_key' => $detailMonth,
            'kind' => $detailKind,
            'bucket' => $detailBucket,
            'label' => $this->detailLabel($detailMonth, $detailBucket, $detailKind),
            'rows' => $rows,
            'total_amount' => array_sum(array_map(static fn(array $row): float => (float) $row['amount'], $rows)),
            'total_count' => count($rows),
        ];
    }

    private function detailLabel(string $monthKey, string $detailBucket, string $detailKind): string
    {
        [$year, $month] = array_pad(explode('-', $monthKey), 2, '');
        $prefix = $detailKind === 'bonus' ? '賞与' : '';
        $bucketLabels = [
            'left_regular' => '(1) 常用労働者',
            'left_executive' => '(2) 兼務役員',
            'left_temporary' => '(3) 臨時労働者',
            'right_regular' => '(5) 常用労働者',
            'right_executive' => '(6) 兼務役員',
        ];

        return sprintf(
            '%s%s年%s月 %s の対象者',
            $prefix,
            $year,
            (int) $month,
            $bucketLabels[$detailBucket] ?? $detailBucket
        );
    }

    /**
     * @param array<string,array<string,mixed>> $detailBuckets
     * @return array<string,array<string,mixed>>
     */
    private function buildDetailMap(array $detailBuckets): array
    {
        $detailMap = [];
        foreach ($detailBuckets as $detailKey => $detail) {
            [$kind, $monthKey, $bucket] = array_pad(explode('|', (string) $detailKey, 3), 3, '');
            $rows = array_values($detail['rows'] ?? []);
            usort($rows, static function (array $a, array $b): int {
                return [$a['staff_name'], $a['staff_id']] <=> [$b['staff_name'], $b['staff_id']];
            });

            $detailMap[$detailKey] = [
                'month_key' => $monthKey,
                'kind' => $kind,
                'bucket' => $bucket,
                'label' => $this->detailLabel($monthKey, $bucket, $kind === 'bonus' ? 'bonus' : 'regular'),
                'rows' => array_map(static function (array $row): array {
                    return [
                        'staff_id' => (string) ($row['staff_id'] ?? ''),
                        'staff_name' => (string) ($row['staff_name'] ?? ''),
                        'amount' => (float) ($row['amount'] ?? 0),
                    ];
                }, $rows),
                'total_amount' => array_sum(array_map(static fn(array $row): float => (float) $row['amount'], $rows)),
                'total_count' => count($rows),
            ];
        }

        return $detailMap;
    }

    /**
     * @return array{
     *   filters: array<string,mixed>,
     *   company_meta: array<string,mixed>,
     *   executive_worker_summary: list<array<string,string>>,
     *   monthly_rows: list<array<string,mixed>>,
     *   yearly_totals: array<string,float|int>,
     *   report_totals: array<string,float|int>,
     *   detail: array<string,mixed>|null,
     *   detail_map: array<string,array<string,mixed>>
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
            'company_meta' => $this->companyMeta($companyName),
            'executive_worker_summary' => [],
            'monthly_rows' => [],
            'yearly_totals' => $this->emptyTotals(),
            'report_totals' => [
                'workers_source_total' => 0,
                'workers_total' => 0,
                'wages_total' => 0.0,
                'wages_total_truncated' => 0.0,
                'employment_workers_source_total' => 0,
                'employment_workers' => 0,
                'employment_wages_total' => 0.0,
                'employment_wages_total_truncated' => 0.0,
            ],
            'detail' => null,
            'detail_map' => [],
        ];
    }
}

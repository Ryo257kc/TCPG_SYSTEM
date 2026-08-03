<?php

namespace App\Services\Admin\V2\Sales;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class SalesV2Service
{
    /**
     * @return array{rows: list<array<string, mixed>>, company_totals: list<array<string, mixed>>, target_month: string, company_id: string, grand_total: float}
     */
    public function summary(string $targetMonth, string $companyId): array
    {
        $normalizedMonth = $this->normalizeTargetMonth($targetMonth);
        $start = CarbonImmutable::createFromFormat('Y-m-d', $normalizedMonth . '-01')->startOfMonth();
        $end = $start->addMonth();

        $generalLabel = "一般保険請求";
        $lateElderlyLabel = "後期高齢保険請求";
        $aidLabel = "医療助成請求";
        $counterTitle = "窓口収入";
        $personalTransferLabel = "個人振込";
        $expenseLabel = "店舗経費";

        $generalExpr = "SUM(CASE WHEN je.debit_item_name = N'{$generalLabel}' THEN ISNULL(je.credit_amount, 0) ELSE 0 END)";
        $lateElderlyExpr = "SUM(CASE WHEN je.debit_item_name = N'{$lateElderlyLabel}' THEN ISNULL(je.credit_amount, 0) ELSE 0 END)";
        $aidExpr = "SUM(CASE WHEN je.debit_item_name = N'{$aidLabel}' THEN ISNULL(je.credit_amount, 0) ELSE 0 END)";
        $insuranceExpr = implode(' + ', [$generalExpr, $lateElderlyExpr, $aidExpr]);
        $counterExpr = "SUM(CASE WHEN je.credit_account_title = N'{$counterTitle}' AND je.credit_item_name <> N'{$personalTransferLabel}' THEN ISNULL(je.credit_amount, 0) ELSE 0 END)";
        $personalTransferExpr = "SUM(CASE WHEN je.credit_account_title = N'{$counterTitle}' AND je.credit_item_name = N'{$personalTransferLabel}' THEN ISNULL(je.credit_amount, 0) ELSE 0 END)";
        $selfPayExpr = "SUM(CASE WHEN je.credit_account_title = N'自費収入' AND je.debit_account_title = N'現金' THEN ISNULL(je.credit_amount, 0) ELSE 0 END)";
        $expenseExpr = "SUM(CASE WHEN je.credit_item_name = N'{$expenseLabel}' THEN ISNULL(je.credit_amount, 0) ELSE 0 END)";
        $bankDepositExpr = "{$counterExpr} + {$personalTransferExpr} + {$selfPayExpr} - {$expenseExpr} - {$personalTransferExpr}";
        $totalExpr = implode(' + ', [$insuranceExpr, $counterExpr, $selfPayExpr, $personalTransferExpr]);

        $query = DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries as je')
            ->leftJoin('dbo.mx_departments as d', function ($join): void {
                $join->whereRaw(
                    "LTRIM(RTRIM(CAST(je.credit_department_name AS NVARCHAR(255)))) = LTRIM(RTRIM(CAST(d.store_short_name AS NVARCHAR(255))))"
                );
            })
            ->leftJoin('dbo.mx_stores as s', function ($join): void {
                $join->whereRaw(
                    "LTRIM(RTRIM(CAST(d.official_store_no AS NVARCHAR(255)))) = LTRIM(RTRIM(CAST(s.store_code AS NVARCHAR(255))))"
                );
            })
            ->leftJoin('dbo.mx_companies as c', 'c.company_id', '=', 's.company_id')
            ->where('je.month_date', '>=', $start->format('Y-m-d'))
            ->where('je.month_date', '<', $end->format('Y-m-d'))
            ->where(function ($query): void {
                $query->where('d.has_receipt', true)
                    ->orWhere('d.has_receipt', 1);
            });

        if ($companyId !== '') {
            $query->whereRaw('LTRIM(RTRIM(CAST(c.company_id AS NVARCHAR(255)))) = ?', [$companyId]);
        }

        $rows = $query
            ->selectRaw("
                ISNULL(c.company_id, '') AS company_id,
                MAX(ISNULL(c.company_name, '')) AS company_name,
                ISNULL(d.store_category, je.credit_department_name) AS department_name,
                MAX(ISNULL(s.store_name, '')) AS store_name,
                {$generalExpr} AS general_amount,
                {$lateElderlyExpr} AS late_elderly_amount,
                {$aidExpr} AS aid_amount,
                {$insuranceExpr} AS insurance_amount,
                {$counterExpr} AS counter_amount,
                {$personalTransferExpr} AS personal_transfer_amount,
                {$selfPayExpr} AS self_pay_amount,
                {$expenseExpr} AS expense_amount,
                {$bankDepositExpr} AS bank_deposit_amount,
                {$totalExpr} AS total_amount
            ")
            ->groupByRaw("ISNULL(c.company_id, ''), ISNULL(d.store_category, je.credit_department_name)")
            ->orderByRaw("ISNULL(c.company_id, ''), ISNULL(d.store_category, je.credit_department_name)")
            ->get()
            ->map(static function ($row): array {
                return [
                    'company_id' => trim((string) ($row->company_id ?? '')),
                    'company_name' => trim((string) ($row->company_name ?? '')),
                    'department_name' => trim((string) ($row->department_name ?? '')),
                    'store_name' => trim((string) ($row->store_name ?? '')),
                    'general_amount' => (float) ($row->general_amount ?? 0),
                    'late_elderly_amount' => (float) ($row->late_elderly_amount ?? 0),
                    'aid_amount' => (float) ($row->aid_amount ?? 0),
                    'insurance_amount' => (float) ($row->insurance_amount ?? 0),
                    'counter_amount' => (float) ($row->counter_amount ?? 0),
                    'personal_transfer_amount' => (float) ($row->personal_transfer_amount ?? 0),
                    'self_pay_amount' => (float) ($row->self_pay_amount ?? 0),
                    'expense_amount' => (float) ($row->expense_amount ?? 0),
                    'bank_deposit_amount' => (float) ($row->bank_deposit_amount ?? 0),
                    'total_amount' => (float) ($row->total_amount ?? 0),
                ];
            })
            ->filter(static fn(array $row): bool => !($row['department_name'] === '' && (float) $row['total_amount'] === 0.0))
            ->values()
            ->all();

        $grandTotal = array_reduce(
            $rows,
            static fn(float $carry, array $row): float => $carry + (float) ($row['total_amount'] ?? 0),
            0.0
        );

        return [
            'rows' => $rows,
            'company_totals' => $this->companyTotals($rows),
            'target_month' => $normalizedMonth,
            'company_id' => $companyId,
            'grand_total' => $grandTotal,
        ];
    }


    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function companyTotals(array $rows): array
    {
        $totals = [];
        $amountKeys = [
            'insurance_amount',
            'counter_amount',
            'personal_transfer_amount',
            'self_pay_amount',
            'total_amount',
            'expense_amount',
            'bank_deposit_amount',
        ];

        foreach ($rows as $row) {
            $companyId = trim((string) ($row['company_id'] ?? ''));
            $companyName = trim((string) ($row['company_name'] ?? ''));
            $key = $companyId !== '' ? $companyId : ($companyName !== '' ? $companyName : '会社未設定');

            if (!array_key_exists($key, $totals)) {
                $totals[$key] = [
                    'company_id' => $companyId,
                    'company_name' => $companyName !== '' ? $companyName : $key,
                    'department_count' => 0,
                    'insurance_amount' => 0.0,
                    'counter_amount' => 0.0,
                    'personal_transfer_amount' => 0.0,
                    'self_pay_amount' => 0.0,
                    'total_amount' => 0.0,
                    'expense_amount' => 0.0,
                    'bank_deposit_amount' => 0.0,
                ];
            }

            $totals[$key]['department_count']++;
            foreach ($amountKeys as $amountKey) {
                $totals[$key][$amountKey] += (float) ($row[$amountKey] ?? 0);
            }
        }

        return array_values($totals);
    }

    /**
     * @return array{stores: list<array<string, mixed>>, target_month: string, company_id: string, grand_total: float}
     */
    public function pdfSummary(string $targetMonth, string $companyId): array
    {
        $summary = $this->summary($targetMonth, $companyId);

        $stores = [];
        foreach ($summary['rows'] as $row) {
            $storeName = trim((string) ($row['store_name'] ?? ''));
            $groupKey = $storeName !== '' ? $storeName : '店舗未設定';

            if (!array_key_exists($groupKey, $stores)) {
                $stores[$groupKey] = [
                    'store_name' => $groupKey,
                    'rows' => [],
                    'store_total' => 0.0,
                ];
            }

            $stores[$groupKey]['rows'][] = $row;
            $stores[$groupKey]['store_total'] += (float) ($row['total_amount'] ?? 0);
        }

        return [
            'stores' => array_values($stores),
            'target_month' => $summary['target_month'],
            'company_id' => $summary['company_id'],
            'grand_total' => $summary['grand_total'],
        ];
    }


    /**
     * @return array{content: string, target_month: string, company_id: string, row_count: int}
     */
    public function freeeJournalCsv(string $targetMonth, string $companyId): array
    {
        $normalizedMonth = $this->normalizeTargetMonth($targetMonth);
        $start = CarbonImmutable::createFromFormat('Y-m-d', $normalizedMonth . '-01')->startOfMonth();
        $end = $start->addMonth();

        $query = DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries as je')
            ->leftJoin('dbo.mx_departments as d', function ($join): void {
                $join->whereRaw(
                    "LTRIM(RTRIM(CAST(je.credit_department_name AS NVARCHAR(255)))) = LTRIM(RTRIM(CAST(d.store_short_name AS NVARCHAR(255))))"
                );
            })
            ->leftJoin('dbo.mx_stores as s', function ($join): void {
                $join->whereRaw(
                    "LTRIM(RTRIM(CAST(d.official_store_no AS NVARCHAR(255)))) = LTRIM(RTRIM(CAST(s.store_code AS NVARCHAR(255))))"
                );
            })
            ->leftJoin('dbo.mx_companies as c', 'c.company_id', '=', 's.company_id')
            ->where('je.month_date', '>=', $start->format('Y-m-d'))
            ->where('je.month_date', '<', $end->format('Y-m-d'))
            ->where(function ($query): void {
                $query->where('d.has_receipt', true)
                    ->orWhere('d.has_receipt', 1);
            })
            ->where(function ($query): void {
                $query->where(function ($salesQuery): void {
                    $salesQuery->where('je.debit_account_title', '医療未収入金')
                        ->where('je.credit_account_title', '保険収入');
                })
                    ->orWhere(function ($salesQuery): void {
                        $salesQuery->where('je.debit_account_title', '現金')
                            ->whereIn('je.credit_account_title', ['窓口収入', '自費収入']);
                    });
            });

        if ($companyId !== '') {
            $query->whereRaw('LTRIM(RTRIM(CAST(c.company_id AS NVARCHAR(255)))) = ?', [$companyId]);
        }

        $rows = $query
            ->select([
                'je.occurred_at',
                'je.debit_account_title',
                'je.debit_amount',
                'je.debit_tax_category',
                'je.debit_item_name',
                'je.debit_counterparty',
                DB::raw('ISNULL(d.store_category, je.debit_department_name) as csv_debit_department_name'),
                'je.debit_memo_tag',
                'je.credit_account_title',
                'je.credit_amount',
                'je.credit_tax_category',
                'je.credit_item_name',
                'je.credit_counterparty',
                DB::raw('ISNULL(d.store_category, je.credit_department_name) as csv_credit_department_name'),
                'je.credit_memo_tag',
                'je.summary_text',
                'je.journal_entry_id',
            ])
            ->orderBy('je.occurred_at')
            ->orderBy('je.journal_entry_id')
            ->get();

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, [
            '日付',
            '借方勘定科目',
            '借方金額',
            '借方税区分',
            '借方品目',
            '借方取引先',
            '借方部門',
            '借方メモタグ',
            '貸方勘定科目',
            '貸方金額',
            '貸方税区分',
            '貸方品目',
            '貸方取引先',
            '貸方部門',
            '貸方メモタグ',
            '摘要',
        ]);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $this->formatCsvDate($row->occurred_at ?? null),
                $this->csvText($row->debit_account_title ?? null),
                $this->csvMoney($row->debit_amount ?? 0),
                $this->csvText($row->debit_tax_category ?? null),
                $this->csvText($row->debit_item_name ?? null),
                $this->csvText($row->debit_counterparty ?? null),
                $this->csvText($row->csv_debit_department_name ?? null),
                $this->csvText($row->debit_memo_tag ?? null),
                $this->csvText($row->credit_account_title ?? null),
                $this->csvMoney($row->credit_amount ?? 0),
                $this->csvText($row->credit_tax_category ?? null),
                $this->csvText($row->credit_item_name ?? null),
                $this->csvText($row->credit_counterparty ?? null),
                $this->csvText($row->csv_credit_department_name ?? null),
                $this->csvText($row->credit_memo_tag ?? null),
                $this->csvText($row->summary_text ?? null),
            ]);
        }

        rewind($handle);
        $content = (string) stream_get_contents($handle);
        fclose($handle);

        return [
            'content' => mb_convert_encoding($content, 'SJIS-win', 'UTF-8'),
            'target_month' => $normalizedMonth,
            'company_id' => $companyId,
            'row_count' => $rows->count(),
        ];
    }

    private function formatCsvDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return CarbonImmutable::parse((string) $value)->format('Y/m/d');
        } catch (\Throwable) {
            return trim((string) $value);
        }
    }

    private function csvMoney(mixed $value): string
    {
        $amount = (int) round((float) $value);
        return '\\' . (string) $amount;
    }

    private function csvText(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    private function normalizeTargetMonth(string $targetMonth): string
    {
        $value = trim($targetMonth);
        if ($value === '') {
            return now()->format('Y-m');
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $value . '-01')->format('Y-m');
        } catch (\Throwable) {
            return now()->format('Y-m');
        }
    }
}

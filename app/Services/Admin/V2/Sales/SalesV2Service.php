<?php

namespace App\Services\Admin\V2\Sales;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class SalesV2Service
{
    /**
     * @return array{rows: list<array<string, mixed>>, target_month: string, company_id: string, grand_total: float}
     */
    public function summary(string $targetMonth, string $companyId): array
    {
        $normalizedMonth = $this->normalizeTargetMonth($targetMonth);
        $start = CarbonImmutable::createFromFormat('Y-m', $normalizedMonth)->startOfMonth();
        $end = $start->addMonth();

        $insuranceExpr = implode(' + ', [
            "SUM(CASE WHEN je.debit_item_name = N'一般保険請求' THEN ISNULL(je.credit_amount, 0) ELSE 0 END)",
            "SUM(CASE WHEN je.debit_item_name = N'後期高齢保険請求' THEN ISNULL(je.credit_amount, 0) ELSE 0 END)",
            "SUM(CASE WHEN je.debit_item_name = N'医療助成請求' THEN ISNULL(je.credit_amount, 0) ELSE 0 END)",
        ]);

        $counterExpr = "SUM(CASE WHEN je.credit_account_title = N'窓口収入' AND je.credit_item_name <> N'個人振込' THEN ISNULL(je.credit_amount, 0) ELSE 0 END)";
        $selfPayExpr = "SUM(CASE WHEN je.credit_item_name = N'自費' THEN ISNULL(je.credit_amount, 0) ELSE 0 END)";
        $expenseExpr = "SUM(CASE WHEN je.credit_item_name = N'店舗経費' THEN ISNULL(je.credit_amount, 0) ELSE 0 END)";
        $personalTransferExpr = "SUM(CASE WHEN je.credit_account_title = N'窓口収入' AND je.credit_item_name = N'個人振込' THEN ISNULL(je.credit_amount, 0) ELSE 0 END)";
        $totalExpr = implode(' + ', [$insuranceExpr, $counterExpr, $selfPayExpr, $personalTransferExpr]);
        $generalLabel = "\u{4E00}\u{822C}\u{4FDD}\u{967A}\u{8ACB}\u{6C42}";
        $lateElderlyLabel = "\u{5F8C}\u{671F}\u{9AD8}\u{9F62}\u{4FDD}\u{967A}\u{8ACB}\u{6C42}";
        $aidLabel = "\u{533B}\u{7642}\u{52A9}\u{6210}\u{8ACB}\u{6C42}";
        $counterTitle = "\u{7A93}\u{53E3}\u{53CE}\u{5165}";
        $personalTransferLabel = "\u{500B}\u{4EBA}\u{632F}\u{8FBC}";
        $selfPayLabel = "\u{81EA}\u{8CBB}";
        $expenseLabel = "\u{5E97}\u{8217}\u{7D4C}\u{8CBB}";

        $generalExpr = "SUM(CASE WHEN je.debit_item_name = N'{$generalLabel}' THEN ISNULL(je.credit_amount, 0) ELSE 0 END)";
        $lateElderlyExpr = "SUM(CASE WHEN je.debit_item_name = N'{$lateElderlyLabel}' THEN ISNULL(je.credit_amount, 0) ELSE 0 END)";
        $aidExpr = "SUM(CASE WHEN je.debit_item_name = N'{$aidLabel}' THEN ISNULL(je.credit_amount, 0) ELSE 0 END)";
        $insuranceExpr = implode(' + ', [$generalExpr, $lateElderlyExpr, $aidExpr]);
        $counterExpr = "SUM(CASE WHEN je.credit_account_title = N'{$counterTitle}' AND je.credit_item_name <> N'{$personalTransferLabel}' THEN ISNULL(je.credit_amount, 0) ELSE 0 END)";
        $personalTransferExpr = "SUM(CASE WHEN je.credit_account_title = N'{$counterTitle}' AND je.credit_item_name = N'{$personalTransferLabel}' THEN ISNULL(je.credit_amount, 0) ELSE 0 END)";
        $selfPayExpr = "SUM(CASE WHEN je.credit_item_name = N'{$selfPayLabel}' THEN ISNULL(je.credit_amount, 0) ELSE 0 END)";
        $expenseExpr = "SUM(CASE WHEN je.credit_item_name = N'{$expenseLabel}' THEN ISNULL(je.credit_amount, 0) ELSE 0 END)";
        $bankDepositExpr = "{$counterExpr} + {$personalTransferExpr} + {$selfPayExpr} - {$expenseExpr}";
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
            ->where('je.month_date', '<', $end->format('Y-m-d'));

        if ($companyId !== '') {
            $query->whereRaw('LTRIM(RTRIM(CAST(c.company_id AS NVARCHAR(255)))) = ?', [$companyId]);
        }

        $rows = $query
            ->selectRaw("
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
            ->groupByRaw('ISNULL(d.store_category, je.credit_department_name)')
            ->orderByRaw('ISNULL(d.store_category, je.credit_department_name)')
            ->get()
            ->map(static function ($row): array {
                return [
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
            ->values()
            ->all();

        $grandTotal = array_reduce(
            $rows,
            static fn (float $carry, array $row): float => $carry + (float) ($row['total_amount'] ?? 0),
            0.0
        );

        return [
            'rows' => $rows,
            'target_month' => $normalizedMonth,
            'company_id' => $companyId,
            'grand_total' => $grandTotal,
        ];
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

    private function normalizeTargetMonth(string $targetMonth): string
    {
        $value = trim($targetMonth);
        if ($value === '') {
            return now()->format('Y-m');
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m', $value)->format('Y-m');
        } catch (\Throwable) {
            return now()->format('Y-m');
        }
    }
}

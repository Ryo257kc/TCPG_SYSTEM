<?php

namespace App\Http\Controllers\Admin\V2;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\Sales\SalesV2Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AccountingV2Controller extends Controller
{
    public function __construct(
        private readonly SalesV2Service $salesService,
    ) {}

    public function journalEntries(Request $request): View
    {
        $dateFrom = trim((string) $request->query('date_from', now()->startOfMonth()->format('Y-m-d')));
        $dateTo = trim((string) $request->query('date_to', now()->endOfMonth()->format('Y-m-d')));
        $selectedCompanyName = trim((string) $request->query('company_name_short', ''));
        $counterparty = trim((string) $request->query('counterparty', ''));
        $amount = trim((string) $request->query('amount', ''));
        $summaryText = trim((string) $request->query('summary_text', ''));
        $accountTitle = trim((string) $request->query('account_title', ''));
        $itemName = trim((string) $request->query('item_name', ''));
        $departmentName = trim((string) $request->query('department_name', ''));
        $managementNumber = trim((string) $request->query('management_number', ''));
        $journalBreakdown = trim((string) $request->query('journal_breakdown', ''));
        $parsedAmount = $this->parseAmountValue($amount);

        $companyOptions = DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries')
            ->select('company_name_short')
            ->whereNotNull('company_name_short')
            ->where('company_name_short', '<>', '')
            ->groupBy('company_name_short')
            ->orderBy('company_name_short')
            ->pluck('company_name_short')
            ->map(fn($value): string => trim((string) $value))
            ->filter(fn(string $value): bool => $value !== '')
            ->values()
            ->all();

        $counterpartyOptions = $this->fetchDistinctUnionOptions('debit_counterparty', 'credit_counterparty');
        $amountOptions = $this->fetchDistinctUnionOptions('debit_amount', 'credit_amount', true);
        $summaryTextOptions = $this->fetchDistinctOptions('summary_text');
        $accountTitleOptions = $this->fetchDistinctUnionOptions('debit_account_title', 'credit_account_title');
        $itemNameOptions = $this->fetchDistinctUnionOptions('debit_item_name', 'credit_item_name');
        $departmentOptions = $this->fetchDistinctUnionOptions('debit_department_name', 'credit_department_name');
        $departmentSelectOptions = $this->fetchDepartmentSelectOptions();
        $managementNumberOptions = $this->fetchDistinctOptions('management_number');
        $journalBreakdownOptions = $this->fetchDistinctOptions('journal_breakdown');

        $rows = DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries as entry')
            ->select([
                'entry.journal_entry_id',
                'entry.journal_breakdown',
                'entry.management_number',
                'entry.month_date',
                'entry.occurred_at',
                'entry.vault_name',
                'entry.debit_account_title',
                'entry.debit_amount',
                'entry.debit_item_name',
                'entry.debit_counterparty',
                'entry.debit_department_name',
                'entry.debit_memo_tag',
                'entry.credit_account_title',
                'entry.credit_amount',
                'entry.credit_item_name',
                'entry.credit_counterparty',
                'entry.credit_department_name',
                'entry.credit_memo_tag',
                'entry.summary_text',
                'entry.company_name_short',
                'entry.deposit_name',
                'entry.allocation_ratio',
                'entry.is_trial_balance_excluded',
                'entry.is_upload_unnecessary',
                'entry.is_reward_excluded',
                'entry.is_confirmation_checked',
            ])
            ->when($dateFrom !== '', fn($query) => $query->whereDate('entry.occurred_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn($query) => $query->whereDate('entry.occurred_at', '<=', $dateTo))
            ->when($selectedCompanyName !== '', fn($query) => $query->where('entry.company_name_short', 'like', '%' . $selectedCompanyName . '%'))
            ->when($counterparty !== '', function ($query) use ($counterparty) {
                $query->where(function ($subQuery) use ($counterparty) {
                    $subQuery->where('entry.debit_counterparty', 'like', '%' . $counterparty . '%')
                        ->orWhere('entry.credit_counterparty', 'like', '%' . $counterparty . '%');
                });
            })
            ->when($parsedAmount !== null, function ($query) use ($parsedAmount) {
                $query->where(function ($subQuery) use ($parsedAmount) {
                    $subQuery->where('entry.debit_amount', $parsedAmount)
                        ->orWhere('entry.credit_amount', $parsedAmount);
                });
            })
            ->when($summaryText !== '', fn($query) => $query->where('entry.summary_text', 'like', '%' . $summaryText . '%'))
            ->when($accountTitle !== '', function ($query) use ($accountTitle) {
                $query->where(function ($subQuery) use ($accountTitle) {
                    $subQuery->where('entry.debit_account_title', 'like', '%' . $accountTitle . '%')
                        ->orWhere('entry.credit_account_title', 'like', '%' . $accountTitle . '%');
                });
            })
            ->when($itemName !== '', function ($query) use ($itemName) {
                $query->where(function ($subQuery) use ($itemName) {
                    $subQuery->where('entry.debit_item_name', 'like', '%' . $itemName . '%')
                        ->orWhere('entry.credit_item_name', 'like', '%' . $itemName . '%');
                });
            })
            ->when($departmentName !== '', function ($query) use ($departmentName) {
                $query->where(function ($subQuery) use ($departmentName) {
                    $subQuery->where('entry.debit_department_name', 'like', '%' . $departmentName . '%')
                        ->orWhere('entry.credit_department_name', 'like', '%' . $departmentName . '%');
                });
            })
            ->when($managementNumber !== '', fn($query) => $query->where('entry.management_number', 'like', '%' . $managementNumber . '%'))
            ->when($journalBreakdown !== '', fn($query) => $query->where('entry.journal_breakdown', 'like', '%' . $journalBreakdown . '%'))
            ->orderByDesc('entry.occurred_at')
            ->orderByDesc('entry.journal_entry_id')
            ->get()
            ->map(fn($row): array => [
                'journal_entry_id' => (int) ($row->journal_entry_id ?? 0),
                'journal_breakdown' => trim((string) ($row->journal_breakdown ?? '')),
                'management_number' => trim((string) ($row->management_number ?? '')),
                'month_date' => $this->formatDateValue($row->month_date, 'Y/m/d'),
                'occurred_at' => $this->formatDateValue($row->occurred_at, 'Y/m/d'),
                'occurred_at_raw' => $this->formatDateValue($row->occurred_at, 'Y-m-d'),
                'vault_name' => trim((string) ($row->vault_name ?? '')),
                'debit_account_title' => trim((string) ($row->debit_account_title ?? '')),
                'debit_amount' => $this->formatMoneyValue($row->debit_amount),
                'debit_item_name' => trim((string) ($row->debit_item_name ?? '')),
                'debit_counterparty' => trim((string) ($row->debit_counterparty ?? '')),
                'debit_department_name' => trim((string) ($row->debit_department_name ?? '')),
                'debit_memo_tag' => trim((string) ($row->debit_memo_tag ?? '')),
                'credit_account_title' => trim((string) ($row->credit_account_title ?? '')),
                'credit_amount' => $this->formatMoneyValue($row->credit_amount),
                'credit_item_name' => trim((string) ($row->credit_item_name ?? '')),
                'credit_counterparty' => trim((string) ($row->credit_counterparty ?? '')),
                'credit_department_name' => trim((string) ($row->credit_department_name ?? '')),
                'credit_memo_tag' => trim((string) ($row->credit_memo_tag ?? '')),
                'summary_text' => trim((string) ($row->summary_text ?? '')),
                'company_name_short' => trim((string) ($row->company_name_short ?? '')),
                'deposit_name' => trim((string) ($row->deposit_name ?? '')),
                'allocation_ratio' => trim((string) ($row->allocation_ratio ?? '')),
                'is_trial_balance_excluded' => (int) ($row->is_trial_balance_excluded ?? 0) !== 0,
                'is_upload_unnecessary' => (int) ($row->is_upload_unnecessary ?? 0) !== 0,
                'is_reward_excluded' => (int) ($row->is_reward_excluded ?? 0) !== 0,
                'is_confirmation_checked' => (int) ($row->is_confirmation_checked ?? 0) !== 0,
            ])
            ->all();

        $journalGroups = collect($rows)
            ->groupBy(fn(array $row): string => ($row['company_name_short'] ?: '-') . "\n" . ($row['occurred_at'] ?: '-') . "\n" . ($row['journal_breakdown'] ?: '-'))
            ->map(function ($items): array {
                $first = $items->first();
                $debitTotal = $items->sum(fn(array $row): float => (float) str_replace(',', '', $row['debit_amount'] ?: '0'));
                $creditTotal = $items->sum(fn(array $row): float => (float) str_replace(',', '', $row['credit_amount'] ?: '0'));

                return [
                    'group_key' => md5(($first['company_name_short'] ?? '') . '|' . ($first['occurred_at'] ?? '') . '|' . ($first['journal_breakdown'] ?? '')),
                    'journal_breakdown' => $first['journal_breakdown'] ?? '',
                    'occurred_at' => $first['occurred_at'] ?? '',
                    'management_number' => $first['management_number'] ?? '',
                    'company_name_short' => $first['company_name_short'] ?? '',
                    'summary_text' => $first['summary_text'] ?? '',
                    'debit_total' => $this->formatMoneyValue($debitTotal),
                    'credit_total' => $this->formatMoneyValue($creditTotal),
                    'detail_count' => $items->count(),
                    'details' => $items->values()->all(),
                ];
            })
            ->values()
            ->all();

        return view('admin_v2.work.journal_entries.index', [
            'rows' => $rows,
            'journalGroups' => $journalGroups,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'companyOptions' => $companyOptions,
            'counterpartyOptions' => $counterpartyOptions,
            'amountOptions' => $amountOptions,
            'summaryTextOptions' => $summaryTextOptions,
            'accountTitleOptions' => $accountTitleOptions,
            'itemNameOptions' => $itemNameOptions,
            'departmentOptions' => $departmentOptions,
            'departmentSelectOptions' => $departmentSelectOptions,
            'managementNumberOptions' => $managementNumberOptions,
            'journalBreakdownOptions' => $journalBreakdownOptions,
            'selectedCompanyName' => $selectedCompanyName,
            'counterparty' => $counterparty,
            'amount' => $amount,
            'summaryText' => $summaryText,
            'accountTitle' => $accountTitle,
            'itemName' => $itemName,
            'departmentName' => $departmentName,
            'managementNumber' => $managementNumber,
            'journalBreakdown' => $journalBreakdown,
        ]);
    }

    // さくら報酬計算
    public function sakuraRewardPrint(Request $request): View
    {
        $dateFrom = trim((string) $request->query('date_from', now()->startOfMonth()->format('Y-m-d')));
        $dateTo = trim((string) $request->query('date_to', now()->endOfMonth()->format('Y-m-d')));

        try {
            $from = Carbon::parse($dateFrom)->startOfDay();
            $to = Carbon::parse($dateTo)->endOfDay();
        } catch (\Throwable) {
            $from = now()->startOfMonth()->startOfDay();
            $to = now()->endOfMonth()->endOfDay();
        }

        $targetMonth = $from->format('Y-m');
        $payrollPaymentMonth = $from->copy()->addMonthNoOverflow();
        $payrollLabel = $payrollPaymentMonth->format('Y/n') . '月給与';
        $bonusLabel = $payrollPaymentMonth->format('Y/n') . '月賞与';
        $salesSummary = $this->salesService->summary($targetMonth, '');
        $sakuraSalesRows = array_values(array_filter(
            (array) ($salesSummary['rows'] ?? []),
            fn(array $row): bool => $this->isSakuraStoreSalesRow($row)
        ));
        $sakuraSales = array_sum(array_map(
            static fn(array $row): float => (float) ($row['total_amount'] ?? 0),
            $sakuraSalesRows
        ));

        $rewardSelectColumns = [
            'entry.journal_entry_id',
            'entry.journal_breakdown',
            'entry.management_number',
            'entry.month_date',
            'entry.occurred_at',
            'entry.vault_name',
            'entry.debit_account_title',
            'entry.debit_amount',
            'entry.debit_item_name',
            'entry.debit_counterparty',
            'entry.debit_department_name',
            'entry.debit_memo_tag',
            'entry.credit_account_title',
            'entry.credit_amount',
            'entry.credit_item_name',
            'entry.credit_counterparty',
            'entry.credit_department_name',
            'entry.credit_memo_tag',
            'entry.summary_text',
            'entry.company_name_short',
            'entry.allocation_ratio',
            'account.expense_sort_name',
            'account.sub_category_name',
            'account.middle_category_name',
            'account.major_category_name',
        ];

        $baseRewardQuery = function () use ($rewardSelectColumns) {
            return DB::connection('sqlsrv')
                ->table('dbo.mx_journal_entries as entry')
                ->leftJoin('dbo.mx_account_titles as account', 'entry.debit_account_title', '=', 'account.account_title_name')
                ->where(function ($query): void {
                    $query->whereNull('entry.closing_journal_entry')
                        ->orWhereRaw("LTRIM(RTRIM(CAST(entry.closing_journal_entry AS NVARCHAR(50)))) = ''")
                        ->orWhereRaw("LTRIM(RTRIM(CAST(entry.closing_journal_entry AS NVARCHAR(50)))) = '0'");
                })
                ->where(function ($query): void {
                    $query->whereNull('entry.is_reward_excluded')
                        ->orWhere('entry.is_reward_excluded', 0);
                })
                ->select($rewardSelectColumns);
        };

        $expenseRows = $baseRewardQuery()
            ->whereBetween('entry.occurred_at', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->where(function ($query): void {
                $query->whereNull('entry.management_number')
                    ->orWhere(function ($subQuery): void {
                        $subQuery->where('entry.management_number', 'not like', '%月給与%')
                            ->where('entry.management_number', 'not like', '%月賞与%');
                    });
            })
            ->orderBy('entry.occurred_at')
            ->orderBy('entry.journal_entry_id')
            ->get()
            ->map(fn($row): array => $this->rewardCalculationRow($row, $from, $to, $payrollLabel, $bonusLabel))
            ->filter(fn(array $row): bool => $row['item_category'] !== '除外')
            ->values()
            ->all();

        $salaryRows = $baseRewardQuery()
            ->where('entry.management_number', 'like', '%' . $payrollLabel . '%')
            ->orderBy('entry.occurred_at')
            ->orderBy('entry.journal_entry_id')
            ->get()
            ->map(fn($row): array => $this->rewardCalculationRow($row, $from, $to, $payrollLabel, $bonusLabel))
            ->filter(fn(array $row): bool => $row['item_category'] !== '除外')
            ->values()
            ->all();

        $bonusRows = $baseRewardQuery()
            ->where('entry.management_number', 'like', '%' . $bonusLabel . '%')
            ->orderBy('entry.occurred_at')
            ->orderBy('entry.journal_entry_id')
            ->get()
            ->map(fn($row): array => $this->rewardCalculationRow($row, $from, $to, $payrollLabel, $bonusLabel))
            ->filter(fn(array $row): bool => $row['item_category'] !== '除外')
            ->values()
            ->all();

        $payrollRows = array_values(array_merge($salaryRows, $bonusRows));

        $storeExpenseRows = array_values(array_filter($expenseRows, function (array $row): bool {
            return $this->containsSakura((string) ($row['debit_department_name'] ?? ''))
                && !in_array((string) ($row['debit_account_title'] ?? ''), ['医療未収入金', '現金'], true);
        }));

        $storePayrollRows = array_values(array_filter($payrollRows, function (array $row): bool {
            $item = trim((string) ($row['item'] ?? ''));

            return trim((string) ($row['debit_department_name'] ?? '')) === 'T_さくら 店舗'
                && ($item === '' || $item !== 'イノウエマサヤ井上真也');
        }));

        $storeRows = array_values(array_merge($storeExpenseRows, $storePayrollRows));

        $companyExpenseRows = array_values(array_filter($expenseRows, function (array $row): bool {
            return (float) ($row['allocation_ratio_number'] ?? 0) > 0
                && !$this->containsSakura((string) ($row['debit_department_name'] ?? ''));
        }));

        $companyPayrollRows = array_values(array_filter($payrollRows, function (array $row): bool {
            return (float) ($row['allocation_ratio_number'] ?? 0) > 0;
        }));

        $companyRows = array_values(array_merge($companyExpenseRows, $companyPayrollRows));

        $storeGroups = $this->rewardExpenseGroups($storeRows, 'expense_amount');
        $companyGroups = $this->rewardCompanyGroups($companyRows, 'allocated_amount');
        $storeExpenseTotal = (int) floor(array_sum(array_map(
            static fn(array $group): float => (float) ($group['total'] ?? 0),
            $storeGroups
        )));
        $companyExpenseTotal = (int) floor(array_sum(array_map(
            static fn(array $company): float => (float) ($company['total'] ?? 0),
            $companyGroups
        )));

        $profit = (int) floor($sakuraSales - $storeExpenseTotal - $companyExpenseTotal);
        $inoueRate = 0.66;
        $companyRate = 0.34;
        $guaranteeFee = 10000;
        $inoueProfit = (int) round($profit * $inoueRate, 0, PHP_ROUND_HALF_UP);
        $companyProfit = $profit - $inoueProfit;
        $inouePayment = $inoueProfit - $guaranteeFee;

        return view('admin_v2.work.journal_entries.sakura_reward_print', [
            'dateFrom' => $from->format('Y-m-d'),
            'dateTo' => $to->format('Y-m-d'),
            'targetMonth' => $targetMonth,
            'sakuraSalesRows' => $sakuraSalesRows,
            'sakuraSales' => $sakuraSales,
            'storeRows' => $storeRows,
            'companyRows' => $companyRows,
            'storeGroups' => $storeGroups,
            'companyGroups' => $companyGroups,
            'summary' => [
                'sakura_sales' => $sakuraSales,
                'store_expense_total' => $storeExpenseTotal,
                'company_expense_total' => $companyExpenseTotal,
                'profit' => $profit,
                'inoue_rate' => $inoueRate,
                'company_rate' => $companyRate,
                'guarantee_fee' => $guaranteeFee,
                'inoue_profit' => $inoueProfit,
                'company_profit' => $companyProfit,
                'inoue_payment' => $inouePayment,
                'total_payment' => $inouePayment + $companyProfit,
            ],
        ]);
    }

    public function importJournalEntries(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'csv_file' => ['required', 'file'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date'],
            'company_name_short' => ['required', 'string', 'max:255'],
        ]);

        $dateFrom = Carbon::parse((string) $data['date_from'])->startOfDay();
        $dateTo = Carbon::parse((string) $data['date_to'])->endOfDay();
        $companyNameShort = trim((string) $data['company_name_short']);
        $csvRows = $this->readCsvAssocRows($request->file('csv_file')->getPathname());

        if ($csvRows === []) {
            return redirect()->route('admin.work.journal_entries', [
                'date_from' => $data['date_from'],
                'date_to' => $data['date_to'],
                'company_name_short' => $companyNameShort,
            ])->with('errorMessage', 'CSVに取り込める行がありませんでした。');
        }

        $departmentMap = DB::connection('sqlsrv')
            ->table('dbo.mx_departments')
            ->select(['store_category', 'store_short_name'])
            ->whereNotNull('store_category')
            ->where('store_category', '<>', '')
            ->get()
            ->mapWithKeys(fn($row): array => [
                trim((string) ($row->store_category ?? '')) => trim((string) ($row->store_short_name ?? '')),
            ])
            ->all();

        $journalColumns = array_flip(Schema::connection('sqlsrv')->getColumnListing('mx_journal_entries'));
        $importedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $invalidAmountCount = 0;

        DB::connection('sqlsrv')->transaction(function () use ($csvRows, $dateFrom, $dateTo, $companyNameShort, $departmentMap, $journalColumns, &$importedCount, &$updatedCount, &$skippedCount, &$invalidAmountCount): void {
            foreach ($csvRows as $csvRow) {
                $occurredAt = $this->parseCsvDate($this->csvValue($csvRow, '取引日'));
                if ($occurredAt === null || $occurredAt->lt($dateFrom) || $occurredAt->gt($dateTo)) {
                    $skippedCount++;
                    continue;
                }

                $creditAccountTitle = $this->csvValue($csvRow, '貸方勘定科目');
                if (in_array($creditAccountTitle, ['保険収入', '自費収入', '窓口収入'], true)) {
                    $skippedCount++;
                    continue;
                }

                $journalBreakdown = $this->journalBreakdownValue($this->csvValue($csvRow, '仕訳番号'));
                if ($journalBreakdown === '') {
                    $skippedCount++;
                    continue;
                }

                // 手動編集（updateJournalEntry/updateJournalEntryGroup）は金額が数値でないと
                // エラーで弾くのに対し、CSV取込だけは非数値をnullで黙って取り込んでいた。
                // 空欄（貸借どちらかしか使わない行）は許容し、非空かつ非数値の場合のみ弾く。
                $debitAmountRaw = $this->csvValue($csvRow, '借方金額');
                $creditAmountRaw = $this->csvValue($csvRow, '貸方金額');
                if (($debitAmountRaw !== '' && $this->parseCsvMoney($debitAmountRaw) === null)
                    || ($creditAmountRaw !== '' && $this->parseCsvMoney($creditAmountRaw) === null)) {
                    $invalidAmountCount++;
                    continue;
                }

                $payload = [
                    'journal_breakdown' => $journalBreakdown,
                    'month_date' => $occurredAt->copy()->startOfMonth()->format('Y-m-d'),
                    'occurred_at' => $occurredAt->format('Y-m-d'),
                    'deposit_name' => $this->depositNameFromCsv($csvRow),
                    'debit_account_title' => $this->csvValue($csvRow, '借方勘定科目'),
                    'debit_amount' => $this->parseCsvMoney($debitAmountRaw),
                    'debit_tax_category' => $this->csvValue($csvRow, '借方税区分'),
                    'debit_item_name' => $this->csvValue($csvRow, '借方品目'),
                    'debit_department_name' => $departmentMap[$this->csvValue($csvRow, '借方部門')] ?? null,
                    'debit_memo_tag' => $this->csvValue($csvRow, '借方メモ', '借方メモタグ'),
                    'credit_account_title' => $creditAccountTitle,
                    'credit_amount' => $this->parseCsvMoney($creditAmountRaw),
                    'credit_tax_category' => $this->csvValue($csvRow, '貸方税区分'),
                    'credit_item_name' => $this->csvValue($csvRow, '貸方品目'),
                    'credit_department_name' => $departmentMap[$this->csvValue($csvRow, '貸方部門')] ?? null,
                    'credit_memo_tag' => $this->csvValue($csvRow, '貸方メモ', '貸方メモタグ'),
                    'debit_counterparty' => $this->csvValue($csvRow, '借方取引先名', '借方取引先'),
                    'credit_counterparty' => $this->csvValue($csvRow, '貸方取引先名', '貸方取引先'),
                    'summary_text' => $this->csvValue($csvRow, '取引内容'),
                    'management_number' => $this->csvValue($csvRow, '管理番号'),
                    'company_name_short' => $companyNameShort,
                    'allocation_ratio' => $this->csvValue($csvRow, '分割割合'),
                    'closing_journal_entry' => $this->csvValue($csvRow, '決算整理仕訳'),
                    'journal_note' => $this->csvValue($csvRow, '借方備考') . $this->csvValue($csvRow, '貸方備考'),
                    'updated_at' => now(),
                ];

                $payload = array_intersect_key($payload, $journalColumns);
                $importKey = array_intersect_key([
                    'company_name_short' => $companyNameShort,
                    'journal_breakdown' => $journalBreakdown,
                    'occurred_at' => $payload['occurred_at'] ?? null,
                    'management_number' => $payload['management_number'] ?? null,
                    'debit_account_title' => $payload['debit_account_title'] ?? null,
                    'debit_amount' => $payload['debit_amount'] ?? null,
                    'debit_item_name' => $payload['debit_item_name'] ?? null,
                    'debit_department_name' => $payload['debit_department_name'] ?? null,
                    'debit_counterparty' => $payload['debit_counterparty'] ?? null,
                    'credit_account_title' => $payload['credit_account_title'] ?? null,
                    'credit_amount' => $payload['credit_amount'] ?? null,
                    'credit_item_name' => $payload['credit_item_name'] ?? null,
                    'credit_department_name' => $payload['credit_department_name'] ?? null,
                    'credit_counterparty' => $payload['credit_counterparty'] ?? null,
                    'summary_text' => $payload['summary_text'] ?? null,
                ], $journalColumns);

                $matchQuery = DB::connection('sqlsrv')
                    ->table('dbo.mx_journal_entries');
                foreach ($importKey as $column => $value) {
                    $matchQuery->where($column, $value);
                }

                // updateOrInsert()は内部でも同じ条件のexists判定を行うため、
                // 件数集計に使うexists判定と合わせて二重に問い合わせていた。
                // ここで判定した結果をそのままupdate/insertの分岐に使い、1回にまとめる。
                if ((clone $matchQuery)->exists()) {
                    $matchQuery->update($payload);
                    $updatedCount++;
                } else {
                    DB::connection('sqlsrv')
                        ->table('dbo.mx_journal_entries')
                        ->insert(array_merge($importKey, $payload));
                    $importedCount++;
                }
            }
        });

        return redirect()->route('admin.work.journal_entries', [
            'date_from' => $data['date_from'],
            'date_to' => $data['date_to'],
            'company_name_short' => $companyNameShort,
        ])->with('statusMessage', 'CSV取込が完了しました。追加: ' . $importedCount . '件 / 更新: ' . $updatedCount . '件 / 対象外: ' . $skippedCount . '件' . ($invalidAmountCount > 0 ? ' / 金額が数値でないため未取込: ' . $invalidAmountCount . '件' : ''));
    }

    public function updateJournalEntry(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'journal_entry_id' => ['required', 'integer'],
            'journal_breakdown' => ['nullable', 'string', 'max:255'],
            'vault_name' => ['nullable', 'string', 'max:255'],
            'management_number' => ['nullable', 'string', 'max:255'],
            'occurred_at' => ['nullable', 'date'],
            'debit_account_title' => ['nullable', 'string', 'max:255'],
            'debit_counterparty' => ['nullable', 'string', 'max:255'],
            'debit_item_name' => ['nullable', 'string', 'max:255'],
            'debit_department_name' => ['nullable', 'string', 'max:255'],
            'debit_amount' => ['nullable', 'string', 'max:255'],
            'debit_memo_tag' => ['nullable', 'string', 'max:255'],
            'allocation_ratio' => ['nullable', 'string', 'max:255'],
            'credit_account_title' => ['nullable', 'string', 'max:255'],
            'credit_counterparty' => ['nullable', 'string', 'max:255'],
            'credit_item_name' => ['nullable', 'string', 'max:255'],
            'credit_department_name' => ['nullable', 'string', 'max:255'],
            'credit_amount' => ['nullable', 'string', 'max:255'],
            'credit_memo_tag' => ['nullable', 'string', 'max:255'],
            'deposit_name' => ['nullable', 'string', 'max:255'],
            'summary_text' => ['nullable', 'string'],
            'company_name_short' => ['nullable', 'string', 'max:255'],
        ]);

        $debitAmount = $this->parseEditableMoney($request->input('debit_amount'));
        $creditAmount = $this->parseEditableMoney($request->input('credit_amount'));
        if ($debitAmount === false || $creditAmount === false) {
            return redirect()->route('admin.work.journal_entries', $this->journalEntriesRedirectParams($request))
                ->with('errorMessage', '金額は数値で入力してください。');
        }

        $payload = [
            'journal_breakdown' => $this->nullableTrim($data['journal_breakdown'] ?? null),
            'vault_name' => $this->nullableTrim($data['vault_name'] ?? null),
            'management_number' => $this->nullableTrim($data['management_number'] ?? null),
            'occurred_at' => ($data['occurred_at'] ?? '') !== '' ? Carbon::parse((string) $data['occurred_at'])->format('Y-m-d') : null,
            'debit_account_title' => $this->nullableTrim($data['debit_account_title'] ?? null),
            'debit_counterparty' => $this->nullableTrim($data['debit_counterparty'] ?? null),
            'debit_item_name' => $this->nullableTrim($data['debit_item_name'] ?? null),
            'debit_department_name' => $this->nullableTrim($data['debit_department_name'] ?? null),
            'debit_amount' => $debitAmount,
            'debit_memo_tag' => $this->nullableTrim($data['debit_memo_tag'] ?? null),
            'allocation_ratio' => $this->nullableTrim($data['allocation_ratio'] ?? null),
            'credit_account_title' => $this->nullableTrim($data['credit_account_title'] ?? null),
            'credit_counterparty' => $this->nullableTrim($data['credit_counterparty'] ?? null),
            'credit_item_name' => $this->nullableTrim($data['credit_item_name'] ?? null),
            'credit_department_name' => $this->nullableTrim($data['credit_department_name'] ?? null),
            'credit_amount' => $creditAmount,
            'credit_memo_tag' => $this->nullableTrim($data['credit_memo_tag'] ?? null),
            'deposit_name' => $this->nullableTrim($data['deposit_name'] ?? null),
            'summary_text' => $this->nullableTrim($data['summary_text'] ?? null),
            'company_name_short' => $this->nullableTrim($data['company_name_short'] ?? null),
            'is_trial_balance_excluded' => $request->boolean('is_trial_balance_excluded') ? 1 : 0,
            'is_upload_unnecessary' => $request->boolean('is_upload_unnecessary') ? 1 : 0,
            'is_reward_excluded' => $request->boolean('is_reward_excluded') ? 1 : 0,
        ];

        if (($payload['occurred_at'] ?? null) !== null) {
            $payload['month_date'] = Carbon::parse((string) $payload['occurred_at'])->startOfMonth()->format('Y-m-d');
        }

        $columns = array_flip(Schema::connection('sqlsrv')->getColumnListing('mx_journal_entries'));
        if (isset($columns['updated_at'])) {
            $payload['updated_at'] = now();
        }
        $payload = array_intersect_key($payload, $columns);

        DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries')
            ->where('journal_entry_id', (int) $data['journal_entry_id'])
            ->update($payload);

        return redirect()->route('admin.work.journal_entries', $this->journalEntriesRedirectParams($request))
            ->with('statusMessage', '仕訳帳を保存しました。');
    }

    public function updateJournalEntryGroup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'common' => ['required', 'array'],
            'common.journal_breakdown' => ['nullable', 'string', 'max:255'],
            'common.vault_name' => ['nullable', 'string', 'max:255'],
            'common.management_number' => ['nullable', 'string', 'max:255'],
            'common.occurred_at' => ['nullable', 'date'],
            'common.counterparty' => ['nullable', 'string', 'max:255'],
            'common.summary_text' => ['nullable', 'string'],
            'common.company_name_short' => ['nullable', 'string', 'max:255'],
            'rows' => ['required', 'array'],
            'rows.*.debit_account_title' => ['nullable', 'string', 'max:255'],
            'rows.*.debit_item_name' => ['nullable', 'string', 'max:255'],
            'rows.*.debit_department_name' => ['nullable', 'string', 'max:255'],
            'rows.*.debit_amount' => ['nullable', 'string', 'max:255'],
            'rows.*.debit_memo_tag' => ['nullable', 'string', 'max:255'],
            'rows.*.allocation_ratio' => ['nullable', 'string', 'max:255'],
            'rows.*.credit_account_title' => ['nullable', 'string', 'max:255'],
            'rows.*.credit_item_name' => ['nullable', 'string', 'max:255'],
            'rows.*.credit_department_name' => ['nullable', 'string', 'max:255'],
            'rows.*.credit_amount' => ['nullable', 'string', 'max:255'],
            'rows.*.credit_memo_tag' => ['nullable', 'string', 'max:255'],
        ]);

        $common = $data['common'];
        $occurredAt = ($common['occurred_at'] ?? '') !== ''
            ? Carbon::parse((string) $common['occurred_at'])->format('Y-m-d')
            : null;
        $counterparty = $this->nullableTrim($common['counterparty'] ?? null);

        $commonPayload = [
            'journal_breakdown' => $this->nullableTrim($common['journal_breakdown'] ?? null),
            'vault_name' => $this->nullableTrim($common['vault_name'] ?? null),
            'management_number' => $this->nullableTrim($common['management_number'] ?? null),
            'occurred_at' => $occurredAt,
            'month_date' => $occurredAt !== null ? Carbon::parse($occurredAt)->startOfMonth()->format('Y-m-d') : null,
            'debit_counterparty' => $counterparty,
            'credit_counterparty' => $counterparty,
            'summary_text' => $this->nullableTrim($common['summary_text'] ?? null),
            'company_name_short' => $this->nullableTrim($common['company_name_short'] ?? null),
            'is_trial_balance_excluded' => $request->boolean('common.is_trial_balance_excluded') ? 1 : 0,
            'is_upload_unnecessary' => $request->boolean('common.is_upload_unnecessary') ? 1 : 0,
            'is_reward_excluded' => $request->boolean('common.is_reward_excluded') ? 1 : 0,
        ];

        $columns = array_flip(Schema::connection('sqlsrv')->getColumnListing('mx_journal_entries'));
        if (isset($columns['updated_at'])) {
            $commonPayload['updated_at'] = now();
        }
        $commonPayload = array_intersect_key($commonPayload, $columns);

        try {
            DB::connection('sqlsrv')->transaction(function () use ($data, $commonPayload, $columns): void {
                foreach ($data['rows'] as $journalEntryId => $row) {
                    $debitAmount = $this->parseEditableMoney($row['debit_amount'] ?? null);
                    $creditAmount = $this->parseEditableMoney($row['credit_amount'] ?? null);
                    if ($debitAmount === false || $creditAmount === false) {
                        throw new \InvalidArgumentException('amount');
                    }

                    $payload = array_merge($commonPayload, [
                        'debit_account_title' => $this->nullableTrim($row['debit_account_title'] ?? null),
                        'debit_item_name' => $this->nullableTrim($row['debit_item_name'] ?? null),
                        'debit_department_name' => $this->nullableTrim($row['debit_department_name'] ?? null),
                        'debit_amount' => $debitAmount,
                        'debit_memo_tag' => $this->nullableTrim($row['debit_memo_tag'] ?? null),
                        'allocation_ratio' => $this->nullableTrim($row['allocation_ratio'] ?? null),
                        'credit_account_title' => $this->nullableTrim($row['credit_account_title'] ?? null),
                        'credit_item_name' => $this->nullableTrim($row['credit_item_name'] ?? null),
                        'credit_department_name' => $this->nullableTrim($row['credit_department_name'] ?? null),
                        'credit_amount' => $creditAmount,
                        'credit_memo_tag' => $this->nullableTrim($row['credit_memo_tag'] ?? null),
                    ]);
                    $payload = array_intersect_key($payload, $columns);

                    DB::connection('sqlsrv')
                        ->table('dbo.mx_journal_entries')
                        ->where('journal_entry_id', (int) $journalEntryId)
                        ->update($payload);
                }
            });
        } catch (\InvalidArgumentException) {
            return redirect()->route('admin.work.journal_entries', $this->journalEntriesRedirectParams($request))
                ->with('errorMessage', '金額は数値で入力してください。');
        }

        return redirect()->route('admin.work.journal_entries', $this->journalEntriesRedirectParams($request))
            ->with('statusMessage', '仕訳帳を保存しました。');
    }

    public function deleteJournalEntry(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'journal_entry_id' => ['required', 'integer'],
        ]);

        DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries')
            ->where('journal_entry_id', (int) $data['journal_entry_id'])
            ->delete();

        return redirect()->route('admin.work.journal_entries', $this->journalEntriesRedirectParams($request))
            ->with('statusMessage', '仕訳帳を削除しました。');
    }

    /**
     * 行単位でstr_getcsv()していた旧実装は、取引内容等のセル内に改行を含む行が来ると
     * そこで1レコードが分断され、以降の列がすべてズレて取り込まれていた。fgetcsv()は
     * クォート内の改行をレコードの区切りと見なさないため、この問題が起きない。
     */
    private function readCsvAssocRows(string $path): array
    {
        $contents = file_get_contents($path);
        if ($contents === false || $contents === '') {
            return [];
        }

        $contents = mb_convert_encoding($contents, 'UTF-8', 'SJIS-win,UTF-8');
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $contents);
        rewind($stream);

        $headers = null;
        $rows = [];
        while (($values = fgetcsv($stream)) !== false) {
            if ($headers === null) {
                $headers = array_map(fn(?string $header): string => trim((string) $header), $values);
                continue;
            }

            if ($values === [null] || $values === ['']) {
                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }
                $row[$header] = trim((string) ($values[$index] ?? ''));
            }
            $rows[] = $row;
        }
        fclose($stream);

        return $rows;
    }

    private function csvValue(array $row, string ...$keys): string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                return trim((string) $row[$key]);
            }
        }

        return '';
    }

    private function parseCsvDate(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse(str_replace('/', '-', $value))->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    // TODO(共通化保留): parseCsvMoney/parseAmountValue/parseEditableMoneyは金額を
    // 数値へ変換する処理が3箇所に分かれている。ただし呼び出し元ごとに戻り値の意味が
    // 微妙に違う（parseEditableMoneyだけ「空」nullと「不正値」falseを区別して
    // \InvalidArgumentExceptionの発火に使っている等）ため、機械的に1つへ統合すると
    // 挙動が変わる恐れがある。各呼び出し箇所の挙動差を洗い出してから安全に一本化すること。
    private function parseCsvMoney(string $value): ?float
    {
        $value = trim(str_replace([',', '￥', '\\'], '', $value));
        if ($value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function journalBreakdownValue(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return ctype_digit($value) ? str_pad($value, 7, '0', STR_PAD_LEFT) : $value;
    }

    private function depositNameFromCsv(array $row): ?string
    {
        if ($this->csvValue($row, '貸方勘定科目') !== '医療未収入金') {
            return null;
        }

        $value = str_replace(['　', '振込入金＊', '振込入金'], '', $this->csvValue($row, '取引内容'))
            . $this->csvValue($row, '借方備考')
            . $this->csvValue($row, '貸方備考');
        $value = mb_convert_kana(trim($value), 'as', 'UTF-8');

        return $value === '' ? null : $value;
    }

    private function fetchDepartmentSelectOptions(): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_departments')
            ->select(['store_short_name', 'store_category'])
            ->whereNull('closed_on')
            ->whereNotNull('store_short_name')
            ->where('store_short_name', '<>', '')
            ->whereNotNull('store_category')
            ->where('store_category', '<>', '')
            ->orderBy('store_category')
            ->get()
            ->map(function ($row): array {
                $value = trim((string) ($row->store_short_name ?? ''));
                $category = trim((string) ($row->store_category ?? ''));

                return [
                    'value' => $value,
                    'label' => $category,
                ];
            })
            ->filter(fn(array $row): bool => $row['value'] !== '')
            ->values()
            ->all();
    }
    private function fetchDistinctOptions(string $column, bool $formatMoney = false): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries')
            ->select($column)
            ->whereNotNull($column)
            ->groupBy($column)
            ->orderBy($column)
            ->pluck($column)
            ->map(function ($value) use ($formatMoney): string {
                if ($formatMoney && is_numeric($value)) {
                    return number_format((float) $value, 0, '.', '');
                }

                return trim((string) $value);
            })
            ->filter(fn(string $value): bool => $value !== '')
            ->values()
            ->all();
    }

    private function fetchDistinctUnionOptions(string $leftColumn, string $rightColumn, bool $formatMoney = false): array
    {
        $mergedValues = array_values(array_unique(array_merge(
            $this->fetchDistinctOptions($leftColumn, $formatMoney),
            $this->fetchDistinctOptions($rightColumn, $formatMoney),
        )));

        natcasesort($mergedValues);

        return array_values($mergedValues);
    }

    private function parseAmountValue(string $value): ?float
    {
        if ($value === '') {
            return null;
        }

        $normalizedValue = str_replace(',', '', $value);

        if (!is_numeric($normalizedValue)) {
            return null;
        }

        return (float) $normalizedValue;
    }

    private function parseEditableMoney(mixed $value): float|int|null|false
    {
        $value = trim(str_replace(',', '', (string) ($value ?? '')));
        if ($value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return false;
        }

        return str_contains($value, '.') ? (float) $value : (int) $value;
    }

    private function nullableTrim(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function journalEntriesRedirectParams(Request $request): array
    {
        return array_filter([
            'date_from' => $request->input('filter_date_from'),
            'date_to' => $request->input('filter_date_to'),
            'company_name_short' => $request->input('filter_company_name_short'),
            'counterparty' => $request->input('filter_counterparty'),
            'amount' => $request->input('filter_amount'),
            'summary_text' => $request->input('filter_summary_text'),
            'account_title' => $request->input('filter_account_title'),
            'item_name' => $request->input('filter_item_name'),
            'department_name' => $request->input('filter_department_name'),
            'management_number' => $request->input('filter_management_number'),
            'journal_breakdown' => $request->input('filter_journal_breakdown'),
        ], fn($value): bool => $value !== null && $value !== '');
    }

    private function rewardCalculationRow(object $row, Carbon $from, Carbon $to, string $payrollLabel, string $bonusLabel): array
    {
        $debitAmount = (float) ($row->debit_amount ?? 0);
        $creditAmount = (float) ($row->credit_amount ?? 0);
        $summaryText = trim((string) ($row->summary_text ?? ''));
        $expenseAmount = str_contains($summaryText, '減免') ? $creditAmount : $debitAmount;
        $allocationRatio = $this->parseRatioValue($row->allocation_ratio ?? null);
        $managementNumber = trim((string) ($row->management_number ?? ''));
        $expenseSortName = trim((string) ($row->expense_sort_name ?? ''));
        $debitAccountTitle = trim((string) ($row->debit_account_title ?? ''));
        $isPayrollManagementNumber = str_contains($managementNumber, '月給与')
            || str_contains($managementNumber, '月賞与');
        $isLaborCost = $isPayrollManagementNumber
            || $expenseSortName === '人件費'
            || in_array($debitAccountTitle, ['給料手当', '役員報酬', '賞与', '業務委託料', '法定福利費'], true);
        $hasTargetPayrollLabel = str_contains($managementNumber, $payrollLabel)
            || str_contains($managementNumber, $bonusLabel);
        $occurredAt = $this->toCarbon($row->occurred_at ?? null);
        $isCurrentMonthOccurred = $occurredAt !== null && $occurredAt->betweenIncluded($from, $to);

        return [
            'journal_entry_id' => (int) ($row->journal_entry_id ?? 0),
            'occurred_at' => $this->formatDateValue($row->occurred_at, 'Y/m/d'),
            'journal_breakdown' => trim((string) ($row->journal_breakdown ?? '')),
            'management_number' => $managementNumber,
            'item' => $this->rewardItemName($row),
            'item_category' => $this->rewardItemCategory($row),
            'expense_sort_name' => $expenseSortName,
            'debit_account_title' => $debitAccountTitle,
            'debit_item_name' => trim((string) ($row->debit_item_name ?? '')),
            'debit_department_name' => trim((string) ($row->debit_department_name ?? '')),
            'debit_counterparty' => trim((string) ($row->debit_counterparty ?? '')),
            'credit_account_title' => trim((string) ($row->credit_account_title ?? '')),
            'credit_item_name' => trim((string) ($row->credit_item_name ?? '')),
            'credit_department_name' => trim((string) ($row->credit_department_name ?? '')),
            'credit_counterparty' => trim((string) ($row->credit_counterparty ?? '')),
            'summary_text' => $summaryText,
            'company_name_short' => trim((string) ($row->company_name_short ?? '')),
            'expense_amount' => $expenseAmount,
            'allocation_ratio' => trim((string) ($row->allocation_ratio ?? '')),
            'allocation_ratio_number' => $allocationRatio,
            'allocated_amount' => $allocationRatio > 0 ? $expenseAmount / $allocationRatio : 0.0,
            'is_labor_cost' => $isLaborCost,
            'is_target_reward_period' => $isLaborCost ? $hasTargetPayrollLabel : $isCurrentMonthOccurred,
        ];
    }

    private function rewardExpenseGroups(array $rows, string $amountKey): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $groupName = trim((string) ($row['expense_sort_name'] ?? ''));
            if ($groupName === '') {
                $groupName = '未分類';
            }
            if (!isset($groups[$groupName])) {
                $groups[$groupName] = [
                    'name' => $groupName,
                    'rows' => [],
                    'total' => 0.0,
                    'count' => 0,
                ];
            }

            $amount = (float) ($row[$amountKey] ?? 0);
            $groups[$groupName]['rows'][] = $row;
            $groups[$groupName]['total'] += $amount;
            $groups[$groupName]['count']++;
        }

        foreach ($groups as $groupName => $group) {
            $groups[$groupName]['summary_rows'] = $this->rewardSummaryRows($group['rows'], $amountKey);
        }

        return array_values($groups);
    }

    private function rewardSummaryRows(array $rows, string $amountKey): array
    {
        $summaryRows = [];
        foreach ($rows as $row) {
            $itemCategory = trim((string) ($row['item_category'] ?? ''));
            $item = trim((string) ($row['item'] ?? ''));
            $allocationRatio = trim((string) ($row['allocation_ratio'] ?? ''));
            $occurredAt = trim((string) ($row['occurred_at'] ?? ''));
            $key = implode('|', [$occurredAt, $itemCategory, $item, $allocationRatio]);

            if (!isset($summaryRows[$key])) {
                $summaryRows[$key] = [
                    'occurred_at' => trim((string) ($row['occurred_at'] ?? '')),
                    'item_category' => $itemCategory,
                    'item' => $item,
                    'allocation_ratio' => $allocationRatio,
                    'expense_amount' => 0.0,
                    'allocated_amount' => 0.0,
                    'count' => 0,
                ];
            }

            $summaryRows[$key]['expense_amount'] += (float) ($row['expense_amount'] ?? 0);
            $summaryRows[$key]['allocated_amount'] += (float) ($row['allocated_amount'] ?? 0);
            $summaryRows[$key]['count']++;
        }

        return array_values($summaryRows);
    }

    private function rewardCompanyGroups(array $rows, string $amountKey): array
    {
        $companies = [];
        foreach ($rows as $row) {
            $companyName = trim((string) ($row['company_name_short'] ?? ''));
            if ($companyName === '') {
                $companyName = '会社未設定';
            }

            if (!isset($companies[$companyName])) {
                $companies[$companyName] = [
                    'company_name' => $companyName,
                    'groups' => [],
                    'total' => 0.0,
                    'count' => 0,
                ];
            }

            $amount = (float) ($row[$amountKey] ?? 0);
            $companies[$companyName]['groups'][] = $row;
            $companies[$companyName]['total'] += $amount;
            $companies[$companyName]['count']++;
        }

        foreach ($companies as $companyName => $company) {
            $companies[$companyName]['groups'] = $this->rewardExpenseGroups($company['groups'], $amountKey);
        }

        return array_values($companies);
    }

    private function rewardItemName(object $row): string
    {
        $accountTitle = trim((string) ($row->debit_account_title ?? ''));
        $itemName = trim((string) ($row->debit_item_name ?? ''));
        $counterparty = trim((string) ($row->debit_counterparty ?? ''));

        if ($accountTitle === '旅費交通費' && $itemName === '通勤手当') {
            return '通勤手当';
        }

        if (in_array($accountTitle, ['給料手当', '役員報酬', '賞与', '業務委託料', '旅費交通費'], true)) {
            $summary = trim((string) ($row->summary_text ?? ''));
            $summary = str_replace(
                ['総合振込', 'インタ－ネツト', '　', ' ', 'さくら店舗', '健康保険料（預り分）', 'プレッジ', '雇用保険料（預り分）', 'トータルケア'],
                '',
                $summary
            );

            return trim($summary . $counterparty);
        }

        return $counterparty;
    }

    private function rewardItemCategory(object $row): string
    {
        $counterparty = trim((string) ($row->debit_counterparty ?? ''));
        $managementNumber = trim((string) ($row->management_number ?? ''));
        $majorCategory = trim((string) ($row->major_category_name ?? ''));
        $middleCategory = trim((string) ($row->middle_category_name ?? ''));
        $debitItem = trim((string) ($row->debit_item_name ?? ''));
        $debitAccount = trim((string) ($row->debit_account_title ?? ''));

        if ($counterparty === '手技療法専門通販') {
            return '医療消耗品費';
        }
        if (str_contains($managementNumber, '月店舗経費')) {
            return '除外';
        }
        if ($majorCategory === '資産' && $middleCategory !== '固定資産') {
            return trim((string) ($row->credit_account_title ?? ''));
        }

        return $debitItem !== '' ? $debitItem : $debitAccount;
    }

    private function parseRatioValue(mixed $value): float
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return 0.0;
        }

        $text = str_replace(',', '', $text);
        return is_numeric($text) ? (float) $text : 0.0;
    }

    private function containsSakura(string $value): bool
    {
        return str_contains($value, 'さくら');
    }

    private function isSakuraStoreSalesRow(array $row): bool
    {
        $departmentName = trim((string) ($row['department_name'] ?? ''));
        $storeName = trim((string) ($row['store_name'] ?? ''));

        return str_contains($departmentName, 'さくら店舗')
            || str_contains($departmentName, 'T_さくら 店舗')
            || str_contains($storeName, 'さくら店舗')
            || str_contains($storeName, 'T_さくら 店舗');
    }

    private function toCarbon(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatDateValue(mixed $value, string $format = 'Y/m/d'): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return \Illuminate\Support\Carbon::parse((string) $value)->format($format);
        } catch (\Throwable) {
            return trim((string) $value);
        }
    }

    private function formatMoneyValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (!is_numeric($value)) {
            return trim((string) $value);
        }

        return number_format((float) $value);
    }
}

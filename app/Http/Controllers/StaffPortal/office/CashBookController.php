<?php

namespace App\Http\Controllers\StaffPortal\office;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CashBookController extends Controller
{
    use HandlesStaffPortalContext;

    private const MONTHLY_CLOSING_AUTHORITY = '経理';

    public function index(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        $staffRow = $this->staffPortalStaffRow($staffId);
        $is_admin = $this->isAdmin($staffRow);
        $target_month = trim((string) $request->query('target_month', now()->format('Y-m')));
        $vault_name = trim((string) $request->query('vault_name', ''));

        try {
            $targetMonthStart = Carbon::createFromFormat('Y-m-d', $target_month . '-01')->startOfMonth();
        } catch (\Throwable) {
            $target_month = now()->format('Y-m');
            $targetMonthStart = Carbon::createFromFormat('Y-m-d', $target_month . '-01')->startOfMonth();
        }
        $targetMonthEnd = $targetMonthStart->copy()->endOfMonth();
        $monthlyClosingRow = $this->cashMonthlyClosingRow($targetMonthEnd);
        $cashBookMonthlyClosed = $monthlyClosingRow !== null;

        $journalColumns = [
            'journal_entry_id',
            'occurred_at',
            'month_date',
            'management_number',
            'journal_breakdown',
            'vault_name',
            'deposit_amount',
            'withdrawal_amount',
            'debit_account_title',
            'debit_amount',
            'debit_department_name',
            'debit_memo_tag',
            'credit_account_title',
            'credit_amount',
            'credit_department_name',
            'credit_memo_tag',
            'description',
            'is_confirmation_checked',
            'company_name_short',
            'management_number',
            'management_note',
            'accounting_note',
            'is_accounting_checked',
        ];

        $journalTableColumns = array_map(
            'strtolower',
            Schema::connection('sqlsrv')->getColumnListing('mx_journal_entries')
        );

        $existingJournalColumns = array_values(array_unique(array_filter(
            $journalColumns,
            static fn(string $column): bool => in_array(strtolower($column), $journalTableColumns, true)
        )));

        $selectColumns = array_map(
            static fn(string $column): string => 'entry.' . $column,
            $existingJournalColumns
        );

        $vaultOptions = DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries as entry')
            ->select('entry.vault_name')
            ->whereNotNull('entry.vault_name')
            ->where('entry.vault_name', 'like', '%金庫')
            ->groupBy('entry.vault_name')
            ->orderBy('entry.vault_name')
            ->get()
            ->map(fn($row): string => trim((string) ($row->vault_name ?? '')))
            ->filter()
            ->values()
            ->all();

        $baseRowsQuery = DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries as entry')
            ->where(function ($query): void {
                $this->applyCashBookConditions($query);
            });

        $cashBookRows = [];
        $cashBookSummary = [
            'previous_balance' => '0',
            'deposit_amount_total' => '0',
            'withdrawal_amount_total' => '0',
            'current_balance' => '0',
        ];

        if ($vault_name !== '') {
            $baseRowsQuery->where('entry.vault_name', $vault_name);

            $cashBookRows = (clone $baseRowsQuery)
                ->select($selectColumns)
                ->whereDate('entry.month_date', '>=', $targetMonthStart->format('Y-m-d'))
                ->whereDate('entry.month_date', '<=', $targetMonthEnd->format('Y-m-d'))
                ->orderBy('entry.occurred_at')
                ->orderBy('entry.journal_entry_id')
                ->get()
                ->map(fn($row): array => [
                    'occurred_at' => $this->formatDateValue($row->occurred_at, 'Y/m/d'),
                    'occurred_at_raw' => $this->formatDateValue($row->occurred_at, 'Y-m-d'),
                    'journal_entry_id' => (int) ($row->journal_entry_id ?? 0),
                    'month_date' => $this->formatDateValue($row->month_date, 'Y/m/d'),
                    'management_number' => trim((string) ($row->management_number ?? '')),
                    'journal_breakdown' => trim((string) ($row->journal_breakdown ?? '')),
                    'vault_name' => trim((string) ($row->vault_name ?? '')),
                    'deposit_amount' => $this->formatMoneyValue($row->deposit_amount),
                    'withdrawal_amount' => $this->formatMoneyValue($row->withdrawal_amount),
                    'debit_account_title' => trim((string) ($row->debit_account_title ?? '')),
                    'debit_amount' => $this->formatMoneyValue($row->debit_amount),
                    'debit_department_name' => trim((string) ($row->debit_department_name ?? '')),
                    'debit_memo_tag' => trim((string) ($row->debit_memo_tag ?? '')),
                    'credit_account_title' => trim((string) ($row->credit_account_title ?? '')),
                    'credit_amount' => $this->formatMoneyValue($row->credit_amount),
                    'credit_department_name' => trim((string) ($row->credit_department_name ?? '')),
                    'credit_memo_tag' => trim((string) ($row->credit_memo_tag ?? '')),
                    'description' => trim((string) ($row->description ?? '')),
                    'is_confirmation_checked' => (bool) ($row->is_confirmation_checked ?? false) ? '1' : '',
                    'company_name_short' => trim((string) ($row->company_name_short ?? '')),
                    'management_note' => trim((string) ($row->management_note ?? '')),
                    'accounting_note' => trim((string) ($row->accounting_note ?? '')),
                    'is_accounting_checked' => (bool) ($row->is_accounting_checked ?? false) ? '1' : '',
                ])
                ->all();

            $previousSummary = DB::connection('sqlsrv')
                ->table('dbo.mx_journal_entries as entry')
                ->where('entry.vault_name', $vault_name)
                ->where('entry.debit_account_title', '小口現金')
                ->where('entry.credit_account_title', '前月繰越')
                ->whereDate('entry.occurred_at', '>=', $targetMonthStart->format('Y-m-d'))
                ->whereDate('entry.occurred_at', '<=', $targetMonthEnd->format('Y-m-d'))
                ->selectRaw('COALESCE(MAX(COALESCE(entry.debit_amount, 0)), 0) as previous_balance')
                ->first();

            $currentSummary = (clone $baseRowsQuery)
                ->whereDate('entry.month_date', '>=', $targetMonthStart->format('Y-m-d'))
                ->whereDate('entry.month_date', '<=', $targetMonthEnd->format('Y-m-d'))
                ->selectRaw('COALESCE(SUM(COALESCE(entry.deposit_amount, 0)), 0) as deposit_amount_total')
                ->selectRaw('COALESCE(SUM(COALESCE(entry.withdrawal_amount, 0)), 0) as withdrawal_amount_total')
                ->first();

            $previousBalance = (float) ($previousSummary->previous_balance ?? 0);
            $depositAmountTotal = (float) ($currentSummary->deposit_amount_total ?? 0);
            $withdrawalAmountTotal = (float) ($currentSummary->withdrawal_amount_total ?? 0);
            $currentBalance = $previousBalance + $depositAmountTotal - $withdrawalAmountTotal;

            $cashBookSummary = [
                'previous_balance' => $this->formatMoneyValue($previousBalance),
                'deposit_amount_total' => $this->formatMoneyValue($depositAmountTotal),
                'withdrawal_amount_total' => $this->formatMoneyValue($withdrawalAmountTotal),
                'current_balance' => $this->formatMoneyValue($currentBalance),
            ];
        }

        return view('staff_portal.office.office_menu.cash_book.index', $this->commonViewData($request, [
            'cashBookRows' => $cashBookRows,
            'vaultOptions' => $vaultOptions,
            'target_month' => $target_month,
            'vault_name' => $vault_name,
            'cashBookSummary' => $cashBookSummary,
            'cashBookMonthlyClosed' => $cashBookMonthlyClosed,
            'cashMonthlyClosingProcessedAt' => $this->formatDateValue($monthlyClosingRow?->processed_at, 'Y/m/d'),
            'is_admin' => $is_admin,
        ]));
    }

    private function cashMonthlyClosingRow(Carbon $targetMonth): ?object
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_monthly_closings')
            ->whereDate('closing_month', $targetMonth->format('Y-m-d'))
            ->where('authority', self::MONTHLY_CLOSING_AUTHORITY)
            ->first();
    }

    private function cashBookMonthEndFromValue(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if ($value instanceof \DateTimeInterface) {
                return Carbon::instance($value)->endOfMonth();
            }

            return Carbon::parse((string) $value)->endOfMonth();
        } catch (\Throwable) {
            return null;
        }
    }

    private function cashBookMonthEndFromRequest(?string $occurredAt, ?string $targetMonth): ?Carbon
    {
        if ($occurredAt !== null && $occurredAt !== '') {
            return $this->cashBookMonthEndFromValue($occurredAt);
        }

        if ($targetMonth !== null && $targetMonth !== '') {
            return Carbon::createFromFormat('Y-m-d', $targetMonth . '-01')->endOfMonth();
        }

        return null;
    }

    private function cashBookMonthlyClosedRedirect(array $data, string $message): RedirectResponse
    {
        return redirect()
            ->route('office.office_menu.cash_book', [
                'target_month' => trim((string) ($data['target_month'] ?? '')),
                'vault_name' => trim((string) ($data['vault_name'] ?? '')),
            ])
            ->with('errorMessage', $message);
    }

    // 現金出納帳の月次処理で翌月繰越仕訳を登録する
    public function unlockMonthly(Request $request): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);

        $staffRow = $this->staffPortalStaffRow($staffId);
        if (!$this->isAdmin($staffRow)) {
            abort(403);
        }

        $data = $request->validate([
            'target_month' => ['required', 'date_format:Y-m'],
            'vault_name' => ['nullable', 'string', 'max:255'],
        ]);

        $targetMonthStart = Carbon::createFromFormat('Y-m-d', (string) $data['target_month'] . '-01')->startOfMonth();
        $targetMonthEnd = $targetMonthStart->copy()->endOfMonth();
        $nextMonthStart = $targetMonthStart->copy()->addMonthNoOverflow()->startOfMonth();
        $vaultName = trim((string) ($data['vault_name'] ?? ''));

        // closeMonthly()は解除対象月の月次確定と同時に、金庫ごとの「翌月繰越」仕訳
        // （journal_breakdown='繰越'、month_date=翌月1日）も作る。ここが消し漏れていると、
        // 解除→残高が変わる修正→再度月次処理、をしても closeMonthly() の$alreadyExistsチェックが
        // 古い繰越仕訳を「もうある」と誤認して何もしないため、残高が古いまま固定されてしまう
        // （2026-08-20、ユーザー指摘「小口の場合は残高も作り直しとかになるけどちゃんとできてんのかな」
        // で発覚）。月次確定行の削除と必ずセットで、翌月繰越仕訳も削除する。
        $deletedJournalCount = 0;
        $deleted = 0;
        DB::connection('sqlsrv')->transaction(function () use ($nextMonthStart, $targetMonthEnd, &$deletedJournalCount, &$deleted): void {
            $deletedJournalCount = DB::connection('sqlsrv')
                ->table('dbo.mx_journal_entries')
                ->where('journal_breakdown', '繰越')
                ->where('debit_account_title', '小口現金')
                ->where('credit_account_title', '前月繰越')
                ->whereDate('occurred_at', $nextMonthStart->format('Y-m-d'))
                ->delete();

            $deleted = DB::connection('sqlsrv')
                ->table('dbo.mx_monthly_closings')
                ->whereDate('closing_month', $targetMonthEnd->format('Y-m-d'))
                ->where('authority', self::MONTHLY_CLOSING_AUTHORITY)
                ->delete();
        });

        $message = $deleted > 0 ? '小口月次確定を解除しました。' : '解除対象の小口月次確定がありませんでした。';
        if ($deletedJournalCount > 0) {
            $message .= ' 翌月繰越仕訳を' . $deletedJournalCount . '件削除しました。';
        }

        return redirect()
            ->route('office.office_menu.cash_book', [
                'target_month' => $data['target_month'],
                'vault_name' => $vaultName,
            ])
            ->with('statusMessage', $message);
    }

    public function closeMonthly(Request $request): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);

        $data = $request->validate([
            'target_month' => ['required', 'date_format:Y-m'],
            'vault_name' => ['required', 'string', 'max:255'],
        ]);

        $targetMonthStart = Carbon::createFromFormat('Y-m-d', (string) $data['target_month'] . '-01')->startOfMonth();
        $targetMonthEnd = $targetMonthStart->copy()->endOfMonth();
        $nextMonthStart = $targetMonthStart->copy()->addMonthNoOverflow()->startOfMonth();
        $vaultName = trim((string) $data['vault_name']);

        if ($this->cashMonthlyClosingRow($targetMonthEnd) !== null) {
            return redirect()
                ->route('office.office_menu.cash_book', [
                    'target_month' => $data['target_month'],
                    'vault_name' => $vaultName,
                ])
                ->with('statusMessage', '月次処理は既に完了しています。');
        }

        $vaultNames = DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries as entry')
            ->select('entry.vault_name')
            ->whereNotNull('entry.vault_name')
            ->where(function ($query): void {
                $this->applyCashBookConditions($query);
            })
            ->whereDate('entry.month_date', '>=', $targetMonthStart->format('Y-m-d'))
            ->whereDate('entry.month_date', '<=', $targetMonthEnd->format('Y-m-d'))
            ->groupBy('entry.vault_name')
            ->orderBy('entry.vault_name')
            ->get()
            ->map(fn($row): string => trim((string) ($row->vault_name ?? '')))
            ->filter()
            ->values()
            ->all();

        if ($vaultNames === []) {
            return redirect()
                ->route('office.office_menu.cash_book', [
                    'target_month' => $data['target_month'],
                    'vault_name' => $vaultName,
                ])
                ->with('errorMessage', '月次処理対象の金庫データがありません。');
        }

        DB::connection('sqlsrv')->transaction(function () use ($vaultNames, $targetMonthStart, $targetMonthEnd, $nextMonthStart): void {
            foreach ($vaultNames as $targetVaultName) {
                $alreadyExists = DB::connection('sqlsrv')
                    ->table('dbo.mx_journal_entries')
                    ->where('vault_name', $targetVaultName)
                    ->where('debit_account_title', '小口現金')
                    ->where('credit_account_title', '前月繰越')
                    ->whereDate('occurred_at', $nextMonthStart->format('Y-m-d'))
                    ->exists();

                if ($alreadyExists) {
                    continue;
                }

                $currentBalance = $this->calculateCashBookCurrentBalance($targetVaultName, $targetMonthStart, $targetMonthEnd);

                DB::connection('sqlsrv')
                    ->table('dbo.mx_journal_entries')
                    ->insert([
                        'journal_breakdown' => '繰越',
                        'occurred_at' => $nextMonthStart->format('Y-m-d'),
                        'month_date' => $nextMonthStart->format('Y-m-d'),
                        'vault_name' => $targetVaultName,
                        'debit_account_title' => '小口現金',
                        'debit_amount' => $currentBalance,
                        'credit_account_title' => '前月繰越',
                        'company_name_short' => $this->resolveCashBookVaultDepartmentName($targetVaultName),
                    ]);
            }

            DB::connection('sqlsrv')
                ->table('dbo.mx_monthly_closings')
                ->insert([
                    'closing_month' => $targetMonthEnd->format('Y-m-d'),
                    'authority' => self::MONTHLY_CLOSING_AUTHORITY,
                    'processed_at' => now(),
                ]);
        });

        return redirect()
            ->route('office.office_menu.cash_book', [
                'target_month' => $data['target_month'],
                'vault_name' => $vaultName,
            ])
            ->with('statusMessage', '翌月繰越を登録し、月次処理が完了しました。');
    }

    private function calculateCashBookCurrentBalance(string $vaultName, Carbon $targetMonthStart, Carbon $targetMonthEnd): float
    {
        $summary = DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries as entry')
            ->where('entry.vault_name', $vaultName)
            ->where(function ($query): void {
                $this->applyCashBookConditions($query);
            })
            ->whereDate('entry.month_date', '>=', $targetMonthStart->format('Y-m-d'))
            ->whereDate('entry.month_date', '<=', $targetMonthEnd->format('Y-m-d'))
            ->selectRaw('COALESCE(SUM(COALESCE(entry.deposit_amount, 0)), 0) as deposit_amount_total')
            ->selectRaw('COALESCE(SUM(COALESCE(entry.withdrawal_amount, 0)), 0) as withdrawal_amount_total')
            ->first();

        $previousSummary = DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries as entry')
            ->where('entry.vault_name', $vaultName)
            ->where('entry.debit_account_title', '小口現金')
            ->where('entry.credit_account_title', '前月繰越')
            ->whereDate('entry.occurred_at', '>=', $targetMonthStart->format('Y-m-d'))
            ->whereDate('entry.occurred_at', '<=', $targetMonthEnd->format('Y-m-d'))
            ->selectRaw('COALESCE(MAX(COALESCE(entry.debit_amount, 0)), 0) as previous_balance')
            ->first();

        $previousBalance = (float) ($previousSummary->previous_balance ?? 0);
        $depositAmountTotal = (float) ($summary->deposit_amount_total ?? 0);
        $withdrawalAmountTotal = (float) ($summary->withdrawal_amount_total ?? 0);

        return $previousBalance + $depositAmountTotal - $withdrawalAmountTotal;
    }

    private function applyCashBookConditions($query): void
    {
        $query->where(function ($query): void {
            $query->where('entry.vault_name', 'like', '%金庫')
                ->where(function ($query): void {
                    $query->where('entry.debit_account_title', '<>', '前月繰越')
                        ->orWhere('entry.debit_account_title', '小口現金');
                })
                ->where(function ($query): void {
                    $query->where('entry.credit_account_title', '<>', '前月繰越')
                        ->orWhereNull('entry.credit_account_title');
                });
        })
            ->orWhere(function ($query): void {
                $query->where('entry.vault_name', 'like', '%金庫')
                    ->where('entry.credit_account_title', '小口現金');
            })
        ;
    }

    public function save(Request $request): RedirectResponse|JsonResponse
    {
        $staffId = $this->staffPortalStaffId($request);

        $data = $request->validate([
            'journal_entry_id' => ['nullable', 'integer'],
            'target_month' => ['nullable', 'date_format:Y-m'],
            'vault_name' => ['required', 'string', 'max:255'],
            'occurred_at' => ['nullable', 'date_format:Y-m-d'],
            'management_number' => ['nullable', 'string', 'max:255'],
            'deposit_amount' => ['nullable', 'string', 'max:255'],
            'debit_memo_tag' => ['nullable', 'string', 'max:255'],
            'withdrawal_amount' => ['nullable', 'string', 'max:255'],
            'credit_memo_tag' => ['nullable', 'string', 'max:255'],
        ]);

        $deposit_amount = $this->parseMoneyInput($data['deposit_amount'] ?? null);
        $withdrawal_amount = $this->parseMoneyInput($data['withdrawal_amount'] ?? null);

        $occurred_at = ($data['occurred_at'] ?? '') !== '' ? (string) $data['occurred_at'] : null;
        $vault_name = trim((string) ($data['vault_name'] ?? ''));
        $management_number = trim((string) ($data['management_number'] ?? ''));
        $journalEntryId = (int) ($data['journal_entry_id'] ?? 0);
        $submittedMonthEnd = $this->cashBookMonthEndFromRequest($occurred_at, $data['target_month'] ?? null);

        if ($submittedMonthEnd !== null && $this->cashMonthlyClosingRow($submittedMonthEnd) !== null) {
            return $this->cashBookMonthlyClosedRedirect($data, '月次処理済みの月は保存できません。');
        }

        if ($journalEntryId > 0) {
            $existingRow = DB::connection('sqlsrv')
                ->table('dbo.mx_journal_entries')
                ->where('journal_entry_id', $journalEntryId)
                ->select(['journal_entry_id', 'occurred_at', 'month_date'])
                ->first();

            if ($existingRow === null) {
                return $this->cashBookMonthlyClosedRedirect($data, '更新対象のデータが見つかりません。');
            }

            $existingMonthEnd = $this->cashBookMonthEndFromValue($existingRow->month_date)
                ?? $this->cashBookMonthEndFromValue($existingRow->occurred_at);

            if ($existingMonthEnd !== null && $this->cashMonthlyClosingRow($existingMonthEnd) !== null) {
                return $this->cashBookMonthlyClosedRedirect($data, '月次処理済みの月は変更できません。');
            }
        }

        $vault_department_name = $this->resolveCashBookVaultDepartmentName($vault_name);
        $journal_breakdown = $this->cashBookJournalBreakdown($vault_name, $management_number, $occurred_at, $deposit_amount);

        $payload = [
            'occurred_at' => $occurred_at,
            'month_date' => $occurred_at !== null
                ? Carbon::parse($occurred_at)->startOfMonth()->format('Y-m-d')
                : (($data['target_month'] ?? '') !== '' ? Carbon::createFromFormat('Y-m-d', (string) $data['target_month'] . '-01')->startOfMonth()->format('Y-m-d') : null),
            'journal_breakdown' => $journal_breakdown,
            'vault_name' => $vault_name,
            'company_name_short' => $vault_department_name,
            'management_number' => $management_number,
            'deposit_amount' => $deposit_amount,
            'debit_account_title' => '小口現金',
            'debit_amount' => $deposit_amount,
            'debit_department_name' => $vault_department_name,
            'debit_memo_tag' => trim((string) ($data['debit_memo_tag'] ?? '')),
            'withdrawal_amount' => $withdrawal_amount,
            'credit_amount' => $withdrawal_amount,
            'credit_memo_tag' => trim((string) ($data['credit_memo_tag'] ?? '')),
        ];

        if (($withdrawal_amount ?? 0.0) != 0.0) {
            $payload['credit_account_title'] = '小口現金';
            $payload['credit_department_name'] = $vault_department_name;
        }

        if ($journalEntryId > 0) {
            DB::connection('sqlsrv')
                ->table('dbo.mx_journal_entries')
                ->where('journal_entry_id', $journalEntryId)
                ->update($payload);
        } else {
            DB::connection('sqlsrv')
                ->table('dbo.mx_journal_entries')
                ->insert($payload);
        }

        return redirect()
            ->route('office.office_menu.cash_book', [
                'target_month' => trim((string) ($data['target_month'] ?? '')),
                'vault_name' => trim((string) ($data['vault_name'] ?? '')),
            ])
            ->with('statusMessage', '保存しました。');
    }

    private function cashBookJournalBreakdown(string $vault_name, string $management_number, ?string $occurred_at, ?float $deposit_amount): string
    {
        if ($occurred_at === null) {
            return $management_number;
        }

        $dateText = Carbon::parse($occurred_at)->format('Ymd');

        if (($deposit_amount ?? 0.0) != 0.0) {
            return $this->cashBookVaultOfficeCode($vault_name) . '入' . $dateText;
        }

        return $management_number . $dateText;
    }

    private function cashBookVaultOfficeCode(string $vault_name): string
    {
        return match ($vault_name) {
            'プレッジ金庫' => 'P',
            'トータル金庫' => 'T',
            default => '',
        };
    }

    private function resolveCashBookVaultDepartmentName(string $vault_name): string
    {
        if ($vault_name === '') {
            return '';
        }

        $row = DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries')
            ->where('vault_name', $vault_name)
            ->where(function ($query): void {
                $query->whereNotNull('company_name_short')
                    ->orWhereNotNull('debit_department_name')
                    ->orWhereNotNull('credit_department_name');
            })
            ->orderByDesc('journal_entry_id')
            ->select(['company_name_short', 'debit_department_name', 'credit_department_name'])
            ->first();

        return trim((string) (($row->company_name_short ?? '') !== ''
            ? $row->company_name_short
            : (($row->debit_department_name ?? '') !== '' ? $row->debit_department_name : ($row->credit_department_name ?? ''))));
    }

    public function delete(Request $request): RedirectResponse|JsonResponse
    {
        $staffId = $this->staffPortalStaffId($request);

        $data = $request->validate([
            'journal_entry_id' => ['required', 'integer'],
            'target_month' => ['nullable', 'date_format:Y-m'],
            'vault_name' => ['required', 'string', 'max:255'],
        ]);

        $existingRow = DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries')
            ->where('journal_entry_id', (int) $data['journal_entry_id'])
            ->select(['journal_entry_id', 'occurred_at', 'month_date'])
            ->first();

        if ($existingRow === null) {
            return $this->cashBookMonthlyClosedRedirect($data, '削除対象のデータが見つかりません。');
        }

        $existingMonthEnd = $this->cashBookMonthEndFromValue($existingRow->month_date)
            ?? $this->cashBookMonthEndFromValue($existingRow->occurred_at);

        if ($existingMonthEnd !== null && $this->cashMonthlyClosingRow($existingMonthEnd) !== null) {
            return $this->cashBookMonthlyClosedRedirect($data, '月次処理済みの月は削除できません。');
        }

        DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries')
            ->where('journal_entry_id', (int) $data['journal_entry_id'])
            ->delete();

        return redirect()
            ->route('office.office_menu.cash_book', [
                'target_month' => trim((string) ($data['target_month'] ?? '')),
                'vault_name' => trim((string) ($data['vault_name'] ?? '')),
            ])
            ->with('statusMessage', '削除しました。');
    }
}

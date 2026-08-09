<?php

namespace App\Http\Controllers\StaffPortal\office;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentConfirmationController extends Controller
{
    use HandlesStaffPortalContext;

    private const BLANK_DEPOSIT_NAME_LABEL = '（入金名称なし）';

    public function index(Request $request): RedirectResponse|View
    {
        $staffId = (string) $request->session()->get('staff_id', '');

        $targetMonth = $this->targetMonth($request);
        $selectedBankAccount = trim((string) $request->query('bank_account', ''));
        $paymentCheck = trim((string) $request->query('payment_check', 'all'));
        $selectedJournalEntryId = (int) $request->query('journal_entry_id', 0);
        $detailTab = trim((string) $request->query('detail_tab', 'unpaid'));
        $detailInsurerKeyword = trim((string) $request->query('detail_insurer_keyword', ''));
        $detailDepositName = trim((string) $request->query('detail_deposit_name', ''));
        if (!in_array($detailTab, ['all', 'zero_payment', 'settled', 'unpaid'], true)) {
            $detailTab = 'unpaid';
        }

        $targetMonthDate = $this->targetMonthStartDate($targetMonth);
        $targetMonthStart = $targetMonthDate->format('Y-m-d');
        $targetMonthNext = $targetMonthDate->copy()->addMonthNoOverflow()->format('Y-m-d');


        $bankAccountOptions = DB::connection('sqlsrv')
            ->table('dbo.mx_departments')
            ->select(['bank_account_selection'])
            ->whereNotNull('bank_account_selection')
            ->orderBy('bank_account_selection')
            ->get()
            ->map(fn($row): string => trim((string) ($row->bank_account_selection ?? '')))
            ->filter(fn(string $value): bool => $value !== '')
            ->unique()
            ->values()
            ->all();

        $rowsQuery = DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries as entry')
            ->leftJoin('dbo.mx_departments as department', 'entry.credit_department_name', '=', 'department.store_short_name')
            ->leftJoin('dbo.mx_stores as entry_store', 'department.official_store_no', '=', 'entry_store.store_code')
            ->select([
                'entry.journal_entry_id',
                'entry.journal_breakdown',
                'entry.month_date',
                'entry.occurred_at',
                'entry.deposit_name',
                'entry.debit_account_title',
                'entry.debit_item_name',
                'entry.debit_amount',
                'entry.received_amount',
                'entry.is_confirmation_checked',
                'entry.deposit_confirmation_note',
                'entry.company_name_short',
                'entry.journal_note',
                'department.store_category',
                'department.bank_account_selection',
                'entry_store.company_id',
            ])
            ->whereNull('entry.vault_name')
            ->where('entry.credit_account_title', '医療未収入金')
            ->where(function ($query): void {
                $query->where('entry.credit_item_name', '<>', '返戻')
                    ->orWhereNull('entry.credit_item_name');
            })
            ->where('entry.month_date', '>=', $targetMonthStart)
            ->where('entry.month_date', '<', $targetMonthNext);

        if ($selectedBankAccount !== '') {
            $rowsQuery->where('department.bank_account_selection', $selectedBankAccount);
        }

        if ($paymentCheck === 'checked') {
            $rowsQuery->whereRaw('(COALESCE(entry.debit_amount, 0) - COALESCE(entry.received_amount, 0)) = 0');
        } elseif ($paymentCheck === 'unchecked') {
            $rowsQuery->whereRaw('(COALESCE(entry.debit_amount, 0) - COALESCE(entry.received_amount, 0)) <> 0');
        }

        $paymentRows = $rowsQuery
            ->orderBy('entry.occurred_at')
            ->orderBy('entry.journal_entry_id')
            ->get()
            ->map(function ($row): array {
                $remainingAmount = ((float) ($row->debit_amount ?? 0)) - ((float) ($row->received_amount ?? 0));

                return [
                    'journal_entry_id' => (int) ($row->journal_entry_id ?? 0),
                    'journal_breakdown' => trim((string) ($row->journal_breakdown ?? '')),
                    'month_date' => $this->formatDateValue($row->month_date, 'Y/m'),
                    'occurred_at' => $this->formatDateValue($row->occurred_at, 'Y/m/d'),
                    'deposit_name' => trim((string) ($row->deposit_name ?? '')),
                    'debit_account_title' => trim((string) ($row->debit_account_title ?? '')),
                    'item_name' => trim((string) ($row->debit_item_name ?? '')),
                    'deposit_amount' => $this->formatMoneyValue($row->debit_amount),
                    'confirmed_amount' => $this->formatMoneyValue($row->received_amount),
                    'remaining_amount' => $remainingAmount == 0.0 ? '0' : $this->formatMoneyValue($remainingAmount),
                    'bank_account_selection' => trim((string) ($row->bank_account_selection ?? '')),
                    'company_id' => trim((string) ($row->company_id ?? '')),
                    'store_name' => trim((string) ($row->store_category ?? '')),
                    'company_name' => trim((string) ($row->company_name_short ?? '')),
                    'remarks' => trim((string) ($row->deposit_confirmation_note ?? '')),
                    'journal_note' => trim((string) ($row->journal_note ?? '')),
                    'is_confirmation_checked' => (bool) ($row->is_confirmation_checked ?? false),
                ];
            })
            ->values();

        $selectedPaymentRow = $paymentRows->firstWhere('journal_entry_id', $selectedJournalEntryId);
        $candidateReceiptRows = [];
        $detailInsurerOptions = [];
        $detailDepositNameOptions = [];

        if (!empty($selectedPaymentRow)) {
            $selectedPaymentKey = trim((string) ($selectedPaymentRow['journal_breakdown'] ?? ''));
            $detailDepositNameOptionsQuery = DB::connection('sqlsrv')
                ->table('dbo.mx_insurance_claim_details as detail')
                ->leftJoin('dbo.mx_departments as department', 'department.store_short_name', '=', 'detail.store_name')
                ->leftJoin('dbo.mx_stores as detail_store', 'department.official_store_no', '=', 'detail_store.store_code')
                ->select(['detail.deposit_name'])
                ->where('detail.insurer_number', '<>', '99999999')
                ->where(function ($query): void {
                    $query->whereNull('detail.payment_date_text')
                        ->orWhere('detail.payment_date_text', '');
                })
                ->where(function ($query): void {
                    $query->whereNull('detail.is_uncollectible')
                        ->orWhere('detail.is_uncollectible', false)
                        ->orWhere('detail.is_uncollectible', 0);
                });

            if (($selectedPaymentRow['company_id'] ?? '') !== '') {
                $detailDepositNameOptionsQuery->whereRaw(
                    'LTRIM(RTRIM(CAST(detail_store.company_id AS NVARCHAR(255)))) = ?',
                    [$selectedPaymentRow['company_id']]
                );
            }

            $detailInsurerOptions = (clone $detailDepositNameOptionsQuery)
                ->leftJoin('dbo.mx_insurers as insurer_option', 'detail.insurer_number', '=', 'insurer_option.insurer_number')
                ->select(['detail.insurer_number', 'insurer_option.insurer_name'])
                ->orderBy('insurer_option.insurer_name')
                ->orderBy('detail.insurer_number')
                ->get()
                ->map(function ($row): string {
                    $insurerName = trim((string) ($row->insurer_name ?? ''));
                    $insurerNumber = trim((string) ($row->insurer_number ?? ''));

                    return $insurerName !== '' ? $insurerName : $insurerNumber;
                })
                ->filter(fn(string $value): bool => $value !== '')
                ->unique()
                ->values()
                ->all();

            $detailDepositNameOptions = (clone $detailDepositNameOptionsQuery)
                ->whereNotNull('detail.deposit_name')
                ->where('detail.deposit_name', '<>', '')
                ->orderBy('detail.deposit_name')
                ->get()
                ->map(fn($row): string => trim((string) ($row->deposit_name ?? '')))
                ->filter(fn(string $value): bool => $value !== '')
                ->unique()
                ->values()
                ->all();

            $hasBlankDepositName = (clone $detailDepositNameOptionsQuery)
                ->where(function ($query): void {
                    $query->whereNull('detail.deposit_name')
                        ->orWhere('detail.deposit_name', '');
                })
                ->exists();
            if ($hasBlankDepositName) {
                array_unshift($detailDepositNameOptions, self::BLANK_DEPOSIT_NAME_LABEL);
            }

            $candidateRowsQuery = DB::connection('sqlsrv')
                ->table('dbo.mx_insurance_claim_details as detail')
                ->leftJoin('dbo.mx_insurers as insurer', 'detail.insurer_number', '=', 'insurer.insurer_number')
                ->leftJoin('dbo.mx_departments as department', 'department.store_short_name', '=', 'detail.store_name')
                ->leftJoin('dbo.mx_stores as detail_store', 'department.official_store_no', '=', 'detail_store.store_code')
                ->leftJoinSub($this->paymentEntryOccurredAtQuery(), 'payment_entry', function ($join): void {
                    $join->whereRaw(
                        "LTRIM(RTRIM(CAST(detail.payment_date_text AS NVARCHAR(255)))) = payment_entry.journal_breakdown_key"
                    );
                })
                ->select([
                    'detail.insurance_claim_detail_id',
                    'detail.treatment_month',
                    'detail.insurer_number',
                    'insurer.insurer_name',
                    'insurer.insurance_type',
                    'detail.deposit_name',
                    'detail.claim_amount',
                    'detail.adjustment_amount',
                    'detail.returned_amount',
                    'detail.is_high_cost_medical',
                    'detail.is_uncollectible',
                    'detail.payment_date_text',
                    'detail.payment_amount',
                    'detail.patient_name',
                    'detail.remarks',
                    'department.store_category',
                    'payment_entry.occurred_at as payment_entry_occurred_at',
                ])
                ->where('detail.insurer_number', '<>', '99999999');

            if ($detailInsurerKeyword !== '') {
                $candidateRowsQuery->where(function ($query) use ($detailInsurerKeyword): void {
                    $query->where('detail.insurer_number', 'like', '%' . $detailInsurerKeyword . '%')
                        ->orWhere('insurer.insurer_name', 'like', '%' . $detailInsurerKeyword . '%');
                });
            }

            if ($detailDepositName === self::BLANK_DEPOSIT_NAME_LABEL) {
                $candidateRowsQuery->where(function ($query): void {
                    $query->whereNull('detail.deposit_name')
                        ->orWhere('detail.deposit_name', '');
                });
            } elseif ($detailDepositName !== '') {
                $candidateRowsQuery->where('detail.deposit_name', 'like', '%' . $detailDepositName . '%');
            }

            if ($detailTab === 'zero_payment') {
                $candidateRowsQuery->whereRaw('COALESCE(detail.payment_amount, 0) = 0');
            } elseif ($detailTab === 'settled') {
                $candidateRowsQuery->whereRaw(
                    'LTRIM(RTRIM(CAST(detail.payment_date_text AS NVARCHAR(255)))) = ?',
                    [$selectedPaymentKey]
                );
                if (($selectedPaymentRow['company_id'] ?? '') !== '') {
                    $candidateRowsQuery->whereRaw(
                        'LTRIM(RTRIM(CAST(detail_store.company_id AS NVARCHAR(255)))) = ?',
                        [$selectedPaymentRow['company_id']]
                    );
                }
            } elseif ($detailTab === 'unpaid') {
                $candidateRowsQuery
                    ->where('detail.deposit_name', $selectedPaymentRow['deposit_name'])
                    ->where(function ($query): void {
                        $query->whereNull('detail.payment_date_text')
                            ->orWhere('detail.payment_date_text', '');
                    })
                    ->where(function ($query): void {
                        $query->whereNull('detail.is_uncollectible')
                            ->orWhere('detail.is_uncollectible', false)
                            ->orWhere('detail.is_uncollectible', 0);
                    });

                if (($selectedPaymentRow['debit_account_title'] ?? '') !== '') {
                    $candidateRowsQuery->where('department.bank_account_selection', $selectedPaymentRow['debit_account_title']);
                }
            }

            $candidateReceiptRows = $candidateRowsQuery
                ->orderBy('detail.treatment_month')
                ->orderBy('detail.insurer_number')
                ->orderBy('detail.insurance_claim_detail_id')
                ->get()
                ->map(fn($row): array => [
                    'insurance_claim_detail_id' => (int) ($row->insurance_claim_detail_id ?? 0),
                    'treatment_month' => $this->formatDateValue($row->treatment_month, 'Y/m/d'),
                    'insurer_number' => trim((string) ($row->insurer_number ?? '')),
                    'insurer_name' => trim((string) ($row->insurer_name ?? '')),
                    'insurance_type' => trim((string) ($row->insurance_type ?? '')),
                    'deposit_name' => trim((string) ($row->deposit_name ?? '')),
                    'claim_amount' => $this->formatMoneyValue($row->claim_amount),
                    'adjustment_amount' => $this->formatMoneyValue($row->adjustment_amount),
                    'returned_amount' => $this->formatMoneyValue($row->returned_amount),
                    'high_cost_amount' => (bool) ($row->is_high_cost_medical ?? false) ? '1' : '',
                    'unrecoverable_amount' => (bool) ($row->is_uncollectible ?? false) ? '1' : '',
                    'payment_date_text' => trim((string) ($row->payment_date_text ?? '')),
                    'payment_date_display' => $this->formatDateValue($row->payment_entry_occurred_at, 'Y/m/d'),
                    'payment_amount' => $this->formatMoneyValue($row->payment_amount),
                    'remaining_amount' => (function () use ($row) {
                        $remainingAmount = ((float) ($row->claim_amount ?? 0))
                            + ((float) ($row->adjustment_amount ?? 0))
                            + ((float) ($row->returned_amount ?? 0))
                            - ((float) ($row->payment_amount ?? 0));

                        return $remainingAmount == 0.0 ? '0' : $this->formatMoneyValue($remainingAmount);
                    })(),
                    'remaining_amount_raw' => (string) (
                        ((float) ($row->claim_amount ?? 0))
                        + ((float) ($row->adjustment_amount ?? 0))
                        + ((float) ($row->returned_amount ?? 0))
                        - ((float) ($row->payment_amount ?? 0))
                    ),
                    'store_name' => trim((string) ($row->store_category ?? '')),
                    'patient_name' => trim((string) ($row->patient_name ?? '')),
                    'is_uncollectible' => (bool) ($row->is_uncollectible ?? false),
                    'remarks' => trim((string) ($row->remarks ?? '')),
                ])
                ->all();
        }

        return view('staff_portal.office.receipt.payment_confirmation.index',  $this->commonViewData($request, [
            'targetMonth' => $targetMonth,
            'selectedBankAccount' => $selectedBankAccount,
            'paymentCheck' => $paymentCheck,
            'detailTab' => $detailTab,
            'detailInsurerKeyword' => $detailInsurerKeyword,
            'detailInsurerOptions' => $detailInsurerOptions,
            'detailDepositName' => $detailDepositName,
            'detailDepositNameOptions' => $detailDepositNameOptions,
            'bankAccountOptions' => $bankAccountOptions,
            'paymentRows' => $paymentRows->all(),
            'selectedPaymentRow' => $selectedPaymentRow,
            'candidateReceiptRows' => $candidateReceiptRows,
        ]));
    }

    public function save(Request $request): RedirectResponse|JsonResponse
    {
        $staffId = (string) $request->session()->get('staff_id', '');

        $data = $request->validate([
            'journal_entry_id' => ['nullable', 'integer'],
            'insurance_claim_detail_id' => ['nullable', 'integer'],
            'source_insurance_claim_detail_id' => ['nullable', 'integer'],
            'adjustment_amount' => ['nullable', 'string'],
            'returned_amount' => ['nullable', 'string'],
            'is_high_cost_medical' => ['nullable', 'boolean'],
            'is_uncollectible' => ['nullable', 'boolean'],
            // mx_insurance_claim_details.payment_date_textの実カラム長(20)に合わせる
            // （長い値だとバリデーションを通過した後に生のSQLエラーになっていた）。
            'payment_date_text' => ['nullable', 'string', 'max:20'],
            'payment_amount' => ['nullable', 'string'],
            'patient_name' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string', 'max:255'],
        ]);

        $detailId = (int) ($data['insurance_claim_detail_id'] ?? 0);
        $sourceDetailId = (int) ($data['source_insurance_claim_detail_id'] ?? 0);
        $journalEntryId = (int) ($data['journal_entry_id'] ?? 0);

        $payload = [
            'adjustment_amount' => $this->parseMoneyInput($data['adjustment_amount'] ?? null),
            'returned_amount' => $this->parseMoneyInput($data['returned_amount'] ?? null),
            'is_high_cost_medical' => $request->boolean('is_high_cost_medical'),
            'is_uncollectible' => $request->boolean('is_uncollectible'),
            'payment_date_text' => trim((string) ($data['payment_date_text'] ?? '')),
            'payment_amount' => $this->parseMoneyInput($data['payment_amount'] ?? null),
            'patient_name' => trim((string) ($data['patient_name'] ?? '')),
            'remarks' => trim((string) ($data['remarks'] ?? '')),
        ];

        if ($detailId > 0) {
            DB::connection('sqlsrv')
                ->table('dbo.mx_insurance_claim_details')
                ->where('insurance_claim_detail_id', $detailId)
                ->update($payload);
        } else {
            if ($sourceDetailId <= 0) {
                return response()->json([
                    'message' => '複製元が見つかりません。',
                ], 422);
            }

            $sourceRow = DB::connection('sqlsrv')
                ->table('dbo.mx_insurance_claim_details')
                ->select([
                    'treatment_month',
                    'insurer_number',
                    'store_name',
                    'deposit_name',
                    'claim_amount',
                    'patient_name',
                ])
                ->where('insurance_claim_detail_id', $sourceDetailId)
                ->first();

            if (!$sourceRow) {
                return response()->json([
                    'message' => '複製元が見つかりません。',
                ], 422);
            }

            // 複製元の患者名を引き継ぐ（$payloadのpatient_nameが空ならこちらを使う。
            // フォーム側で患者名を明示的に入力していればそちらを優先）。
            if (trim((string) ($payload['patient_name'] ?? '')) === '') {
                $payload['patient_name'] = trim((string) ($sourceRow->patient_name ?? ''));
            }

            $detailId = (int) DB::connection('sqlsrv')
                ->table('dbo.mx_insurance_claim_details')
                ->insertGetId(array_merge([
                    'treatment_month' => $sourceRow->treatment_month,
                    'insurer_number' => $sourceRow->insurer_number,
                    'store_name' => $sourceRow->store_name,
                    'deposit_name' => $sourceRow->deposit_name,
                    'claim_amount' => 0,
                ], $payload), 'insurance_claim_detail_id');
        }

        $savedRow = DB::connection('sqlsrv')
            ->table('dbo.mx_insurance_claim_details as detail')
            ->leftJoinSub($this->paymentEntryOccurredAtQuery(), 'payment_entry', function ($join): void {
                $join->whereRaw(
                    "LTRIM(RTRIM(CAST(detail.payment_date_text AS NVARCHAR(255)))) = payment_entry.journal_breakdown_key"
                );
            })
            ->select([
                'detail.claim_amount',
                'detail.adjustment_amount',
                'detail.returned_amount',
                'detail.is_high_cost_medical',
                'detail.is_uncollectible',
                'detail.payment_date_text',
                'detail.payment_amount',
                'detail.patient_name',
                'detail.remarks',
                'payment_entry.occurred_at as payment_entry_occurred_at',
            ])
            ->where('detail.insurance_claim_detail_id', $detailId)
            ->first();

        $remainingAmount = ((float) ($savedRow?->claim_amount ?? 0))
            + ((float) ($savedRow?->adjustment_amount ?? 0))
            + ((float) ($savedRow?->returned_amount ?? 0))
            - ((float) ($savedRow?->payment_amount ?? 0));
        $journalEntryAmounts = $this->syncJournalEntryReceivedAmount($journalEntryId);

        return response()->json([
            'journal_entry_id' => $journalEntryId,
            'insurance_claim_detail_id' => $detailId,
            'adjustment_amount' => $this->formatMoneyValue($savedRow?->adjustment_amount),
            'returned_amount' => $this->formatMoneyValue($savedRow?->returned_amount),
            'high_cost_amount' => (bool) ($savedRow?->is_high_cost_medical ?? false) ? '1' : '',
            'unrecoverable_amount' => (bool) ($savedRow?->is_uncollectible ?? false) ? '1' : '',
            'payment_date_text' => trim((string) ($savedRow?->payment_date_text ?? '')),
            'payment_date_display' => $this->formatDateValue($savedRow?->payment_entry_occurred_at, 'Y/m/d'),
            'payment_amount' => $this->formatMoneyValue($savedRow?->payment_amount),
            'remaining_amount' => $remainingAmount == 0.0 ? '0' : $this->formatMoneyValue($remainingAmount),
            'confirmed_amount' => $journalEntryAmounts['confirmed_amount'],
            'entry_remaining_amount' => $journalEntryAmounts['remaining_amount'],
            'patient_name' => trim((string) ($savedRow?->patient_name ?? '')),
            'remarks' => trim((string) ($savedRow?->remarks ?? '')),
            'is_high_cost_medical' => (bool) ($savedRow?->is_high_cost_medical ?? false),
            'is_uncollectible' => (bool) ($savedRow?->is_uncollectible ?? false),
        ]);
    }

    public function delete(Request $request): JsonResponse
    {
        $staffId = (string) $request->session()->get('staff_id', '');

        $data = $request->validate([
            'insurance_claim_detail_id' => ['required', 'integer'],
        ]);

        DB::connection('sqlsrv')
            ->table('dbo.mx_insurance_claim_details')
            ->where('insurance_claim_detail_id', (int) $data['insurance_claim_detail_id'])
            ->delete();

        return response()->json([
            'deleted' => true,
        ]);
    }

    private function syncJournalEntryReceivedAmount(int $journalEntryId): array
    {
        if ($journalEntryId <= 0) {
            return [
                'confirmed_amount' => '',
                'remaining_amount' => '',
            ];
        }

        $journalEntry = DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries as entry')
            ->leftJoin('dbo.mx_departments as department', 'entry.credit_department_name', '=', 'department.store_short_name')
            ->leftJoin('dbo.mx_stores as entry_store', 'department.official_store_no', '=', 'entry_store.store_code')
            ->select([
                'entry.journal_breakdown',
                'entry.debit_amount',
                'entry_store.company_id',
            ])
            ->where('journal_entry_id', $journalEntryId)
            ->first();

        if (!$journalEntry) {
            return [
                'confirmed_amount' => '',
                'remaining_amount' => '',
            ];
        }

        $confirmedAmount = $this->confirmedPaymentAmountForJournalBreakdown(
            $journalEntry->journal_breakdown ?? null,
            $journalEntry->company_id ?? null
        );
        $remainingAmount = ((float) ($journalEntry->debit_amount ?? 0)) - $confirmedAmount;

        DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries')
            ->where('journal_entry_id', $journalEntryId)
            ->update(['received_amount' => $confirmedAmount]);

        return [
            'confirmed_amount' => $this->formatMoneyValue($confirmedAmount),
            'remaining_amount' => $remainingAmount == 0.0 ? '0' : $this->formatMoneyValue($remainingAmount),
        ];
    }

    private function paymentEntryOccurredAtQuery()
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries')
            ->selectRaw('LTRIM(RTRIM(CAST(journal_breakdown AS NVARCHAR(255)))) as journal_breakdown_key')
            ->selectRaw('MAX(occurred_at) as occurred_at')
            ->whereNotNull('journal_breakdown')
            ->groupByRaw('LTRIM(RTRIM(CAST(journal_breakdown AS NVARCHAR(255))))');
    }

    private function confirmedPaymentAmountForJournalBreakdown(mixed $journalBreakdown, mixed $companyId): float
    {
        $journalBreakdown = trim((string) ($journalBreakdown ?? ''));
        $companyId = trim((string) ($companyId ?? ''));
        if ($journalBreakdown === '' || $companyId === '') {
            return 0.0;
        }

        return (float) DB::connection('sqlsrv')
            ->table('dbo.mx_insurance_claim_details as detail')
            ->leftJoin('dbo.mx_departments as department', 'department.store_short_name', '=', 'detail.store_name')
            ->leftJoin('dbo.mx_stores as detail_store', 'department.official_store_no', '=', 'detail_store.store_code')
            ->whereRaw(
                'LTRIM(RTRIM(CAST(detail.payment_date_text AS NVARCHAR(255)))) = ?',
                [$journalBreakdown]
            )
            ->whereRaw(
                'LTRIM(RTRIM(CAST(detail_store.company_id AS NVARCHAR(255)))) = ?',
                [$companyId]
            )
            ->sum(DB::raw('COALESCE(detail.payment_amount, 0)'));
    }
}

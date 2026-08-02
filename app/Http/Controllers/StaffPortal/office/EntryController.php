<?php

namespace App\Http\Controllers\StaffPortal\office;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EntryController extends Controller
{
    use HandlesStaffPortalContext;

    private const MONTHLY_CLOSING_AUTHORITY = '入金確認';


    public function index(Request $request): RedirectResponse|View
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        $staffRow = $this->staffPortalStaffRow($staffId);
        $is_admin = $this->isAdmin($staffRow);
        if ($staffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'ログインしてください');
        }

        $targetMonth = $this->targetMonth($request);
        $selectedStore = trim((string) $request->query('store_name', ''));
        $showUnpaidOnly = trim((string) $request->query('unpaid_only', '')) === '1';

        $targetMonthDate = Carbon::createFromFormat('Y-m-d', $targetMonth . '-01')->startOfDay();
        $targetMonthStart = $targetMonthDate->format('Y-m-d');
        $targetMonthNext = $targetMonthDate->copy()->addMonthNoOverflow()->format('Y-m-d');
        $targetMonthEnd = $targetMonthDate->copy()->endOfMonth()->startOfDay();
        $monthlyClosingRow = $this->receiptMonthlyClosingRow($targetMonthEnd);
        $isReceiptMonthlyClosed = $monthlyClosingRow !== null;

        $rowsQuery = DB::connection('sqlsrv')
            ->table('dbo.mx_insurance_claim_details as detail')
            ->leftJoin('dbo.mx_insurers as insurer', 'detail.insurer_number', '=', 'insurer.insurer_number')
            ->leftJoin('dbo.mx_departments as department', 'department.store_short_name', '=', 'detail.store_name')
            ->leftJoin('dbo.mx_staffs as staff', 'detail.staff_name', '=', 'staff.staff_id')
            ->select([
                'detail.insurance_claim_detail_id',
                'detail.treatment_month',
                'detail.insurer_number',
                'detail.deposit_name',
                'detail.summary_category',
                'detail.total_sheets',
                'detail.total_parts_count',
                'detail.returned_amount',
                'department.store_category',
                'detail.subject_name',
                'detail.remarks',
                'insurer.insurance_category',
                'insurer.insurance_type',
                'insurer.insurer_name',
                'detail.total_amount',
                'detail.copayment_amount',
                'detail.claim_amount',
                'detail.adjustment_amount',
                'detail.payment_date_text',
                'detail.payment_amount',
                'detail.store_name',
                'department.store_category',
            ])
            ->where('detail.insurer_number', '<>', '99999999')
            // ->whereDate('detail.treatment_month', '>=', $targetMonthStart)
            // ->whereDate('detail.treatment_month', '<', $targetMonthNext);
            ->where('detail.treatment_month', '>=', $targetMonthStart)
            ->where('detail.treatment_month', '<', $targetMonthNext);

        if ($selectedStore !== '') {
            $rowsQuery->where('department.store_category', $selectedStore);
        }

        $receiptRowCollection = $rowsQuery
            ->orderBy('detail.treatment_month')
            ->orderBy('detail.insurer_number')
            ->orderBy('detail.insurance_claim_detail_id')
            ->get();

        $receiptSummaryRows = collect([
            ['label' => '一般', 'filter' => static fn($row): bool => trim((string) ($row->insurance_type ?? '')) !== '後期高齢' && trim((string) ($row->insurance_type ?? '')) !== '医療助成'],
            ['label' => '後期高齢', 'filter' => static fn($row): bool => trim((string) ($row->insurance_type ?? '')) === '後期高齢'],
            ['label' => '医療助成', 'filter' => static fn($row): bool => trim((string) ($row->insurance_type ?? '')) === '医療助成'],
        ])->map(function (array $summaryRow) use ($receiptRowCollection): array {
            $filtered = $receiptRowCollection->filter($summaryRow['filter']);

            $claimAmount = (float) $filtered->sum(fn($row) => (float) ($row->claim_amount ?? 0));
            $adjustmentAmount = (float) $filtered->sum(fn($row) => (float) ($row->adjustment_amount ?? 0));
            $returnedAmount = (float) $filtered->sum(fn($row) => (float) ($row->returned_amount ?? 0));

            return [
                'row_count' => $filtered->count() . '件',
                'label' => $summaryRow['label'],
                'total_sheets' => (string) ((int) $filtered->sum(fn($row) => (int) ($row->total_sheets ?? 0))),
                'total_parts_count' => (string) ((int) $filtered->sum(fn($row) => (int) ($row->total_parts_count ?? 0))),
                'total_amount' => $this->formatMoneyValue((float) $filtered->sum(fn($row) => (float) ($row->total_amount ?? 0))),
                'copayment_amount' => $this->formatMoneyValue((float) $filtered->sum(fn($row) => (float) ($row->copayment_amount ?? 0))),
                'claim_amount' => $this->formatMoneyValue($claimAmount),
                'adjustment_amount' => $this->formatMoneyValue($adjustmentAmount),
                'returned_amount' => $this->formatMoneyValue($returnedAmount),
                'grand_total' => $this->formatMoneyValue($claimAmount + $adjustmentAmount - $returnedAmount),
                'payment_amount' => $this->formatMoneyValue((float) $filtered->sum(fn($row) => (float) ($row->payment_amount ?? 0))),
            ];
        })->all();

        $receiptRows = $receiptRowCollection
            ->map(fn($row): array => [
                'insurance_claim_detail_id' => (int) ($row->insurance_claim_detail_id ?? 0),
                'treatment_month' => $this->formatDateValue($row->treatment_month, 'Y/m/d'),
                'insurer_number' => trim((string) ($row->insurer_number ?? '')),
                'insurer_number_raw' => trim((string) ($row->insurer_number ?? '')),
                'deposit_name' => trim((string) ($row->deposit_name ?? '')),
                'summary_category' => trim((string) ($row->summary_category ?? '')),
                'summary_category_raw' => trim((string) ($row->summary_category ?? '')),
                'total_sheets' => $row->total_sheets,
                'total_sheets_raw' => $row->total_sheets === null ? '' : (string) $row->total_sheets,
                'total_parts_count' => $row->total_parts_count,
                'total_parts_count_raw' => $row->total_parts_count === null ? '' : (string) $row->total_parts_count,
                'returned_amount' => $this->formatMoneyValue($row->returned_amount),
                'returned_amount_raw' => $row->returned_amount === null ? '' : rtrim(rtrim((string) $row->returned_amount, '0'), '.'),
                'store_name' => trim((string) (($row->store_category ?? '') !== '' ? $row->store_category : ($row->store_name ?? ''))),
                'patient_name' => trim((string) ($row->subject_name ?? '')),
                'patient_name_raw' => trim((string) ($row->patient_name ?? '')),
                'remarks' => trim((string) ($row->remarks ?? '')),
                'remarks_raw' => trim((string) ($row->remarks ?? '')),
                'insurance_category' => trim((string) ($row->insurance_category ?? '')),
                'insurer_name' => trim((string) ($row->insurer_name ?? '')),
                'insurance_type' => trim((string) ($row->insurance_type ?? '')),
                'total_amount' => $this->formatMoneyValue($row->total_amount),
                'total_amount_raw' => $this->formatMoneyValue($row->total_amount),
                'copayment_amount' => $this->formatMoneyValue($row->copayment_amount),
                'copayment_amount_raw' => $this->formatMoneyValue($row->copayment_amount),
                'claim_amount' => $this->formatMoneyValue($row->claim_amount),
                'claim_amount_raw' => $this->formatMoneyValue($row->claim_amount),
                'adjustment_amount' => $this->formatMoneyValue($row->adjustment_amount),
                'adjustment_amount_raw' => $this->formatMoneyValue($row->adjustment_amount),
                'payment_date_display' => trim((string) ($row->payment_date_text ?? '')),
                'payment_amount' => $this->formatMoneyValue($row->payment_amount),
                'payment_amount_raw' => $row->payment_amount === null ? '' : rtrim(rtrim((string) $row->payment_amount, '0'), '.'),
            ])
            ->all();

        $storeRows = DB::connection('sqlsrv')
            ->table('dbo.mx_departments')
            ->select(['store_category', 'receipt_type'])
            ->where(function ($query): void {
                $query->where('has_receipt', true)
                    ->orWhere('has_receipt', 1);
            })
            ->whereNull('closed_on')
            ->whereNotNull('store_category')
            ->orderBy('store_category')
            ->get();

        $storeOptions = $this->receiptStoreOptions();

        $receiptTypeByStore = $storeRows
            ->mapWithKeys(fn($row): array => [
                trim((string) ($row->store_category ?? '')) => trim((string) ($row->receipt_type ?? '')),
            ])
            ->filter(fn(string $receiptType, string $storeName): bool => $storeName !== '')
            ->all();

        $insurerLookupOptions = DB::connection('sqlsrv')
            ->table('dbo.mx_insurers')
            ->select([
                'insurer_number',
                'insurer_name',
                'insurance_category',
                'scheduled_payment_name',
                'scheduled_payment_name_2',
            ])
            ->whereNotNull('insurer_number')
            ->orderBy('insurer_number')
            ->get()
            ->map(fn($row): array => [
                'insurer_number' => trim((string) ($row->insurer_number ?? '')),
                'insurer_name' => trim((string) ($row->insurer_name ?? '')),
                'insurance_category' => trim((string) ($row->insurance_category ?? '')),
                'scheduled_payment_name' => trim((string) ($row->scheduled_payment_name ?? '')),
                'scheduled_payment_name_2' => trim((string) ($row->scheduled_payment_name_2 ?? '')),
            ])
            ->filter(fn(array $row): bool => $row['insurer_number'] !== '')
            ->values()
            ->all();

        return view('staff_portal.office.receipt.entry.index', $this->commonViewData($request, [
            'targetMonth' => $targetMonth,
            'selectedStore' => $selectedStore,
            'storeOptions' => $storeOptions,
            'receiptTypeByStore' => $receiptTypeByStore,
            'insurerLookupOptions' => $insurerLookupOptions,
            'receiptRows' => $receiptRows,
            'receiptSummaryRows' => $receiptSummaryRows,
            'isReceiptMonthlyClosed' => $isReceiptMonthlyClosed,
            'receiptMonthlyClosingProcessedAt' => $this->formatDateValue($monthlyClosingRow?->processed_at, 'Y/m/d'),
            'is_admin' => $is_admin,
        ]));
    }

    public function save(Request $request): RedirectResponse|JsonResponse
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'ログインしてください');
        }

        $data = $request->validate(
            [
                'insurance_claim_detail_id' => ['nullable', 'integer'],
                'insurer_number' => ['required', 'string', 'size:8'],
                'summary_category' => ['nullable', 'string', 'max:10'],
                'total_sheets' => ['nullable', 'integer'],
                'total_parts_count' => ['nullable', 'integer'],
                'total_amount' => ['nullable', 'string'],
                'copayment_amount' => ['nullable', 'string'],
                'claim_amount' => ['nullable', 'string'],
                'returned_amount' => ['nullable', 'string'],
                'adjustment_amount' => ['nullable', 'string'],
                'patient_name' => ['nullable', 'string', 'max:50'],
                'remarks' => ['nullable', 'string', 'max:255'],
                'target_month' => ['required', 'date_format:Y-m'],
                'store_name' => ['nullable', 'string', 'max:20'],
                'deposit_name' => ['nullable', 'string', 'max:50'],
            ],
            [
                'insurer_number.required' => '保険者番号を入力してください',
                'insurer_number.size' => '保険者番号は8桁で入力してください',
                'target_month.required' => '施術月を選択してください',
                'target_month.date_format' => '施術月の形式が不正です',
            ]
        );

        $insurerExists = DB::connection('sqlsrv')
            ->table('dbo.mx_insurers')
            ->where('insurer_number', trim((string) $data['insurer_number']))
            ->exists();

        if (!$insurerExists) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => '保険者番号は候補から選択してください',
                ], 422);
            }
            return back()
                ->withErrors(['insurer_number' => '保険者番号は候補から選択してください'])
                ->withInput();
        }

        $targetMonth = Carbon::createFromFormat('Y-m-d', (string) $data['target_month'] . '-01')->endOfMonth()->startOfDay();
        if ($this->isReceiptMonthlyClosed($targetMonth)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => '月次処理済みのため編集できません。',
                ], 423);
            }

            return redirect()->route('office.receipt.entry', [
                'target_month' => $data['target_month'],
                'store_name' => trim((string) ($data['store_name'] ?? '')),
            ])->with('errorMessage', '月次処理済みのため編集できません。');
        }

        $submittedStoreName = trim((string) ($data['store_name'] ?? ''));
        $resolvedStoreShortName = '';

        if ($submittedStoreName !== '') {
            $departmentRow = DB::connection('sqlsrv')
                ->table('dbo.mx_departments')
                ->where('store_category', $submittedStoreName)
                ->select(['store_short_name'])
                ->first();

            $resolvedStoreShortName = trim((string) ($departmentRow?->store_short_name ?? ''));
        }

        $payload = [
            'insurer_number' => trim((string) $data['insurer_number']),
            'treatment_month' => $targetMonth,
            'store_name' => $resolvedStoreShortName,
            'summary_category' => trim((string) ($data['summary_category'] ?? '')),
            'total_sheets' => $data['total_sheets'] === null || $data['total_sheets'] === '' ? null : (int) $data['total_sheets'],
            'total_parts_count' => $data['total_parts_count'] === null || $data['total_parts_count'] === '' ? null : (int) $data['total_parts_count'],
            'total_amount' => $this->parseMoneyInput($data['total_amount'] ?? null),
            'copayment_amount' => $this->parseMoneyInput($data['copayment_amount'] ?? null),
            'claim_amount' => $this->parseMoneyInput($data['claim_amount'] ?? null),
            'returned_amount' => $this->parseMoneyInput($data['returned_amount'] ?? null),
            'adjustment_amount' => $this->parseMoneyInput($data['adjustment_amount'] ?? null),
            'patient_name' => trim((string) ($data['patient_name'] ?? '')),
            'remarks' => trim((string) ($data['remarks'] ?? '')),
            'deposit_name' => trim((string) ($data['deposit_name'] ?? '')),
        ];

        $detailId = (int) ($data['insurance_claim_detail_id'] ?? 0);

        if ($detailId > 0) {
            DB::connection('sqlsrv')
                ->table('dbo.mx_insurance_claim_details')
                ->where('insurance_claim_detail_id', $detailId)
                ->update($payload);
        } else {
            $detailId = (int) DB::connection('sqlsrv')
                ->table('dbo.mx_insurance_claim_details')
                ->insertGetId($payload, 'insurance_claim_detail_id');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'insurance_claim_detail_id' => $detailId,
            ]);
        }

        return redirect()->route('office.receipt.entry', [
            'target_month' => $data['target_month'],
            'store_name' => trim((string) ($data['store_name'] ?? '')),
        ])->with('successMessage', 'レセ請求を保存しました');
    }

    public function delete(Request $request): JsonResponse|RedirectResponse
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'ログインしてください');
        }

        $data = $request->validate([
            'insurance_claim_detail_id' => ['required', 'integer'],
            'target_month' => ['required', 'date_format:Y-m'],
            'store_name' => ['nullable', 'string', 'max:20'],
        ]);

        $targetMonth = Carbon::createFromFormat('Y-m-d', (string) $data['target_month'] . '-01')->endOfMonth()->startOfDay();
        if ($this->isReceiptMonthlyClosed($targetMonth)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => '月次処理済みのため削除できません。',
                ], 423);
            }

            return redirect()->route('office.receipt.entry', [
                'target_month' => $data['target_month'],
                'store_name' => trim((string) ($data['store_name'] ?? '')),
            ])->with('errorMessage', '月次処理済みのため削除できません。');
        }

        DB::connection('sqlsrv')
            ->table('dbo.mx_insurance_claim_details')
            ->where('insurance_claim_detail_id', (int) $data['insurance_claim_detail_id'])
            ->delete();

        return redirect()->route('office.receipt.entry', [
            'target_month' => $data['target_month'],
            'store_name' => trim((string) ($data['store_name'] ?? '')),
        ])->with('successMessage', 'レセ請求を削除しました');
    }

    public function closeMonthly(Request $request): RedirectResponse
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'ログインしてください');
        }

        $data = $request->validate([
            'target_month' => ['required', 'date_format:Y-m'],
            'store_name' => ['nullable', 'string', 'max:20'],
        ]);

        $targetMonthStart = Carbon::createFromFormat('Y-m-d', (string) $data['target_month'] . '-01')->startOfDay();
        $targetMonthNext = $targetMonthStart->copy()->addMonthNoOverflow();
        $targetMonth = $targetMonthStart->copy()->endOfMonth()->startOfDay();
        $monthlyClosingRow = $this->receiptMonthlyClosingRow($targetMonth);

        $createdJournalCount = 0;
        DB::connection('sqlsrv')->transaction(function () use ($targetMonthStart, $targetMonthNext, $targetMonth, $monthlyClosingRow, &$createdJournalCount): void {
            $createdJournalCount = $this->createReceiptMonthlyJournalEntries($targetMonthStart, $targetMonthNext, $targetMonth);
            $createdJournalCount += $this->createReceiptMonthlyWindowJournalEntries($targetMonthStart, $targetMonthNext, $targetMonth);

            if ($monthlyClosingRow === null) {
                DB::connection('sqlsrv')
                    ->table('dbo.mx_monthly_closings')
                    ->insert([
                        'closing_month' => $targetMonth,
                        'authority' => self::MONTHLY_CLOSING_AUTHORITY,
                        'processed_at' => now(),
                    ]);
            }
        });

        $message = $monthlyClosingRow === null ? '月次処理が完了しました。' : '月次処理は既に完了しています。';
        if ($createdJournalCount > 0) {
            $message .= ' 売上仕訳を' . $createdJournalCount . '件反映しました。';
        }

        return redirect()->route('office.receipt.entry', [
            'target_month' => $data['target_month'],
            'store_name' => trim((string) ($data['store_name'] ?? '')),
        ])->with('statusMessage', $message);
    }
    public function unlockMonthly(Request $request): RedirectResponse
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'ログインしてください');
        }

        $staffRow = $this->staffPortalStaffRow($staffId);
        if (!$this->isAdmin($staffRow)) {
            abort(403);
        }

        $data = $request->validate([
            'target_month' => ['required', 'date_format:Y-m'],
            'store_name' => ['nullable', 'string', 'max:20'],
        ]);

        $targetMonthStart = Carbon::createFromFormat('Y-m-d', (string) $data['target_month'] . '-01')->startOfDay();
        $targetMonth = $targetMonthStart->copy()->endOfMonth()->startOfDay();

        $deleted = 0;
        $deletedJournalCount = 0;
        DB::connection('sqlsrv')->transaction(function () use ($targetMonthStart, $targetMonth, &$deleted, &$deletedJournalCount): void {
            $deletedJournalCount = $this->deleteReceiptMonthlyJournalEntries($targetMonthStart);
            $deleted = DB::connection('sqlsrv')
                ->table('dbo.mx_monthly_closings')
                ->whereDate('closing_month', $targetMonth->format('Y-m-d'))
                ->where('authority', self::MONTHLY_CLOSING_AUTHORITY)
                ->delete();
        });

        $message = $deleted > 0 ? 'レセ月次処理を解除しました。' : '解除対象の月次処理がありませんでした。';
        if ($deletedJournalCount > 0) {
            $message .= ' 売上仕訳を' . $deletedJournalCount . '件削除しました。';
        }

        return redirect()->route('office.receipt.entry', [
            'target_month' => $data['target_month'],
            'store_name' => trim((string) ($data['store_name'] ?? '')),
        ])->with('statusMessage', $message);
    }
    private function deleteReceiptMonthlyJournalEntries(Carbon $targetMonthStart): int
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries')
            ->whereDate('month_date', $targetMonthStart->format('Y-m-d'))
            ->where('summary_text', $targetMonthStart->format('n') . '月売上')
            ->where(function ($query) use ($targetMonthStart): void {
                $query->where(function ($query): void {
                    $query->where('debit_account_title', '医療未収入金')
                        ->where('credit_account_title', '保険収入')
                        ->whereIn('credit_item_name', ['一般保険請求', '後期高齢保険請求', '医療助成請求']);
                })->orWhere(function ($query) use ($targetMonthStart): void {
                    $query->where('debit_account_title', '現金')
                        ->where('credit_account_title', '窓口収入')
                        ->whereIn('credit_item_name', ['保険窓口負担', '個人振込'])
                        ->where('journal_breakdown', 'like', $targetMonthStart->format('Ym') . 'レセ%');
                })->orWhere(function ($query) use ($targetMonthStart): void {
                    $query->where('debit_account_title', '現金')
                        ->where('credit_account_title', '自費収入')
                        ->where('credit_item_name', '自費')
                        ->where('journal_breakdown', 'like', $targetMonthStart->format('Ym') . 'レセ自費%');
                });
            })
            ->delete();
    }
    private function createReceiptMonthlyJournalEntries(Carbon $targetMonthStart, Carbon $targetMonthNext, Carbon $targetMonth): int
    {
        $departmentNoColumn = $this->receiptDepartmentNoColumn();
        $prefixCase = "CASE WHEN LTRIM(RTRIM(ISNULL(insurer.insurance_type, N''))) = N'後期高齢' THEN N'後期' WHEN LTRIM(RTRIM(ISNULL(insurer.insurance_type, N''))) = N'医療助成' THEN N'助成' ELSE N'一般' END";
        $itemCase = "CASE WHEN LTRIM(RTRIM(ISNULL(insurer.insurance_type, N''))) = N'後期高齢' THEN N'後期高齢保険請求' WHEN LTRIM(RTRIM(ISNULL(insurer.insurance_type, N''))) = N'医療助成' THEN N'医療助成請求' ELSE N'一般保険請求' END";

        $rows = DB::connection('sqlsrv')
            ->table('dbo.mx_insurance_claim_details as detail')
            ->leftJoin('dbo.mx_insurers as insurer', 'detail.insurer_number', '=', 'insurer.insurer_number')
            ->leftJoin('dbo.mx_departments as department', 'department.store_short_name', '=', 'detail.store_name')
            ->selectRaw("
                detail.store_name as department_name,
                department.{$departmentNoColumn} as department_no,
                {$prefixCase} as journal_prefix,
                {$itemCase} as item_name,
                SUM(ISNULL(detail.claim_amount, 0) + ISNULL(detail.adjustment_amount, 0) - ISNULL(detail.returned_amount, 0)) as amount
            ")
            ->where('detail.treatment_month', '>=', $targetMonthStart->format('Y-m-d'))
            ->where('detail.treatment_month', '<', $targetMonthNext->format('Y-m-d'))
            ->where('detail.insurer_number', '<>', '99999999')
            ->groupByRaw("detail.store_name, department.{$departmentNoColumn}, {$prefixCase}, {$itemCase}")
            ->get();

        $changedCount = 0;

        foreach ($rows as $row) {
            $departmentName = trim((string) ($row->department_name ?? ''));
            $amount = (float) ($row->amount ?? 0);
            if ($departmentName === '' || $amount === 0.0) {
                continue;
            }

            $prefix = trim((string) ($row->journal_prefix ?? '一般'));
            $itemName = trim((string) ($row->item_name ?? '一般保険請求'));
            $departmentNo = trim((string) ($row->department_no ?? ''));
            $journalBreakdown = $targetMonthStart->format('Ym') . $prefix . ($departmentNo !== '' ? $departmentNo : $departmentName);

            $journalQuery = DB::connection('sqlsrv')
                ->table('dbo.mx_journal_entries')
                ->whereDate('month_date', $targetMonthStart->format('Y-m-d'))
                ->where('journal_breakdown', $journalBreakdown)
                ->where('debit_account_title', '医療未収入金')
                ->where('debit_item_name', $itemName)
                ->where('debit_department_name', $departmentName)
                ->where('credit_account_title', '保険収入');

            if ((clone $journalQuery)->exists()) {
                (clone $journalQuery)->update([
                    'summary_text' => $targetMonthStart->format('n') . '月売上',
                    'occurred_at' => $targetMonth->format('Y-m-d'),
                    'debit_amount' => $amount,
                    'credit_amount' => $amount,
                    'credit_item_name' => $itemName,
                    'credit_department_name' => $departmentName,
                    'company_name_short' => $this->receiptMonthlyJournalCompanyName($departmentName),
                ]);
                $changedCount++;
                continue;
            }

            DB::connection('sqlsrv')
                ->table('dbo.mx_journal_entries')
                ->insert([
                    'journal_breakdown' => $journalBreakdown,
                    'summary_text' => $targetMonthStart->format('n') . '月売上',
                    'occurred_at' => $targetMonth->format('Y-m-d'),
                    'month_date' => $targetMonthStart->format('Y-m-d'),
                    'debit_account_title' => '医療未収入金',
                    'debit_item_name' => $itemName,
                    'debit_tax_category' => '対象外',
                    'debit_department_name' => $departmentName,
                    'debit_amount' => $amount,
                    'credit_account_title' => '保険収入',
                    'credit_amount' => $amount,
                    'credit_item_name' => $itemName,
                    'credit_tax_category' => '非課売上',
                    'credit_department_name' => $departmentName,
                    'company_name_short' => $this->receiptMonthlyJournalCompanyName($departmentName),
                ]);

            $changedCount++;
        }

        return $changedCount;
    }
    /**
     * @return array{0: string, 1: string}
     */
    private function createReceiptMonthlyWindowJournalEntries(Carbon $targetMonthStart, Carbon $targetMonthNext, Carbon $targetMonth): int
    {
        $departmentNoColumn = $this->receiptDepartmentNoColumn();

        $rows = DB::connection('sqlsrv')
            ->table('dbo.mx_insurance_claim_details as detail')
            ->leftJoin('dbo.mx_departments as department', 'department.store_short_name', '=', 'detail.store_name')
            ->selectRaw("
                detail.store_name as department_name,
                department.{$departmentNoColumn} as department_no,
                SUM(CASE WHEN LTRIM(RTRIM(ISNULL(detail.deposit_name, N''))) IN (N'ｼﾞﾋ', N'ｼﾞﾋｺｳｻﾞ') THEN 0 ELSE ISNULL(detail.cash_collected_amount, 0) END) as counter_amount,
                SUM(CASE WHEN LTRIM(RTRIM(ISNULL(detail.deposit_name, N''))) IN (N'ｼﾞﾋ', N'ｼﾞﾋｺｳｻﾞ') THEN 0 ELSE ISNULL(detail.payment_amount, 0) END) as personal_transfer_amount,
                SUM(CASE WHEN LTRIM(RTRIM(ISNULL(detail.deposit_name, N''))) = N'ｼﾞﾋ' THEN ISNULL(detail.cash_collected_amount, 0) ELSE 0 END) as self_pay_amount
            ")
            ->where('detail.treatment_month', '>=', $targetMonthStart->format('Y-m-d'))
            ->where('detail.treatment_month', '<', $targetMonthNext->format('Y-m-d'))
            ->where('detail.insurer_number', '99999999')
            ->groupByRaw("detail.store_name, department.{$departmentNoColumn}")
            ->get();

        $changedCount = 0;

        foreach ($rows as $row) {
            $departmentName = trim((string) ($row->department_name ?? ''));
            if ($departmentName === '') {
                continue;
            }

            $departmentNo = trim((string) ($row->department_no ?? ''));
            $journalKey = $departmentNo !== '' ? $departmentNo : $departmentName;

            $changedCount += $this->upsertReceiptMonthlyWindowJournalEntry(
                $targetMonthStart,
                $targetMonth,
                '窓口',
                $journalKey,
                $departmentName,
                '保険窓口負担',
                (float) ($row->counter_amount ?? 0)
            );

            $changedCount += $this->upsertReceiptMonthlyWindowJournalEntry(
                $targetMonthStart,
                $targetMonth,
                '個人',
                $journalKey,
                $departmentName,
                '個人振込',
                (float) ($row->personal_transfer_amount ?? 0)
            );

            $changedCount += $this->upsertReceiptMonthlySelfPayJournalEntry(
                $targetMonthStart,
                $targetMonth,
                $journalKey,
                $departmentName,
                (float) ($row->self_pay_amount ?? 0)
            );
        }

        return $changedCount;
    }

    private function upsertReceiptMonthlySelfPayJournalEntry(
        Carbon $targetMonthStart,
        Carbon $targetMonth,
        string $journalKey,
        string $departmentName,
        float $amount
    ): int {
        if ($amount === 0.0) {
            return 0;
        }

        $journalBreakdown = $targetMonthStart->format('Ym') . 'レセ自費' . $journalKey;
        $payload = [
            'journal_breakdown' => $journalBreakdown,
            'summary_text' => $targetMonthStart->format('n') . '月売上',
            'occurred_at' => $targetMonth->format('Y-m-d'),
            'month_date' => $targetMonthStart->format('Y-m-d'),
            'debit_account_title' => '現金',
            'debit_item_name' => '自費',
            'debit_tax_category' => '対象外',
            'debit_department_name' => $departmentName,
            'debit_amount' => $amount,
            'credit_account_title' => '自費収入',
            'credit_amount' => $amount,
            'credit_item_name' => '自費',
            'credit_tax_category' => '課税売上10%',
            'credit_department_name' => $departmentName,
            'company_name_short' => $this->receiptMonthlyJournalCompanyName($departmentName),
        ];

        $journalQuery = DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries')
            ->whereDate('month_date', $targetMonthStart->format('Y-m-d'))
            ->where('journal_breakdown', $journalBreakdown)
            ->where('debit_account_title', '現金')
            ->where('credit_account_title', '自費収入')
            ->where('credit_item_name', '自費');

        if ((clone $journalQuery)->exists()) {
            (clone $journalQuery)->update($payload);
            return 1;
        }

        DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries')
            ->insert($payload);

        return 1;
    }

    private function upsertReceiptMonthlyWindowJournalEntry(
        Carbon $targetMonthStart,
        Carbon $targetMonth,
        string $journalLabel,
        string $journalKey,
        string $departmentName,
        string $itemName,
        float $amount
    ): int {
        if ($amount === 0.0) {
            return 0;
        }

        $journalBreakdown = $targetMonthStart->format('Ym') . 'レセ' . $journalLabel . $journalKey;
        $payload = [
            'journal_breakdown' => $journalBreakdown,
            'summary_text' => $targetMonthStart->format('n') . '月売上',
            'occurred_at' => $targetMonth->format('Y-m-d'),
            'month_date' => $targetMonthStart->format('Y-m-d'),
            'debit_account_title' => '現金',
            'debit_item_name' => $itemName,
            'debit_tax_category' => '対象外',
            'debit_department_name' => $departmentName,
            'debit_amount' => $amount,
            'credit_account_title' => '窓口収入',
            'credit_amount' => $amount,
            'credit_item_name' => $itemName,
            'credit_tax_category' => '非課売上',
            'credit_department_name' => $departmentName,
            'company_name_short' => $this->receiptMonthlyJournalCompanyName($departmentName),
        ];

        $journalQuery = DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries')
            ->whereDate('month_date', $targetMonthStart->format('Y-m-d'))
            ->where('journal_breakdown', $journalBreakdown)
            ->where('debit_account_title', '現金')
            ->where('credit_account_title', '窓口収入')
            ->where('credit_item_name', $itemName);

        if ((clone $journalQuery)->exists()) {
            (clone $journalQuery)->update($payload);
            return 1;
        }

        DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries')
            ->insert($payload);

        return 1;
    }
    private function receiptMonthlyJournalItem(string $insuranceType): array
    {
        $insuranceType = trim($insuranceType);
        if ($insuranceType === '後期高齢') {
            return ['後期', '後期高齢保険請求'];
        }
        if ($insuranceType === '医療助成') {
            return ['助成', '医療助成請求'];
        }

        return ['一般', '一般保険請求'];
    }

    private function receiptDepartmentNoColumn(): string
    {
        return 'department_no';
    }
    private function receiptMonthlyJournalCompanyName(string $departmentName): string
    {
        return trim((string) (DB::connection('sqlsrv')
            ->table('dbo.mx_journal_entries')
            ->where(function ($query) use ($departmentName): void {
                $query->where('debit_department_name', $departmentName)
                    ->orWhere('credit_department_name', $departmentName);
            })
            ->whereNotNull('company_name_short')
            ->where('company_name_short', '<>', '')
            ->orderByDesc('journal_entry_id')
            ->value('company_name_short') ?? ''));
    }
    private function receiptMonthlyClosingRow(Carbon $targetMonth): ?object
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_monthly_closings')
            ->whereDate('closing_month', $targetMonth->format('Y-m-d'))
            ->where('authority', self::MONTHLY_CLOSING_AUTHORITY)
            ->first();
    }

    private function isReceiptMonthlyClosed(Carbon $targetMonth): bool
    {
        return $this->receiptMonthlyClosingRow($targetMonth) !== null;
    }


    // 柔整取込インポート
    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file'],
            'sejutsu_month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'store_name' => ['required', 'string'],
        ], [
            'csv_file.required' => 'CSVファイルを選択してください。',
            'sejutsu_month.required' => '施術月を選択してください。',
            'sejutsu_month.regex' => '施術月の形式が正しくありません。',
            'store_name.required' => '店舗を選択してください。',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getPathname(), 'r');

        if ($handle === false) {
            return back()->with('errorMessage', 'CSVファイルを開けませんでした。');
        }

        $header = fgetcsv($handle);

        if ($header !== false) {
            $header = mb_convert_encoding($header, 'UTF-8', 'SJIS-win');

            if ($this->csvRowContainsQuestionMark($header)) {
                fclose($handle);
                return back()->with('errorMessage', 'CSV内に文字化けの可能性がある「?」が含まれています。元データを修正してから再取込してください。対象行: 1');
            }
        }

        $treatmentMonth = Carbon::createFromFormat('Y-m-d', (string) $request->input('sejutsu_month') . '-01')->endOfMonth()->startOfDay();
        $storeName = trim((string) $request->input('store_name'));

        if ($storeName === '') {
            fclose($handle);
            return back()->with('errorMessage', '店舗を選択してください。');
        }

        $departmentRow = DB::connection('sqlsrv')
            ->table('dbo.mx_departments')
            ->where('store_category', $storeName)
            ->select(['store_short_name'])
            ->first();
        $resolvedStoreShortName = trim((string) ($departmentRow?->store_short_name ?? ''));

        if ($resolvedStoreShortName === '') {
            fclose($handle);
            return back()->with('errorMessage', '店舗に紐づく店舗略称が見つかりません。');
        }

        $alreadyImported = DB::connection('sqlsrv')
            ->table('dbo.mx_insurance_claim_details')
            ->whereDate('treatment_month', $treatmentMonth->format('Y-m-d'))
            ->where('store_name', $resolvedStoreShortName)
            ->exists();

        if ($alreadyImported) {
            fclose($handle);
            return back()->with('errorMessage', 'この月・店舗のデータは既に取込済です。');
        }

        $count = 0;
        $importRows = [];
        $newInsurers = [];
        $lineNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;

            // 文字コード対策（必要なら）
            $row = mb_convert_encoding($row, 'UTF-8', 'SJIS-win');

            if ($this->csvRowContainsQuestionMark($row)) {
                fclose($handle);
                return back()->with('errorMessage', 'CSV内に文字化けの可能性がある「?」が含まれています。元データを修正してから再取込してください。対象行: ' . $lineNumber);
            }

            if (count($row) < 9) {
                continue;
            }

            $insurerNumber = trim((string) ($row[1] ?? ''));
            $insurerName = trim((string) ($row[2] ?? ''));

            if (!preg_match('/^\d{8}$/', $insurerNumber)) {
                fclose($handle);
                return back()->with('errorMessage', '保険者番号は8桁である必要があります。取込を中止しました。対象行: ' . $lineNumber . ' / 値: ' . $insurerNumber);
            }

            $importRows[] = [
                'treatment_month' => $treatmentMonth,
                'insurer_number' => $insurerNumber,
                'summary_category' => trim((string) ($row[3] ?? '')),
                'total_sheets' => $row[4] === '' ? null : (int) str_replace(',', '', (string) $row[4]),
                'total_parts_count' => $row[5] === '' ? null : (int) str_replace(',', '', (string) $row[5]),
                'total_amount' => $this->parseMoneyInput($row[6] ?? null),
                'copayment_amount' => $this->parseMoneyInput($row[7] ?? null),
                'claim_amount' => $this->parseMoneyInput($row[8] ?? null),
                'store_name' => $resolvedStoreShortName,
            ];

            if ($insurerName !== '') {
                $newInsurers[$insurerNumber] = [
                    'insurer_number' => $insurerNumber,
                    'insurer_name' => $insurerName,
                    'scheduled_payment_name' => '',
                    'scheduled_payment_name_2' => '',
                    'insurance_type' => '',
                    'subsidy_type' => '',
                ];
            }

            $count++;
        }

        fclose($handle);

        if ($importRows === []) {
            return back()->with('errorMessage', '取込対象のデータがありません。');
        }

        $insertedInsurerCount = 0;

        DB::connection('sqlsrv')->transaction(function () use ($newInsurers, $importRows, &$insertedInsurerCount): void {
            foreach ($newInsurers as $insurerNumber => $payload) {
                $exists = DB::connection('sqlsrv')
                    ->table('dbo.mx_insurers')
                    ->where('insurer_number', $insurerNumber)
                    ->exists();

                if (!$exists) {
                    DB::connection('sqlsrv')
                        ->table('dbo.mx_insurers')
                        ->insert($payload);
                    $insertedInsurerCount++;
                }
            }

            DB::connection('sqlsrv')
                ->table('dbo.mx_insurance_claim_details')
                ->insert($importRows);
        });

        return redirect()->route('office.receipt.entry', [
            'target_month' => (string) $request->input('sejutsu_month'),
            'store_name' => $storeName,
        ])->with(
            'statusMessage',
            '保険者を' . $insertedInsurerCount . '件登録しました。明細を' . count($importRows) . '件取込しました。'
        );
    }

    private function csvRowContainsQuestionMark(array $row): bool
    {
        foreach ($row as $value) {
            if (str_contains((string) $value, '?')) {
                return true;
            }
        }

        return false;
    }

    private function parseMoneyInput(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $text = trim(str_replace([',', '，', ' '], '', (string) $value));

        if ($text === '' || !is_numeric($text)) {
            return null;
        }

        return (float) $text;
    }
}

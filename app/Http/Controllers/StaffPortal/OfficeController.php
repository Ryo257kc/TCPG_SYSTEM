<?php

namespace App\Http\Controllers\StaffPortal;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\Master\CompanyV2Service;
use App\Services\Admin\V2\Sales\SalesV2Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OfficeController extends Controller
{
    public function __construct(
        private readonly CompanyV2Service $companyService,
        private readonly SalesV2Service $salesService,
    ) {
    }

    public function salesMenu(Request $request): RedirectResponse|View
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'ログインしてください');
        }

        return view('staff_portal.admin.sales.index', [
            'displayName' => $this->resolveDisplayName($staffId),
            'hidePayrollLinks' => false,
        ]);
    }

    public function sales(Request $request): RedirectResponse|View
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'ログインしてください');
        }

        $companyRows = $this->companyService->list('')['rows'] ?? [];
        $companyOptions = array_values(array_filter(array_map(
            static fn (array $row): array => [
                'company_id' => trim((string) ($row['company_id'] ?? '')),
                'company_name' => trim((string) ($row['company_name'] ?? '')),
            ],
            $companyRows
        ), static fn (array $row): bool => $row['company_id'] !== '' && $row['company_name'] !== ''));

        $targetMonth = trim((string) $request->query('target_month', now()->format('Y-m')));
        $companyId = trim((string) $request->query('company_id', ''));
        $summary = $this->salesService->summary($targetMonth, $companyId);

        return view('staff_portal.office.sales.index', [
            'displayName' => $this->resolveDisplayName($staffId),
            'hidePayrollLinks' => false,
            'companyOptions' => $companyOptions,
            'salesRows' => $summary['rows'],
            'targetMonth' => $summary['target_month'],
            'selectedCompanyId' => $summary['company_id'],
            'grandTotal' => $summary['grand_total'],
        ]);
    }

    public function salesPrint(Request $request): RedirectResponse|View
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'ログインしてください');
        }

        $companyRows = $this->companyService->list('')['rows'] ?? [];
        $companyOptions = array_values(array_filter(array_map(
            static fn (array $row): array => [
                'company_id' => trim((string) ($row['company_id'] ?? '')),
                'company_name' => trim((string) ($row['company_name'] ?? '')),
            ],
            $companyRows
        ), static fn (array $row): bool => $row['company_id'] !== '' && $row['company_name'] !== ''));

        $targetMonth = trim((string) $request->query('target_month', now()->format('Y-m')));
        $companyId = trim((string) $request->query('company_id', ''));
        $summary = $this->salesService->pdfSummary($targetMonth, $companyId);
        $companyName = '';

        foreach ($companyOptions as $option) {
            if (($option['company_id'] ?? '') === $summary['company_id']) {
                $companyName = (string) ($option['company_name'] ?? '');
                break;
            }
        }

        return view('staff_portal.office.sales.print', [
            'stores' => $summary['stores'],
            'targetMonth' => $summary['target_month'],
            'selectedCompanyId' => $summary['company_id'],
            'grandTotal' => $summary['grand_total'],
            'companyName' => $companyName,
        ]);
    }

    private function resolveDisplayName(string $staffId): string
    {
        $row = \Illuminate\Support\Facades\DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->whereRaw('LTRIM(RTRIM(staff_id)) = ?', [$staffId])
            ->first(['staff_name']);

        $name = trim((string) ($row->staff_name ?? ''));

        return $name !== '' ? $name : $staffId;
    }

    public function receipt(Request $request): RedirectResponse|View
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'ログインしてください');
        }

        return view('staff_portal.office.receipt.index', [
            'displayName' => $this->resolveDisplayName($staffId),
            'hidePayrollLinks' => false,
        ]);
    }

    public function receiptEntry(Request $request): RedirectResponse|View
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'ログインしてください');
        }

        $targetMonth = trim((string) $request->query('target_month', now()->format('Y-m')));
        $selectedStore = trim((string) $request->query('store_name', ''));

        [$targetYear, $targetMonthNo] = $this->parseTargetMonth($targetMonth);

        $rowsQuery = DB::connection('sqlsrv')
            ->table('dbo.mx_insurance_claim_details as detail')
            ->leftJoin('dbo.mx_insurers as insurer', 'detail.insurer_number', '=', 'insurer.insurer_number')
            ->leftJoin('dbo.mx_departments as department', 'detail.store_name', '=', 'department.store_short_name')
            ->select([
                'detail.insurance_claim_detail_id',
                'detail.treatment_month',
                'detail.insurer_number',
                'detail.deposit_name',
                'detail.summary_category',
                'detail.total_sheets',
                'detail.total_parts_count',
                'detail.returned_amount',
                'detail.store_name',
                'department.store_category',
                'detail.patient_name',
                'detail.remarks',
                'insurer.insurance_category',
                'insurer.insurance_type',
                'insurer.insurer_name',
                'detail.total_amount',
                'detail.copayment_amount',
                'detail.claim_amount',
                'detail.adjustment_amount',
                'detail.payment_date_display',
                'detail.payment_amount',
            ])
            ->where('detail.insurer_number', '<>', '99999999')
            ->whereYear('detail.treatment_month', $targetYear)
            ->whereMonth('detail.treatment_month', $targetMonthNo);

        if ($selectedStore !== '') {
            $rowsQuery->where('department.store_category', $selectedStore);
        }

        $receiptRowCollection = $rowsQuery
            ->orderBy('detail.treatment_month')
            ->orderBy('detail.insurer_number')
            ->orderBy('detail.insurance_claim_detail_id')
            ->get();

        $receiptSummaryRows = collect([
            ['label' => '一般', 'filter' => static fn ($row): bool => trim((string) ($row->insurance_type ?? '')) !== '後期高齢' && trim((string) ($row->insurance_type ?? '')) !== '医療助成'],
            ['label' => '後期高齢', 'filter' => static fn ($row): bool => trim((string) ($row->insurance_type ?? '')) === '後期高齢'],
            ['label' => '医療助成', 'filter' => static fn ($row): bool => trim((string) ($row->insurance_type ?? '')) === '医療助成'],
        ])->map(function (array $summaryRow) use ($receiptRowCollection): array {
            $filtered = $receiptRowCollection->filter($summaryRow['filter']);

            $claimAmount = (float) $filtered->sum(fn ($row) => (float) ($row->claim_amount ?? 0));
            $adjustmentAmount = (float) $filtered->sum(fn ($row) => (float) ($row->adjustment_amount ?? 0));
            $returnedAmount = (float) $filtered->sum(fn ($row) => (float) ($row->returned_amount ?? 0));

            return [
                'row_count' => $filtered->count() . '件',
                'label' => $summaryRow['label'],
                'total_sheets' => (string) ((int) $filtered->sum(fn ($row) => (int) ($row->total_sheets ?? 0))),
                'total_parts_count' => (string) ((int) $filtered->sum(fn ($row) => (int) ($row->total_parts_count ?? 0))),
                'total_amount' => $this->formatMoneyValue((float) $filtered->sum(fn ($row) => (float) ($row->total_amount ?? 0))),
                'copayment_amount' => $this->formatMoneyValue((float) $filtered->sum(fn ($row) => (float) ($row->copayment_amount ?? 0))),
                'claim_amount' => $this->formatMoneyValue($claimAmount),
                'adjustment_amount' => $this->formatMoneyValue($adjustmentAmount),
                'returned_amount' => $this->formatMoneyValue($returnedAmount),
                'grand_total' => $this->formatMoneyValue($claimAmount + $adjustmentAmount - $returnedAmount),
                'payment_amount' => $this->formatMoneyValue((float) $filtered->sum(fn ($row) => (float) ($row->payment_amount ?? 0))),
            ];
        })->all();

        $receiptRows = $receiptRowCollection
            ->map(fn ($row): array => [
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
                'patient_name' => trim((string) ($row->patient_name ?? '')),
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
                'payment_date_display' => $this->formatDateValue($row->payment_date_display, 'Y/m/d'),
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

        $storeOptions = $storeRows
            ->map(fn ($row): string => trim((string) ($row->store_category ?? '')))
            ->filter(fn (string $name): bool => $name !== '')
            ->unique()
            ->values()
            ->all();

        $receiptTypeByStore = $storeRows
            ->mapWithKeys(fn ($row): array => [
                trim((string) ($row->store_category ?? '')) => trim((string) ($row->receipt_type ?? '')),
            ])
            ->filter(fn (string $receiptType, string $storeName): bool => $storeName !== '')
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
            ->map(fn ($row): array => [
                'insurer_number' => trim((string) ($row->insurer_number ?? '')),
                'insurer_name' => trim((string) ($row->insurer_name ?? '')),
                'insurance_category' => trim((string) ($row->insurance_category ?? '')),
                'scheduled_payment_name' => trim((string) ($row->scheduled_payment_name ?? '')),
                'scheduled_payment_name_2' => trim((string) ($row->scheduled_payment_name_2 ?? '')),
            ])
            ->filter(fn (array $row): bool => $row['insurer_number'] !== '')
            ->values()
            ->all();

        return view('staff_portal.office.receipt_entry.index', [
            'displayName' => $this->resolveDisplayName($staffId),
            'hidePayrollLinks' => false,
            'targetMonth' => $targetMonth,
            'selectedStore' => $selectedStore,
            'storeOptions' => $storeOptions,
            'receiptTypeByStore' => $receiptTypeByStore,
            'insurerLookupOptions' => $insurerLookupOptions,
            'receiptRows' => $receiptRows,
            'receiptSummaryRows' => $receiptSummaryRows,
        ]);
    }

    public function receiptEntrySave(Request $request): RedirectResponse|JsonResponse
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

        $targetMonth = Carbon::createFromFormat('Y-m', (string) $data['target_month'])->endOfMonth()->startOfDay();

        $submittedStoreName = trim((string) ($data['store_name'] ?? ''));
        $resolvedStoreShortName = '';
        if ($submittedStoreName !== '') {
            $resolvedStoreShortName = trim((string) (DB::connection('sqlsrv')
                ->table('dbo.mx_departments')
                ->where('store_category', $submittedStoreName)
                ->value('store_short_name') ?? ''));
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
            $nextId = (int) DB::connection('sqlsrv')
                ->table('dbo.mx_insurance_claim_details')
                ->max('insurance_claim_detail_id') + 1;

            DB::connection('sqlsrv')
                ->table('dbo.mx_insurance_claim_details')
                ->insert(array_merge($payload, [
                    'insurance_claim_detail_id' => $nextId,
                ]));

            $detailId = $nextId;
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

    public function receiptEntryDelete(Request $request): RedirectResponse
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

        DB::connection('sqlsrv')
            ->table('dbo.mx_insurance_claim_details')
            ->where('insurance_claim_detail_id', (int) $data['insurance_claim_detail_id'])
            ->delete();

        return redirect()->route('office.receipt.entry', [
            'target_month' => $data['target_month'],
            'store_name' => trim((string) ($data['store_name'] ?? '')),
        ])->with('successMessage', 'レセ請求を削除しました');
    }

    public function paymentConfirmation(Request $request): RedirectResponse|View
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', '繝ｭ繧ｰ繧､繝ｳ縺励※縺上□縺輔＞');
        }

        return view('staff_portal.office.payment_confirmation.index', [
            'displayName' => $this->resolveDisplayName($staffId),
            'hidePayrollLinks' => false,
        ]);
    }

    public function homeVisitCounter(Request $request): RedirectResponse|View
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', '繝ｭ繧ｰ繧､繝ｳ縺励※縺上□縺輔＞');
        }

        return view('staff_portal.office.home_visit_counter.index', [
            'displayName' => $this->resolveDisplayName($staffId),
            'hidePayrollLinks' => false,
        ]);
    }

    public function insurers(Request $request): RedirectResponse|View
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', '繝ｭ繧ｰ繧､繝ｳ縺励※縺上□縺輔＞');
        }

        $search = trim((string) $request->query('search', ''));
        $depositNameFilter = trim((string) $request->query('deposit_name_display', 'all'));
        $activeTab = trim((string) $request->query('tab', 'all'));

        $rowsQuery = DB::connection('sqlsrv')
            ->table('dbo.mx_insurers')
            ->select([
                'insurer_number',
                'insurer_name',
                'scheduled_payment_name',
                'scheduled_payment_name_2',
                'insurance_type',
                'subsidy_type',
                'insurance_kind',
            ]);

        if ($search !== '') {
            $rowsQuery->where(function ($query) use ($search): void {
                $query->where('insurer_number', 'like', $search . '%')
                    ->orWhere('insurer_name', 'like', '%' . $search . '%');
            });
        }

        if ($depositNameFilter === 'with') {
            $rowsQuery->where(function ($query): void {
                $query->where(function ($nested): void {
                    $nested->whereNotNull('scheduled_payment_name')
                        ->where('scheduled_payment_name', '<>', '');
                })->orWhere(function ($nested): void {
                    $nested->whereNotNull('scheduled_payment_name_2')
                        ->where('scheduled_payment_name_2', '<>', '');
                });
            });
        } elseif ($depositNameFilter === 'without') {
            $rowsQuery->where(function ($query): void {
                $query->whereNull('scheduled_payment_name')
                    ->orWhere('scheduled_payment_name', '');
            })->where(function ($query): void {
                $query->whereNull('scheduled_payment_name_2')
                    ->orWhere('scheduled_payment_name_2', '');
            });
        }

        if ($activeTab === 'general') {
            $rowsQuery
                ->where(function ($query): void {
                    $query->whereNull('insurance_type')
                        ->orWhere('insurance_type', '<>', '後期高齢');
                })
                ->where(function ($query): void {
                    $query->whereNull('insurance_type')
                        ->orWhere('insurance_type', '<>', '医療助成');
                });
        } elseif ($activeTab === 'elderly') {
            $rowsQuery->where('insurance_type', 'like', '後期高齢%');
        } elseif ($activeTab === 'medical_support') {
            $rowsQuery->where('insurance_type', 'like', '医療助成%');
        } elseif ($activeTab === 'incomplete') {
            $rowsQuery
                ->whereNotNull('insurer_number')
                ->where('insurer_number', '<>', '')
                ->where(function ($query): void {
                    $query->whereNull('insurer_name')
                        ->orWhere('insurer_name', '');
                });
        }

        $insurerRows = $rowsQuery
            ->orderBy('insurer_number')
            ->get()
            ->map(static fn ($row): array => [
                'insurer_number' => trim((string) ($row->insurer_number ?? '')),
                'insurer_name' => trim((string) ($row->insurer_name ?? '')),
                'scheduled_payment_name' => trim((string) ($row->scheduled_payment_name ?? '')),
                'scheduled_payment_name_2' => trim((string) ($row->scheduled_payment_name_2 ?? '')),
                'insurance_type' => trim((string) ($row->insurance_type ?? '')),
                'subsidy_type' => trim((string) ($row->subsidy_type ?? '')),
                'insurance_kind' => trim((string) ($row->insurance_kind ?? '')),
            ])
            ->all();

        return view('staff_portal.office.insurers.index', [
            'displayName' => $this->resolveDisplayName($staffId),
            'hidePayrollLinks' => false,
            'search' => $search,
            'depositNameFilter' => in_array($depositNameFilter, ['all', 'with', 'without'], true) ? $depositNameFilter : 'all',
            'activeTab' => in_array($activeTab, ['all', 'general', 'elderly', 'medical_support', 'incomplete'], true) ? $activeTab : 'all',
            'insurerRows' => $insurerRows,
        ]);
    }

    public function insurersSave(Request $request): RedirectResponse
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'ログインしてください');
        }

        $data = $request->validate(
            [
                'original_insurer_number' => ['nullable', 'string', 'max:8'],
                'insurer_number' => ['required', 'string', 'size:8'],
                'insurer_name' => ['nullable', 'string', 'max:50'],
                'scheduled_payment_name' => ['nullable', 'string', 'max:50'],
                'scheduled_payment_name_2' => ['nullable', 'string', 'max:50'],
                'insurance_type' => ['nullable', 'string', 'max:20'],
                'subsidy_type' => ['nullable', 'string', 'max:30'],
            ],
            [
                'insurer_number.required' => '保険者番号を入力してください',
                'insurer_number.size' => '保険者番号は8桁で入力してください',
            ]
        );

        $originalInsurerNumber = trim((string) ($data['original_insurer_number'] ?? ''));
        $insurerNumber = trim((string) $data['insurer_number']);

        $payload = [
            'insurer_number' => $insurerNumber,
            'insurer_name' => trim((string) ($data['insurer_name'] ?? '')),
            'scheduled_payment_name' => trim((string) ($data['scheduled_payment_name'] ?? '')),
            'scheduled_payment_name_2' => trim((string) ($data['scheduled_payment_name_2'] ?? '')),
            'insurance_type' => trim((string) ($data['insurance_type'] ?? '')),
            'subsidy_type' => trim((string) ($data['subsidy_type'] ?? '')),
        ];

        $query = DB::connection('sqlsrv')->table('dbo.mx_insurers');

        if ($originalInsurerNumber !== '') {
            $existing = $query
                ->where('insurer_number', $originalInsurerNumber)
                ->first(['insurer_number']);

            if ($existing === null) {
                return back()->with('errorMessage', '更新対象の保険者が見つかりません');
            }

            if ($originalInsurerNumber !== $insurerNumber) {
                $duplicate = DB::connection('sqlsrv')
                    ->table('dbo.mx_insurers')
                    ->where('insurer_number', $insurerNumber)
                    ->exists();

                if ($duplicate) {
                    return back()->with('errorMessage', '同じ保険者番号が既に存在します');
                }
            }

            $query->where('insurer_number', $originalInsurerNumber)->update($payload);
        } else {
            $duplicate = DB::connection('sqlsrv')
                ->table('dbo.mx_insurers')
                ->where('insurer_number', $insurerNumber)
                ->exists();

            if ($duplicate) {
                return back()->with('errorMessage', '同じ保険者番号が既に存在します');
            }

            DB::connection('sqlsrv')->table('dbo.mx_insurers')->insert($payload);
        }

        return back()->with('successMessage', '保険者を保存しました');
    }

    public function insurersDelete(Request $request): RedirectResponse
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'ログインしてください');
        }

        $data = $request->validate([
            'original_insurer_number' => ['required', 'string', 'max:8'],
        ]);

        DB::connection('sqlsrv')
            ->table('dbo.mx_insurers')
            ->where('insurer_number', trim((string) $data['original_insurer_number']))
            ->delete();

        return back()->with('successMessage', '保険者を削除しました');
    }

    private function parseTargetMonth(string $targetMonth): array
    {
        if (preg_match('/^(\d{4})-(\d{2})$/', $targetMonth, $matches) === 1) {
            return [(int) $matches[1], (int) $matches[2]];
        }

        return [(int) now()->format('Y'), (int) now()->format('m')];
    }

    private function formatDateValue(mixed $value, string $format): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format($format);
        }

        try {
            return Carbon::parse((string) $value)->format($format);
        } catch (\Throwable) {
            return trim((string) $value);
        }
    }

    private function formatMoneyValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $numeric = (float) $value;
        if (abs($numeric) < 0.0000001) {
            return '';
        }

        if (abs($numeric - round($numeric)) < 0.0000001) {
            return number_format((float) round($numeric));
        }

        return rtrim(rtrim(number_format($numeric, 4, '.', ','), '0'), '.');
    }

    private function parseMoneyInput(mixed $value): ?float
    {
        $normalized = str_replace(',', '', trim((string) ($value ?? '')));
        if ($normalized === '') {
            return null;
        }

        return is_numeric($normalized) ? (float) $normalized : null;
    }

}

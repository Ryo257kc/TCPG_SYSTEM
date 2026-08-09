<?php

namespace App\Http\Controllers\StaffPortal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use App\Services\Admin\V2\Master\CompanyV2Service;
use App\Services\Admin\V2\Sales\SalesV2Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class OfficeController extends Controller
{
    use HandlesStaffPortalContext;

    public function __construct(
        private readonly CompanyV2Service $companyService,
        private readonly SalesV2Service $salesService,
    ) {}

    public function salesMenu(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);

        $companyRows = $this->companyService->list('')['rows'] ?? [];
        $companyOptions = array_values(array_filter(array_map(
            static fn(array $row): array => [
                'company_id' => trim((string) ($row['company_id'] ?? '')),
                'company_name' => trim((string) ($row['company_name'] ?? '')),
            ],
            $companyRows
        ), static fn(array $row): bool => $row['company_id'] !== '' && $row['company_name'] !== ''));

        return view('staff_portal.office.sales.index', $this->commonViewData($request, [
            'companyOptions' => $companyOptions,
        ]));
    }

    // 売上一覧
    public function sales(Request $request): RedirectResponse|View
    {
        $staffId = (string) $request->session()->get('staff_id', '');

        $companyRows = $this->companyService->list('')['rows'] ?? [];
        $companyOptions = array_values(array_filter(array_map(
            static fn(array $row): array => [
                'company_id' => trim((string) ($row['company_id'] ?? '')),
                'company_name' => trim((string) ($row['company_name'] ?? '')),
            ],
            $companyRows
        ), static fn(array $row): bool => $row['company_id'] !== '' && $row['company_name'] !== ''));

        $targetMonth = trim((string) $request->query('target_month', now()->format('Y-m')));
        $companyId = trim((string) $request->query('company_id', ''));
        $summary = $this->salesService->summary($targetMonth, $companyId);

        return view('staff_portal.office.sales.index', $this->commonViewData($request, [
            'companyOptions' => $companyOptions,
            'salesRows' => $summary['rows'],
            'targetMonth' => $summary['target_month'],
            'selectedCompanyId' => $summary['company_id'],
            'grandTotal' => $summary['grand_total'],
        ]));
    }

    public function salesPrint(Request $request): RedirectResponse|View
    {
        $staffId = (string) $request->session()->get('staff_id', '');

        $companyRows = $this->companyService->list('')['rows'] ?? [];
        $companyOptions = array_values(array_filter(array_map(
            static fn(array $row): array => [
                'company_id' => trim((string) ($row['company_id'] ?? '')),
                'company_name' => trim((string) ($row['company_name'] ?? '')),
            ],
            $companyRows
        ), static fn(array $row): bool => $row['company_id'] !== '' && $row['company_name'] !== ''));

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

    // 未収入金一覧
    public function uncollectedReceivables(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);

        $paymentMonth = trim((string) $request->query('payment_month', now()->format('Y-m')));
        $selectedStoreCategory = trim((string) $request->query('store_category', ''));
        $selectedCompanyId = trim((string) $request->query('company_id', ''));

        $companyRows = $this->companyService->list('')['rows'] ?? [];
        $companyOptions = array_values(array_filter(array_map(
            static fn(array $row): array => [
                'company_id' => trim((string) ($row['company_id'] ?? '')),
                'company_name' => trim((string) ($row['company_name'] ?? '')),
            ],
            $companyRows
        ), static fn(array $row): bool => $row['company_id'] !== '' && $row['company_name'] !== ''));

        $remainingAmountSql = $this->remainingAmountSql();

        $rowsQuery = $this->receiptDetailBaseQuery()
            ->select([
                'detail.insurance_claim_detail_id',
                'detail.insurer_number',
                'insurer.insurer_name',
                'insurer.insurance_type',
                'detail.treatment_month',
                'detail.claim_amount',
                'detail.adjustment_amount',
                'detail.cash_collected_amount',
                'detail.returned_amount',
                'detail.remarks',
                'detail.payment_date_text',
                'detail.payment_amount',
                'detail.deposit_name',
                'department.store_category',
                'company.company_id',
                'company.company_name',
                'department.bank_account_selection',
            ])
            ->selectRaw($remainingAmountSql . ' as remaining_amount')
            ->where('detail.insurer_number', '<>', '99999999')
            ->whereRaw($remainingAmountSql . ' <> 0')
            ->where(function ($query): void {
                $query->whereNull('detail.is_uncollectible')
                    ->orWhere('detail.is_uncollectible', false)
                    ->orWhere('detail.is_uncollectible', 0);
            });

        if ($selectedStoreCategory !== '') {
            $rowsQuery->where('department.store_category', $selectedStoreCategory);
        }

        if ($selectedCompanyId !== '') {
            $rowsQuery->where('company.company_id', $selectedCompanyId);
        }

        $uncollectedRows = $rowsQuery
            ->orderBy('detail.treatment_month')
            ->orderBy('detail.insurer_number')
            ->orderBy('detail.insurance_claim_detail_id')
            ->get()
            ->map(fn($row): array => [
                'insurance_claim_detail_id' => (string) ($row->insurance_claim_detail_id ?? ''),
                'insurer_number' => trim((string) ($row->insurer_number ?? '')),
                'insurer_name' => trim((string) ($row->insurer_name ?? '')),
                'insurance_type' => trim((string) ($row->insurance_type ?? '')),
                'treatment_month' => $this->formatDateValue($row->treatment_month, 'Y/m/d'),
                'claim_amount' => $this->formatMoneyValue($row->claim_amount),
                'adjustment_amount' => $this->formatMoneyValue($row->adjustment_amount),
                'cash_collected_amount' => $this->formatMoneyValue($row->cash_collected_amount),
                'returned_amount' => $this->formatMoneyValue($row->returned_amount),
                'remarks' => trim((string) ($row->remarks ?? '')),
                'payment_date_text' => trim((string) ($row->payment_date_text ?? '')),
                'payment_amount' => $this->formatMoneyValue($row->payment_amount),
                'remaining_amount' => $this->formatMoneyValue($row->remaining_amount),
                'deposit_name' => trim((string) ($row->deposit_name ?? '')),
                'store_category' => trim((string) ($row->store_category ?? '')),
                'company_id' => trim((string) ($row->company_id ?? '')),
                'company_name' => trim((string) ($row->company_name ?? '')),
                'bank_account_selection' => trim((string) ($row->bank_account_selection ?? '')),
            ])
            ->all();

        return view('staff_portal.office.sales.uncollected.index', $this->commonViewData($request, [
            'paymentMonth' => $paymentMonth,
            'selectedStoreCategory' => $selectedStoreCategory,
            'selectedCompanyId' => $selectedCompanyId,
            'storeCategoryOptions' => $this->receiptStoreOptions(),
            'companyOptions' => $companyOptions,
            'uncollectedRows' => $uncollectedRows,
        ]));
    }


    // 未収入金一覧　月別印刷
    public function uncollectedReceivablesPrint(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);

        $paymentMonth = trim((string) $request->query('payment_month', now()->format('Y-m')));
        $selectedStoreCategory = trim((string) $request->query('store_category', ''));
        $selectedCompanyId = trim((string) $request->query('company_id', ''));

        try {
            $monthEnd = Carbon::createFromFormat('Y-m', $paymentMonth)->endOfMonth();
        } catch (\Throwable) {
            $paymentMonth = now()->format('Y-m');
            $monthEnd = Carbon::createFromFormat('Y-m', $paymentMonth)->endOfMonth();
        }

        $remainingAmountSql = $this->remainingAmountSql();

        $rowsQuery = $this->receiptDetailBaseQuery()
            ->leftJoin('dbo.mx_journal_entries as payment_entry', function ($join): void {
                $join->whereRaw(
                    "LTRIM(RTRIM(CAST(detail.payment_date_text AS NVARCHAR(255)))) = LTRIM(RTRIM(CAST(payment_entry.journal_breakdown AS NVARCHAR(255))))"
                );
            })
            ->select([
                'detail.treatment_month',
                'department.store_category',
                'store.business_type',
                'company.company_id',
                'company.company_name',
            ])
            ->selectRaw('COUNT(*) as receipt_count')
            ->selectRaw('SUM(' . $remainingAmountSql . ') as remaining_amount')
            ->where('detail.insurer_number', '<>', '99999999')
            ->whereDate('detail.treatment_month', '<=', $monthEnd->format('Y-m-d'))
            ->whereNotNull('department.store_category')
            ->where(function ($query): void {
                $query->whereNull('detail.is_uncollectible')
                    ->orWhere('detail.is_uncollectible', false)
                    ->orWhere('detail.is_uncollectible', 0);
            })
            ->where(function ($query) use ($monthEnd): void {
                $query->whereNull('payment_entry.occurred_at')
                    ->orWhereDate('payment_entry.occurred_at', '>', $monthEnd->format('Y-m-d'));
            });

        if ($selectedStoreCategory !== '') {
            $rowsQuery->where('department.store_category', $selectedStoreCategory);
        }

        if ($selectedCompanyId !== '') {
            $rowsQuery->where('company.company_id', $selectedCompanyId);
        }

        $printRows = $rowsQuery
            ->groupBy([
                'detail.treatment_month',
                'department.store_category',
                'store.business_type',
                'company.company_id',
                'company.company_name',
            ])
            ->havingRaw('SUM(' . $remainingAmountSql . ') <> 0')
            ->orderBy('company.company_name')
            ->orderBy('department.store_category')
            ->orderBy('detail.treatment_month')
            ->get()
            ->map(fn($row): array => [
                'company_id' => trim((string) ($row->company_id ?? '')),
                'company_name' => trim(str_replace('株式会社', '', (string) ($row->company_name ?? ''))),
                'store_category' => trim((string) ($row->store_category ?? '')),
                'store_label' => trim((string) ($row->business_type ?? '')) !== '（店舗）' ? '【' . trim((string) ($row->store_category ?? '')) . '】' : '',
                'treatment_month' => $this->formatDateValue($row->treatment_month, 'Y/m'),
                'receipt_count' => (int) ($row->receipt_count ?? 0),
                'remaining_amount' => (float) ($row->remaining_amount ?? 0),
                'remaining_amount_display' => $this->formatMoneyValue($row->remaining_amount),
            ])
            ->all();

        return view('staff_portal.office.sales.uncollected.print', [
            'paymentMonth' => $paymentMonth,
            'monthEnd' => $monthEnd->format('Y/m/d'),
            'printRows' => $printRows,
            'grandTotal' => array_sum(array_map(static fn(array $row): float => (float) $row['remaining_amount'], $printRows)),
        ]);
    }



    // 未収入金一覧　個別印刷
    public function uncollectedReceivablesDetailPrint(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);

        $paymentMonth = trim((string) $request->query('payment_month', now()->format('Y-m')));
        $selectedStoreCategory = trim((string) $request->query('store_category', ''));
        $selectedCompanyId = trim((string) $request->query('company_id', ''));

        try {
            $monthEnd = Carbon::createFromFormat('Y-m', $paymentMonth)->endOfMonth();
        } catch (\Throwable) {
            $paymentMonth = now()->format('Y-m');
            $monthEnd = Carbon::createFromFormat('Y-m', $paymentMonth)->endOfMonth();
        }

        $remainingAmountSql = $this->remainingAmountSql();

        $rowsQuery = $this->receiptDetailBaseQuery()
            ->leftJoin('dbo.mx_journal_entries as payment_entry', function ($join): void {
                $join->whereRaw(
                    "LTRIM(RTRIM(CAST(detail.payment_date_text AS NVARCHAR(255)))) = LTRIM(RTRIM(CAST(payment_entry.journal_breakdown AS NVARCHAR(255))))"
                );
            })
            ->select([
                'detail.treatment_month',
                'detail.insurer_number',
                'insurer.insurer_name',
                'detail.summary_category',
                'detail.total_amount',
                'detail.copayment_amount',
                'detail.claim_amount',
                'detail.cash_collected_amount',
                'detail.adjustment_amount',
                'detail.returned_amount',
                'detail.payment_amount',
                'detail.remarks',
                'department.store_category',
                'company.company_id',
                'company.company_name',
            ])
            ->selectRaw($remainingAmountSql . ' as remaining_amount')
            ->where('detail.insurer_number', '<>', '99999999')
            ->whereDate('detail.treatment_month', '<=', $monthEnd->format('Y-m-d'))
            ->whereNotNull('department.store_category')
            ->where(function ($query): void {
                $query->whereNull('detail.is_uncollectible')
                    ->orWhere('detail.is_uncollectible', false)
                    ->orWhere('detail.is_uncollectible', 0);
            })
            ->where(function ($query) use ($monthEnd): void {
                $query->whereNull('payment_entry.occurred_at')
                    ->orWhereDate('payment_entry.occurred_at', '>', $monthEnd->format('Y-m-d'));
            });

        if ($selectedStoreCategory !== '') {
            $rowsQuery->where('department.store_category', $selectedStoreCategory);
        }

        if ($selectedCompanyId !== '') {
            $rowsQuery->where('company.company_id', $selectedCompanyId);
        }

        $printRows = $rowsQuery
            ->whereRaw($remainingAmountSql . ' <> 0')
            ->orderBy('company.company_name')
            ->orderBy('department.store_category')
            ->orderBy('detail.treatment_month')
            ->orderBy('detail.insurer_number')
            ->get()
            ->map(fn($row): array => [
                'company_id' => trim((string) ($row->company_id ?? '')),
                'company_name' => trim(str_replace('株式会社', '', (string) ($row->company_name ?? ''))),
                'store_category' => trim((string) ($row->store_category ?? '')),
                'treatment_month' => $this->formatDateValue($row->treatment_month, 'Y/m'),
                'insurer_number' => trim((string) ($row->insurer_number ?? '')),
                'insurer_name' => trim((string) ($row->insurer_name ?? '')),
                'summary_category' => trim((string) ($row->summary_category ?? '')),
                'total_amount' => $this->formatMoneyValue($row->total_amount),
                'copayment_amount' => $this->formatMoneyValue($row->copayment_amount),
                'claim_amount' => $this->formatMoneyValue($row->claim_amount),
                'cash_collected_amount' => $this->formatMoneyValue($row->cash_collected_amount),
                'adjustment_amount' => $this->formatMoneyValue($row->adjustment_amount),
                'returned_amount' => $this->formatMoneyValue($row->returned_amount),
                'payment_amount' => $this->formatMoneyValue($row->payment_amount),
                'remarks' => trim((string) ($row->remarks ?? '')),
                'remaining_amount' => (float) ($row->remaining_amount ?? 0),
            ])
            ->all();

        return view('staff_portal.office.sales.uncollected.detail_print', [
            'paymentMonth' => $paymentMonth,
            'monthEnd' => $monthEnd->format('Y/m/d'),
            'printRows' => $printRows,
            'grandTotal' => array_sum(array_map(static fn(array $row): float => (float) $row['remaining_amount'], $printRows)),
        ]);
    }

    public function receipt(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);

        return view('staff_portal.office.receipt.index', $this->commonViewData($request, []));
    }



    public function officeMenu(Request $request): RedirectResponse|View
    {
        $staffId = (string) session('staff_id', '');

        return view('staff_portal.office.office_menu.index', $this->commonViewData($request, []));
    }

    /**
     * 未収入金の残額計算式（正本）。uncollectedReceivables/uncollectedReceivablesPrint/
     * uncollectedReceivablesDetailPrintの3箇所に同じ文字列がそのままコピーされていたのを
     * ここ1箇所にまとめた。
     */
    private function remainingAmountSql(): string
    {
        return '(COALESCE(detail.claim_amount, 0) + COALESCE(detail.returned_amount, 0) + COALESCE(detail.adjustment_amount, 0) - COALESCE(detail.payment_amount, 0))';
    }
}

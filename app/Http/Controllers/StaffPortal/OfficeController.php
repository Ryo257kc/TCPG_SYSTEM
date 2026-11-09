<?php

namespace App\Http\Controllers\StaffPortal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use App\Services\Admin\V2\Master\CompanyV2Service;
use App\Services\Admin\V2\Sales\SalesV2Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OfficeController extends Controller
{
    use HandlesStaffPortalContext;

    public function __construct(
        private readonly CompanyV2Service $companyService,
        private readonly SalesV2Service $salesService,
    ) {}

    public function salesMenu(Request $request): RedirectResponse|View
    {
        $this->requirePaymentCheck($request);

        return view('staff_portal.office.sales.index', $this->commonViewData($request, [
            'companyOptions' => $this->companyOptions(),
        ]));
    }

    // 売上一覧
    public function sales(Request $request): RedirectResponse|View
    {
        $this->requirePaymentCheck($request);

        $targetMonth = trim((string) $request->query('target_month', now()->format('Y-m')));
        $companyId = trim((string) $request->query('company_id', ''));
        $summary = $this->salesService->summary($targetMonth, $companyId);

        return view('staff_portal.office.sales.index', $this->commonViewData($request, [
            'companyOptions' => $this->companyOptions(),
            'salesRows' => $summary['rows'],
            'targetMonth' => $summary['target_month'],
            'selectedCompanyId' => $summary['company_id'],
            'grandTotal' => $summary['grand_total'],
        ]));
    }

    public function salesPrint(Request $request): RedirectResponse|View
    {
        $this->requirePaymentCheck($request);

        $companyOptions = $this->companyOptions();

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
            'rows' => $summary['rows'],
            'targetMonth' => $summary['target_month'],
            'selectedCompanyId' => $summary['company_id'],
            'grandTotal' => $summary['grand_total'],
            'companyName' => $companyName,
        ]);
    }

    // 未収入金一覧
    public function uncollectedReceivables(Request $request): RedirectResponse|View
    {
        return view('staff_portal.office.sales.uncollected.index', $this->uncollectedReceivablesData($request));
    }

    public function returnsPrint(Request $request): View
    {
        return view('staff_portal.office.sales.uncollected.returns_print', $this->uncollectedReceivablesData($request, true));
    }

    public function returnsCsv(Request $request): StreamedResponse
    {
        abort_unless($this->isAdmin($this->staffPortalStaffRow($this->staffPortalStaffId($request))), 403);
        $request->validate([
            'payment_month' => ['required', 'date_format:Y-m'],
            'return_status' => ['required', 'in:processed'],
            'company_id' => ['required', 'string', 'max:50'],
        ], [
            'company_id.required' => '仕訳CSVは会社ごとに出力します。会社を選択してください。',
            'return_status.in' => '仕訳CSVは処理済みの明細から出力してください。',
        ]);
        $data = $this->uncollectedReceivablesData($request, true);
        $company = collect($data['companyOptions'])->firstWhere('company_id', $data['selectedCompanyId']);
        if ($company === null || $data['uncollectedRows'] === []) {
            throw ValidationException::withMessages(['company_id' => '選択した会社・決算月に、出力対象の処理済み明細がありません。']);
        }
        $date = Carbon::createFromFormat('!Y-m', $data['paymentMonth'])->endOfMonth();
        $csvRows = [];
        foreach ($data['uncollectedRows'] as $row) {
            if ($row['store_category'] === '') {
                throw ValidationException::withMessages(['company_id' => '店舗名が未設定の明細があるため、仕訳CSVを出力できません。']);
            }
            $negative = (float) $row['returned_amount_value'] < 0;
            $amount = ltrim($row['returned_amount_value'], '+-');
            if (str_contains($amount, '.')) $amount = rtrim(rtrim($amount, '0'), '.');
            $csvRows[] = [
                $date->format('Y/n/j'), 1,
                $negative ? '保険収入' : '医療未収入金', $row['store_category'], $negative ? '返戻' : '',
                $amount, $negative ? '非課売上' : '対象外', 0,
                $negative ? '医療未収入金' : '保険収入', $row['store_category'], $negative ? '' : '返戻',
                $amount, $negative ? '対象外' : '非課売上', 0,
                Carbon::parse($row['treatment_month'])->format('n') . '月売上',
            ];
        }
        $companyName = str_replace(['株式会社', '\\', '/', ':', '*', '?', '"', '<', '>', '|'], ['', '_', '_', '_', '_', '_', '_', '_', '_', '_'], $company['company_name']);
        $filename = $date->format('Y.n') . '-返戻-' . $companyName . '.csv';
        return response()->streamDownload(function () use ($csvRows): void {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF"); // ExcelでUTF-8として判別させる。
            fputcsv($stream, ['日付', '伝票番号', '借方勘定科目', '借方部門', '借方品目', '借方金額', '借方税区分', '借方税額', '貸方勘定科目', '貸方部門', '貸方品目', '貸方金額', '貸方税区分', '貸方税額', '摘要'], ',', '"', '', "\r\n");
            foreach ($csvRows as $row) fputcsv($stream, $row, ',', '"', '', "\r\n");
            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    private function uncollectedReceivablesData(Request $request, bool $forceReturns = false): array
    {
        $this->requirePaymentCheck($request);
        $canProcessReturns = $this->isAdmin($this->staffPortalStaffRow($this->staffPortalStaffId($request)));
        $isReturnTab = $forceReturns || $request->query('tab') === 'returns';
        abort_if($isReturnTab && !$canProcessReturns, 403);
        $returnStatus = 'unprocessed';
        if ($isReturnTab) {
            $filters = $request->validate([
                'return_status' => ['sometimes', 'in:unprocessed,processed'],
                'payment_month' => ['sometimes', 'required', 'date_format:Y-m'],
            ]);
            $returnStatus = $filters['return_status'] ?? 'unprocessed';
        }

        $paymentMonth = trim((string) $request->query('payment_month', now()->format('Y-m')));
        $selectedStoreCategory = trim((string) $request->query('store_category', ''));
        $selectedCompanyId = trim((string) $request->query('company_id', ''));

        $companyOptions = $this->companyOptions();

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
            ->where('detail.insurer_number', '<>', '99999999');

        if ($isReturnTab) {
            $rowsQuery->addSelect(['detail.is_uncollectible', 'detail.returned_journal_processed_at'])
                ->whereRaw('COALESCE(detail.returned_amount, 0) <> 0')
                ->where('detail.treatment_month', '<', Carbon::createFromFormat('!Y-m', $paymentMonth)->addMonth()->format('Y-m-d'));
            if ($returnStatus === 'unprocessed') {
                $rowsQuery->whereNull('detail.returned_journal_processed_at');
            } elseif ($returnStatus === 'processed') {
                $rowsQuery->whereNotNull('detail.returned_journal_processed_at');
            }
            if ($returnStatus === 'processed') {
                $start = Carbon::createFromFormat('!Y-m', $paymentMonth);
                $rowsQuery->where('detail.returned_journal_processed_at', '>=', $start->copy()->endOfMonth()->format('Y-m-d'))
                    ->where('detail.returned_journal_processed_at', '<', $start->copy()->addMonth()->format('Y-m-d'));
            }
        } else {
            $rowsQuery->whereRaw($remainingAmountSql . ' <> 0')->where(function ($query): void {
                $query->whereNull('detail.is_uncollectible')
                    ->orWhere('detail.is_uncollectible', false)
                    ->orWhere('detail.is_uncollectible', 0);
            });
        }

        if ($selectedStoreCategory !== '') {
            $rowsQuery->where('department.store_category', $selectedStoreCategory);
        }

        if ($selectedCompanyId !== '') {
            $rowsQuery->where('company.company_id', $selectedCompanyId);
        }

        $records = $rowsQuery
            ->orderBy('detail.treatment_month')
            ->orderBy('detail.insurer_number')
            ->orderBy('detail.insurance_claim_detail_id')
            ->get();
        $returnTotals = [];
        if ($isReturnTab) {
            foreach (['claim_amount', 'adjustment_amount', 'returned_amount'] as $column) {
                $returnTotals[$column] = $this->formatMoneyValue($records->sum(fn($row): float => (float) ($row->{$column} ?? 0)));
            }
        }
        $uncollectedRows = $records
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
                'returned_amount_value' => (string) ($row->returned_amount ?? 0),
                'is_uncollectible' => (bool) ($row->is_uncollectible ?? false),
                'returned_journal_processed_at' => $this->formatDateValue($row->returned_journal_processed_at ?? null, 'Y/m/d'),
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

        return $this->commonViewData($request, [
            'canProcessReturns' => $canProcessReturns,
            'isReturnTab' => $isReturnTab,
            'returnStatus' => $returnStatus,
            'returnTotals' => $returnTotals,
            'paymentMonth' => $paymentMonth,
            'selectedStoreCategory' => $selectedStoreCategory,
            'selectedCompanyId' => $selectedCompanyId,
            'storeCategoryOptions' => $this->receiptStoreOptions(),
            'companyOptions' => $companyOptions,
            'uncollectedRows' => $uncollectedRows,
        ]);
    }


    // 管理者の決算用返戻処理日登録。売上仕訳・返戻額は更新しない。
    public function saveReturnProcessingDate(Request $request): RedirectResponse
    {
        abort_unless($this->isAdmin($this->staffPortalStaffRow($this->staffPortalStaffId($request))), 403);
        $values = $request->validate([
            'detail_ids' => ['required', 'array', 'min:1', 'max:2000'],
            'detail_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'payment_month' => ['required', 'date_format:Y-m'],
            'return_status' => ['required', 'in:unprocessed,processed'],
            'company_id' => ['nullable', 'string', 'max:50'],
            'store_category' => ['nullable', 'string', 'max:50'],
        ], [
            'detail_ids.required' => '処理日を登録する明細にチェックを入れてください。',
            'payment_month.required' => '決算月を指定してください。',
        ]);
        $ids = array_map('intval', $values['detail_ids']);
        $date = Carbon::createFromFormat('!Y-m', $values['payment_month'])->endOfMonth()->format('Y-m-d');
        $nextMonth = Carbon::createFromFormat('!Y-m', $values['payment_month'])->addMonth()->format('Y-m-d');
        DB::connection('sqlsrv')->transaction(function () use ($ids, $date, $nextMonth): void {
            $query = DB::connection('sqlsrv')->table('dbo.mx_insurance_claim_details')
                ->whereIn('insurance_claim_detail_id', $ids)
                ->where('insurer_number', '<>', '99999999')
                ->where('treatment_month', '<', $nextMonth)
                ->whereRaw('COALESCE(returned_amount, 0) <> 0');
            if ((clone $query)->lockForUpdate()->get(['insurance_claim_detail_id'])->count() !== count($ids)) {
                throw ValidationException::withMessages(['detail_ids' => '対象の明細が変更されています。画面を更新してから選択し直してください。']);
            }
            $query->update(['returned_journal_processed_at' => $date]);
        });

        return redirect()->route('office.sales.uncollected', [
            'tab' => 'returns', 'payment_month' => $values['payment_month'],
            'return_status' => $values['return_status'],
            'company_id' => $values['company_id'] ?? '', 'store_category' => $values['store_category'] ?? '',
        ])->with('statusMessage', count($ids) . '件の返戻仕訳処理日を' . $date . 'で保存しました。');
    }

    // 未収入金一覧　月別印刷
    public function uncollectedReceivablesPrint(Request $request): RedirectResponse|View
    {
        $this->requirePaymentCheck($request);

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
        $this->requirePaymentCheck($request);

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
        $this->requirePaymentCheck($request);

        return view('staff_portal.office.receipt.index', $this->commonViewData($request, []));
    }

    public function officeMenu(Request $request): RedirectResponse|View
    {
        $this->requirePaymentCheck($request);

        return view('staff_portal.office.office_menu.index', $this->commonViewData($request, []));
    }

    // 事務所（isPaymentCheck||isAdmin）権限が必要な画面共通のガード。
    // ダッシュボードのメニューでは非表示にしていたが、コントローラー側で
    // URLを直接叩かれた場合の制限が無かった（2026-08-23発覚）。
    private function requirePaymentCheck(Request $request): void
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        if (!$this->isPaymentCheck($this->staffPortalStaffRow($staffId))) {
            abort(403);
        }
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

    /**
     * 会社選択肢（正本）。salesMenu/sales/salesPrint/uncollectedReceivablesの4箇所に
     * 同じ組み立て処理がそのままコピーされていたのをここ1箇所にまとめた。
     *
     * @return list<array{company_id: string, company_name: string}>
     */
    private function companyOptions(): array
    {
        $companyRows = $this->companyService->list('')['rows'] ?? [];

        return array_values(array_filter(array_map(
            static fn(array $row): array => [
                'company_id' => trim((string) ($row['company_id'] ?? '')),
                'company_name' => trim((string) ($row['company_name'] ?? '')),
            ],
            $companyRows
        ), static fn(array $row): bool => $row['company_id'] !== '' && $row['company_name'] !== ''));
    }
}

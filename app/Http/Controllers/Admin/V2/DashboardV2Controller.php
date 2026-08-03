<?php

namespace App\Http\Controllers\Admin\V2;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\Master\CompanyV2Service;
use App\Services\Admin\V2\Sales\SalesV2Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class DashboardV2Controller extends Controller
{
    public function __construct(
        private readonly CompanyV2Service $companyService,
        private readonly SalesV2Service $salesService,
    ) {}

    public function index(Request $request): View
    {
        $requestedPage = (string) $request->query('page', 'home');
        return $this->renderPage($requestedPage);
    }

    public function bonus(): View
    {
        return $this->renderPage('bonus-detail');
    }

    public function sales(Request $request): View
    {
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

        return view('admin_v2.sales.index', [
            'companyOptions' => $companyOptions,
            'salesRows' => $summary['rows'],
            'companyTotals' => $summary['company_totals'],
            'targetMonth' => $summary['target_month'],
            'selectedCompanyId' => $summary['company_id'],
            'grandTotal' => $summary['grand_total'],
        ]);
    }

    public function salesPdf(Request $request): View
    {
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

        return view('admin_v2.sales.print', [
            'stores' => $summary['stores'],
            'targetMonth' => $summary['target_month'],
            'selectedCompanyId' => $summary['company_id'],
            'grandTotal' => $summary['grand_total'],
            'companyName' => $companyName,
        ]);
    }


    public function salesCsv(Request $request): Response
    {
        $targetMonth = trim((string) $request->query('target_month', now()->format('Y-m')));
        $companyId = trim((string) $request->query('company_id', ''));
        $csv = $this->salesService->freeeJournalCsv($targetMonth, $companyId);
        $yyyymm = str_replace('-', '', $csv['target_month']);
        $downloadName = 'TC_freee振伝_売上' . $yyyymm . '.csv';
        $fallbackName = 'TC_freee_sales_' . $yyyymm . '.csv';

        return response($csv['content'], 200, [
            'Content-Type' => 'text/csv; charset=Shift_JIS',
            'Content-Disposition' => "attachment; filename=\"{$fallbackName}\"; filename*=UTF-8''" . rawurlencode($downloadName),
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function masterCompany(): View
    {
        return $this->renderPage('master-company');
    }

    public function masterStaff(): View
    {
        return $this->renderPage('master-staff');
    }

    public function masterStore(): View
    {
        return $this->renderPage('master-store');
    }

    public function masterAllowance(): View
    {
        return $this->renderPage('master-allowance');
    }

    private function renderPage(string $pageKey): View
    {
        return view('admin_v2.dashboard.index', [
            'requestedPage' => $pageKey,
            'informationMessages' => $this->informationMessages(),
            'needsCorrection' => $this->needsCorrection(),
        ]);
    }

    private function informationMessages(): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_message')
            ->select(['mesa_no', 'mase_date', 'title', 'contents'])
            ->whereIn('destination', ['info', 'All'])
            ->where(function ($query): void {
                $query->whereNull('me_check')
                    ->orWhere('me_check', 0);
            })
            ->orderByDesc('mase_date')
            ->orderByDesc('mesa_no')
            ->get()
            ->map(function ($row): array {
                $messageDate = '';

                try {
                    $messageDate = $row->mase_date ? Carbon::parse($row->mase_date)->format('Y/m/d') : '';
                } catch (\Throwable) {
                    $messageDate = trim((string) ($row->mase_date ?? ''));
                }

                return [
                    'mase_date' => $messageDate,
                    'title' => trim((string) ($row->title ?? '')),
                    'contents' => trim((string) ($row->contents ?? '')),
                ];
            })
            ->filter(fn(array $row): bool => $row['title'] !== '' || $row['contents'] !== '')
            ->values()
            ->all();
    }

    private function needsCorrection(): bool
    {
        $staffId = (string) (session('staff_id') ?? '');

        if ($staffId === '') {
            return false;
        }

        return DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards')
            ->whereRaw('LTRIM(RTRIM([staff_name])) = ?', [$staffId])
            ->where('is_returned', 1)
            ->where('manager_name', 0)
            ->exists();
    }
}

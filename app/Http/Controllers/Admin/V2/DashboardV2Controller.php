<?php

namespace App\Http\Controllers\Admin\V2;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\Master\CompanyV2Service;
use App\Services\Admin\V2\Sales\SalesV2Service;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
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

        return view('staff_portal.office.sales.print', [
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
            'returnedAttendanceStaff' => $this->returnedAttendanceStaffNames(),
            'paidLeaveGrantTargetStaff' => $this->paidLeaveGrantTargetStaffNames(),
            'kaigoInsuranceStartTargetStaff' => $this->kaigoInsuranceStartTargetStaffNames(),
        ]);
    }

    /** @return list<string> */
    private function returnedAttendanceStaffNames(): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards as tc')
            ->leftJoin('dbo.mx_staffs as s', DB::raw('LTRIM(RTRIM(tc.staff_name))'), '=', DB::raw('LTRIM(RTRIM(s.staff_id))'))
            ->where('tc.is_returned', 1)
            ->selectRaw("DISTINCT LTRIM(RTRIM(COALESCE(s.staff_name, tc.staff_name, ''))) as staff_name")
            ->pluck('staff_name')
            ->map(static fn($name): string => self::compactStaffName((string) $name))
            ->filter(static fn(string $name): bool => $name !== '')
            ->values()
            ->all();
    }

    // 今月が有休加算月（yukyu_month）に該当する在職スタッフ。
    /** @return list<string> */
    private function paidLeaveGrantTargetStaffNames(): array
    {
        $currentMonth = (string) (int) now('Asia/Tokyo')->format('n');

        return DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->where('yukyu', 1)
            ->where('employment', '在職')
            ->whereRaw('LTRIM(RTRIM(yukyu_month)) = ?', [$currentMonth])
            ->selectRaw("LTRIM(RTRIM(staff_name)) as staff_name")
            ->pluck('staff_name')
            ->map(static fn($name): string => self::compactStaffName((string) $name))
            ->filter(static fn(string $name): bool => $name !== '')
            ->values()
            ->all();
    }

    // 今月から介護保険料の徴収が始まる（40歳到達の前日を含む月）在職スタッフ。
    // 判定式は PayrollV2SocialInsuranceAmountService::shouldApplyKaigo() の起点計算と揃える。
    /** @return list<string> */
    private function kaigoInsuranceStartTargetStaffNames(): array
    {
        $now = now('Asia/Tokyo');
        $monthStart = $now->copy()->startOfMonth()->format('Y-m-d');
        $monthEnd = $now->copy()->startOfMonth()->addMonthNoOverflow()->format('Y-m-d');

        return DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->where('employment', '在職')
            ->whereNotNull('birthday')
            ->whereRaw('DATEADD(day, -1, DATEADD(year, 40, birthday)) >= ?', [$monthStart])
            ->whereRaw('DATEADD(day, -1, DATEADD(year, 40, birthday)) < ?', [$monthEnd])
            ->selectRaw("LTRIM(RTRIM(staff_name)) as staff_name")
            ->pluck('staff_name')
            ->map(static fn($name): string => self::compactStaffName((string) $name))
            ->filter(static fn(string $name): bool => $name !== '')
            ->values()
            ->all();
    }

    // 一覧表示用：staff_name内の姓名区切りの空白（全角・半角）を除いて詰めて表示する。
    private static function compactStaffName(string $name): string
    {
        return str_replace(["\u{3000}", ' '], '', trim($name));
    }
}

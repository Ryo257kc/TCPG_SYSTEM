<?php

namespace App\Http\Controllers\StaffPortal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use App\Services\Admin\V2\Payroll\PayrollV2SalesImportService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StoreSalesController extends Controller
{
    use HandlesStaffPortalContext;

    public function __construct(
        private readonly PayrollV2SalesImportService $salesImportService,
    ) {}

    public function index(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);

        return view('staff_portal.admin.sales.index', $this->commonViewData($request, []));
    }

    public function personal(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);

        return view('staff_portal.admin.sales.personal', $this->commonViewData($request, $this->personalSalesViewData($request)));
    }

    public function consignment(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);

        return view('staff_portal.admin.sales.consignment', $this->commonViewData($request, $this->consignmentSalesViewData($request)));
    }

    public function daily(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);

        return view('staff_portal.admin.sales.daily', $this->commonViewData($request, $this->dailySalesViewData($request)));
    }

    public function monthly(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);

        return view('staff_portal.admin.sales.monthly', $this->commonViewData($request, $this->monthlySalesViewData($request)));
    }

    private function emptyMonthlySalesViewDataForMonth(string $selectedMonth): array
    {
        return [
            'selectedMonth' => $selectedMonth,
            'selectedStaffId' => '',
            'selectedStaffName' => '',
            'staffOptions' => $this->salesStaffOptions($selectedMonth),
            'hasSearched' => false,
            'errorMessage' => '',
            'rowCount' => 0,
            'rows' => collect(),
            'storeRows' => collect(),
            'houseCall' => ['total' => 0],
            'storeTotals' => [
                'insurance' => 0,
                'private' => 0,
                'total' => 0,
            ],
            'totals' => [
                'insurance_count' => 0,
                'insurance_total' => 0,
                'insurance_share' => 0,
                'private_count' => 0,
                'private_total' => 0,
                'private_share' => 0,
                'grand' => 0,
            ],
        ];
    }

    private function personalSalesViewData(Request $request): array
    {
        $selectedMonth = trim((string) $request->query('month', now()->format('Y-m')));
        if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = now()->format('Y-m');
        }

        $selectedStaffId = trim((string) $request->query('staff_id', ''));
        $viewData = $this->emptyMonthlySalesViewDataForMonth($selectedMonth);
        $viewData['selectedStaffId'] = $selectedStaffId;
        $viewData['hasSearched'] = $selectedStaffId !== '';

        if ($selectedStaffId === '') {
            return $viewData;
        }

        foreach ($viewData['staffOptions'] as $staff) {
            if ((string) ($staff['staff_id'] ?? '') === $selectedStaffId) {
                $viewData['selectedStaffName'] = (string) ($staff['staff_name'] ?? '');
                break;
            }
        }

        $monthStart = Carbon::createFromFormat('Y-m-d', $selectedMonth . '-01')->startOfDay();
        $monthEnd = $monthStart->copy()->addMonth();
        $staffIds = $this->staffIdVariants($selectedStaffId);

        $storeRows = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_患者名日報 as patient')
            ->join('dbo.T_先生別日報 as teacher', 'patient.患者No', '=', 'teacher.患者No_t')
            ->selectRaw("
                LTRIM(RTRIM(COALESCE(patient.[店舗], N''))) as store_name,
                SUM(ISNULL(teacher.[請求金額], 0)) as insurance_amount,
                SUM(ISNULL(teacher.[自費], 0)) as private_amount
            ")
            ->where('patient.日付', '>=', $monthStart->toDateString())
            ->where('patient.日付', '<', $monthEnd->toDateString())
            ->where(function ($query): void {
                $query->whereNull('teacher.先生別外')
                    ->orWhere('teacher.先生別外', 0);
            })
            ->whereIn(DB::raw("LTRIM(RTRIM(CAST(teacher.[担当者ID] as nvarchar(20))))"), $staffIds)
            ->groupByRaw("LTRIM(RTRIM(COALESCE(patient.[店舗], N'')))")
            ->orderByRaw("LTRIM(RTRIM(COALESCE(patient.[店舗], N'')))")
            ->get()
            ->map(function ($row): array {
                $insuranceAmount = $this->num($row->insurance_amount ?? 0);
                $privateAmount = $this->num($row->private_amount ?? 0);

                return [
                    'store_name' => trim((string) ($row->store_name ?? '')),
                    'insurance_amount' => $insuranceAmount,
                    'private_amount' => $privateAmount,
                    'total_amount' => $insuranceAmount + $privateAmount,
                ];
            });

        $menuRows = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_先生別日報 as teacher')
            ->join('dbo.T_患者名日報 as patient', 'patient.患者No', '=', 'teacher.患者No_t')
            ->join('dbo.T_施術メニュー as menu', 'menu.メニュー', '=', 'teacher.メニュー')
            ->selectRaw("
                teacher.[メニュー] as menu_name,
                LTRIM(RTRIM(COALESCE(patient.[店舗], N''))) as store_name,
                COUNT(patient.[患者No]) as visit_count,
                SUM(ISNULL(teacher.[自費], 0)) as private_total,
                SUM(CASE WHEN patient.[店舗] = N'さくら鍼灸整骨院' THEN ISNULL(teacher.[自費], 0) ELSE 0 END) as sakura_incentive_total,
                SUM(CASE WHEN patient.[店舗] = N'ひなた鍼灸整骨院' THEN ISNULL(teacher.[自費], 0) ELSE 0 END) as hinata_incentive_total
            ")
            ->where('patient.日付', '>=', $monthStart->toDateString())
            ->where('patient.日付', '<', $monthEnd->toDateString())
            ->whereNotNull('teacher.メニュー')
            ->whereNotNull('menu.歩合割合')
            ->where(function ($query): void {
                $query->whereNull('teacher.先生別外')
                    ->orWhere('teacher.先生別外', 0);
            })
            ->whereIn(DB::raw("LTRIM(RTRIM(CAST(teacher.[担当者ID] as nvarchar(20))))"), $staffIds)
            ->groupByRaw("teacher.[メニュー], LTRIM(RTRIM(COALESCE(patient.[店舗], N'')))")
            ->orderByRaw("LTRIM(RTRIM(COALESCE(patient.[店舗], N'')))")
            ->orderBy('teacher.メニュー')
            ->get()
            ->map(fn($row): array => [
                'menu_name' => trim((string) ($row->menu_name ?? '')),
                'visit_count' => (int) $this->num($row->visit_count ?? 0),
                'private_total' => $this->num($row->private_total ?? 0),
                'sakura_incentive_total' => $this->num($row->sakura_incentive_total ?? 0),
                'hinata_incentive_total' => $this->num($row->hinata_incentive_total ?? 0),
                'store_name' => trim((string) ($row->store_name ?? '')),
            ]);

        $viewData['storeRows'] = $storeRows;
        $viewData['rows'] = $menuRows;
        $viewData['rowCount'] = $storeRows->count();
        $viewData['storeTotals'] = [
            'insurance' => $storeRows->sum('insurance_amount'),
            'private' => $storeRows->sum('private_amount'),
            'total' => $storeRows->sum('total_amount'),
        ];

        $payrollMonth = $monthStart->copy()->addMonth();
        $homeVisitRows = $this->salesImportService->totalsByStaff(
            [$selectedStaffId],
            (int) $payrollMonth->format('Y'),
            (int) $payrollMonth->format('m')
        );
        $homeVisitRow = $homeVisitRows[$selectedStaffId] ?? null;
        $viewData['houseCall'] = [
            'total' => $homeVisitRow !== null ? $this->salesImportService->homeVisitSalesTotal($homeVisitRow) : 0,
        ];

        return $viewData;
    }

    private function monthlySalesViewData(Request $request): array
    {
        $selectedMonth = trim((string) $request->query('month', now()->format('Y-m')));
        if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = now()->format('Y-m');
        }

        $selectedStaffId = trim((string) $request->query('staff_id', ''));
        $viewData = $this->emptyMonthlySalesViewDataForMonth($selectedMonth);
        $viewData['selectedStaffId'] = $selectedStaffId;
        $viewData['hasSearched'] = $selectedStaffId !== '';

        if ($selectedStaffId === '') {
            return $viewData;
        }

        foreach ($viewData['staffOptions'] as $staff) {
            if ((string) ($staff['staff_id'] ?? '') === $selectedStaffId) {
                $viewData['selectedStaffName'] = (string) ($staff['staff_name'] ?? '');
                break;
            }
        }

        $monthStart = Carbon::createFromFormat('Y-m-d', $selectedMonth . '-01')->startOfDay();
        $monthEnd = $monthStart->copy()->addMonth();
        $staffIds = $this->staffIdVariants($selectedStaffId);

        $rows = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_患者名日報 as patient')
            ->join('dbo.T_先生別日報 as teacher', 'patient.患者No', '=', 'teacher.患者No_t')
            ->selectRaw("
                CONVERT(date, patient.[日付]) as sales_date,
                LTRIM(RTRIM(COALESCE(patient.[店舗], N''))) as store_name,
                COUNT(DISTINCT CASE WHEN (ISNULL(teacher.[請求金額], 0) + ISNULL(teacher.[保険負担], 0)) <> 0 THEN patient.[患者No] END) as insurance_count,
                SUM(ISNULL(teacher.[請求金額], 0)) as insurance_total,
                SUM(CASE WHEN patient.[割合] = N'自' THEN 1 ELSE 0 END) as private_count,
                SUM(ISNULL(teacher.[自費], 0)) as private_total
            ")
            ->where('patient.日付', '>=', $monthStart->toDateString())
            ->where('patient.日付', '<', $monthEnd->toDateString())
            ->where(function ($query): void {
                $query->whereNull('teacher.先生別外')
                    ->orWhere('teacher.先生別外', 0);
            })
            ->whereIn(DB::raw("LTRIM(RTRIM(CAST(teacher.[担当者ID] as nvarchar(20))))"), $staffIds)
            ->groupByRaw("CONVERT(date, patient.[日付]), LTRIM(RTRIM(COALESCE(patient.[店舗], N'')))")
            ->orderByRaw('CONVERT(date, patient.[日付])')
            ->orderByRaw("LTRIM(RTRIM(COALESCE(patient.[店舗], N'')))")
            ->get()
            ->map(function ($row): array {
                $insuranceTotal = $this->num($row->insurance_total ?? 0);
                $privateTotal = $this->num($row->private_total ?? 0);

                return [
                    'date_label' => $this->dateLabelWithWeekday($row->sales_date ?? null),
                    'insurance_count' => (int) $this->num($row->insurance_count ?? 0),
                    'insurance_total' => $insuranceTotal,
                    'private_count' => (int) $this->num($row->private_count ?? 0),
                    'private_total' => $privateTotal,
                    'store_name' => trim((string) ($row->store_name ?? '')),
                ];
            });

        $viewData['rows'] = $rows;
        $viewData['rowCount'] = $rows->count();
        $viewData['totals'] = [
            'insurance_count' => $rows->sum('insurance_count'),
            'insurance_total' => $rows->sum('insurance_total'),
            'insurance_share' => 0,
            'private_count' => $rows->sum('private_count'),
            'private_total' => $rows->sum('private_total'),
            'private_share' => 0,
            'grand' => $rows->sum('insurance_total') + $rows->sum('private_total'),
        ];

        return $viewData;
    }

    private function consignmentSalesViewData(Request $request): array
    {
        $selectedMonth = trim((string) $request->query('month', now()->format('Y-m')));
        if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = now()->format('Y-m');
        }

        $selectedStaffId = trim((string) $request->query('staff_id', ''));
        $monthStart = Carbon::createFromFormat('Y-m-d', $selectedMonth . '-01')->startOfDay();
        $monthEnd = $monthStart->copy()->addMonth();
        $staffOptions = $this->consignmentStaffOptions($monthStart);
        $viewData = $this->emptyMonthlySalesViewDataForMonth($selectedMonth);
        $viewData['staffOptions'] = $staffOptions;
        $viewData['selectedStaffId'] = $selectedStaffId;
        $viewData['hasSearched'] = $selectedStaffId !== '';

        if ($selectedStaffId === '') {
            return $viewData;
        }

        foreach ($staffOptions as $staff) {
            if ((string) ($staff['staff_id'] ?? '') === $selectedStaffId) {
                $viewData['selectedStaffName'] = (string) ($staff['staff_name'] ?? '');
                break;
            }
        }

        $staffIds = $this->staffIdVariants($selectedStaffId);
        $rows = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_施術メニュー as menu')
            ->join('dbo.T_先生別日報 as teacher', 'menu.メニュー', '=', 'teacher.メニュー')
            ->join('dbo.T_患者名日報 as patient', 'patient.患者No', '=', 'teacher.患者No_t')
            ->selectRaw("
                CONVERT(date, patient.[日付]) as sales_date,
                teacher.[メニュー] as menu_name,
                SUM(ISNULL(teacher.[自費], 0)) as private_total,
                COUNT(patient.[患者No]) as patient_count,
                menu.[歩合割合] as commission_rate,
                COUNT(CASE WHEN ISNULL(teacher.[請求金額], 0) <> 0 THEN 1 ELSE NULL END) as insurance_count,
                SUM(ISNULL(teacher.[請求金額], 0)) as claim_total
            ")
            ->where('patient.日付', '>=', $monthStart->toDateString())
            ->where('patient.日付', '<', $monthEnd->toDateString())
            ->whereIn(DB::raw('LTRIM(RTRIM(CAST(teacher.[担当者ID] as nvarchar(20))))'), $staffIds)
            ->whereNotNull('teacher.メニュー')
            ->whereNotNull('menu.歩合割合')
            ->where(function ($query): void {
                $query->whereNull('teacher.先生別外')
                    ->orWhere('teacher.先生別外', 0);
            })
            ->groupByRaw('CONVERT(date, patient.[日付]), teacher.[メニュー], menu.[歩合割合], teacher.[先生別外]')
            ->orderByRaw('CONVERT(date, patient.[日付])')
            ->orderBy('teacher.メニュー')
            ->get()
            ->map(function ($row): array {
                $insuranceCount = (int) $this->num($row->insurance_count ?? 0);
                $privateTotal = $this->num($row->private_total ?? 0);
                $insuranceShare = $insuranceCount * 700;
                $privateShare = $privateTotal / 2;

                return [
                    'date_label' => $this->dateLabelWithWeekday($row->sales_date ?? null),
                    'menu_name' => trim((string) ($row->menu_name ?? '')),
                    'insurance_count' => $insuranceCount,
                    'insurance_total' => $this->num($row->claim_total ?? 0),
                    'insurance_share' => $insuranceShare,
                    'private_count' => (int) $this->num($row->patient_count ?? 0),
                    'private_total' => $privateTotal,
                    'private_share' => $privateShare,
                ];
            });

        $viewData['rows'] = $rows;
        $viewData['rowCount'] = $rows->count();
        $viewData['totals'] = [
            'insurance_count' => $rows->sum('insurance_count'),
            'insurance_total' => $rows->sum('insurance_total'),
            'insurance_share' => $rows->sum('insurance_share'),
            'private_count' => $rows->sum('private_count'),
            'private_total' => $rows->sum('private_total'),
            'private_share' => $rows->sum('private_share'),
            'grand' => $rows->sum('insurance_share') + $rows->sum('private_share'),
        ];

        return $viewData;
    }

    private function dailySalesViewData(Request $request): array
    {
        $selectedDate = trim((string) $request->query('date', now()->format('Y-m-d')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            $selectedDate = now()->format('Y-m-d');
        }

        $selectedStaffId = trim((string) $request->query('staff_id', ''));
        $selectedMonth = substr($selectedDate, 0, 7);
        $viewData = [
            'selectedDate' => $selectedDate,
            'selectedDateLabel' => $this->dateLabelWithWeekday($selectedDate),
            'selectedStaffId' => $selectedStaffId,
            'selectedStaffName' => '',
            'staffOptions' => $this->salesStaffOptions($selectedDate),
            'hasSearched' => $selectedStaffId !== '',
            'errorMessage' => '',
            'rowCount' => 0,
            'rows' => collect(),
            'totals' => [
                'insurance' => 0,
                'private' => 0,
                'grand' => 0,
            ],
        ];

        if ($selectedStaffId === '') {
            return $viewData;
        }

        foreach ($viewData['staffOptions'] as $staff) {
            if ((string) ($staff['staff_id'] ?? '') === $selectedStaffId) {
                $viewData['selectedStaffName'] = (string) ($staff['staff_name'] ?? '');
                break;
            }
        }

        $staffIds = $this->staffIdVariants($selectedStaffId);
        $rows = DB::connection('sqlsrv_dailyreport')
            ->table('dbo.T_患者名日報 as patient')
            ->join('dbo.T_先生別日報 as teacher', 'patient.患者No', '=', 'teacher.患者No_t')
            ->selectRaw("
                CONVERT(time, patient.[時刻]) as at_time,
                patient.[患者名] as patient_name,
                LTRIM(RTRIM(COALESCE(patient.[店舗], N''))) as store_name,
                SUM(ISNULL(teacher.[請求金額], 0)) as insurance_amount,
                SUM(ISNULL(teacher.[自費], 0)) as private_amount
            ")
            ->whereDate('patient.日付', $selectedDate)
            ->where(function ($query): void {
                $query->whereNull('teacher.先生別外')
                    ->orWhere('teacher.先生別外', 0);
            })
            ->whereIn(DB::raw("LTRIM(RTRIM(CAST(teacher.[担当者ID] as nvarchar(20))))"), $staffIds)
            ->groupByRaw("CONVERT(time, patient.[時刻]), patient.[患者名], LTRIM(RTRIM(COALESCE(patient.[店舗], N'')))")
            ->orderByRaw("CONVERT(time, patient.[時刻])")
            ->orderBy('patient.患者名')
            ->get()
            ->map(fn($row): array => [
                'at_time' => $row->at_time !== null ? substr((string) $row->at_time, 0, 5) : '',
                'patient_name' => trim((string) ($row->patient_name ?? '')),
                'insurance_amount' => $this->num($row->insurance_amount ?? 0),
                'private_amount' => $this->num($row->private_amount ?? 0),
                'store_name' => trim((string) ($row->store_name ?? '')),
            ]);

        $viewData['rows'] = $rows;
        $viewData['rowCount'] = $rows->count();
        $viewData['totals'] = [
            'insurance' => $rows->sum('insurance_amount'),
            'private' => $rows->sum('private_amount'),
            'grand' => $rows->sum('insurance_amount') + $rows->sum('private_amount'),
        ];

        return $viewData;
    }

    /** @return list<string> */
    private function staffIdVariants(string $staffId): array
    {
        $trimmed = trim($staffId);
        $withoutZero = ltrim($trimmed, '0');

        return array_values(array_unique(array_filter([
            $trimmed,
            $withoutZero,
            ctype_digit($withoutZero) ? str_pad($withoutZero, 3, '0', STR_PAD_LEFT) : '',
        ], static fn(string $value): bool => $value !== '')));
    }

    private function num(mixed $value): float
    {
        return (float) str_replace(',', '', (string) ($value ?? 0));
    }

    private function dateLabelWithWeekday(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            $date = Carbon::parse((string) $value);
        } catch (\Throwable) {
            return '';
        }

        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];

        return $date->format('n/j') . '（' . ($weekdays[$date->dayOfWeek] ?? '') . '）';
    }

    private function salesStaffOptions(string $targetMonth): array
    {
        $targetMonth = trim($targetMonth);
        $monthStart = preg_match('/^\d{4}-\d{2}-\d{2}$/', $targetMonth) === 1
            ? substr($targetMonth, 0, 7) . '-01'
            : ($targetMonth !== '' ? $targetMonth . '-01' : now()->format('Y-m-01'));

        return $this->storeSalesStaffOptions($monthStart);
    }

    private function consignmentStaffOptions(Carbon|string $targetMonthStart): array
    {
        $targetMonthStartDate = $targetMonthStart instanceof Carbon
            ? $targetMonthStart->format('Y-m-d')
            : $targetMonthStart;

        return DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->select(['staff_id', 'staff_name', 'display_name_ja'])
            ->whereNotNull('staff_id')
            ->where('staff_id', '<>', '')
            ->where(function ($query) use ($targetMonthStartDate): void {
                $query->whereNull('tai_date')
                    ->orWhereDate('tai_date', '>=', $targetMonthStartDate);
            })
            ->where(function ($query): void {
                $query->where('staff_division', 'like', '%業務委託%')
                    ->orWhere('staff_division', 'like', '%委託%');
            })
            ->orderBy('staff_id')
            ->get()
            ->map(fn($row): array => [
                'staff_id' => trim((string) ($row->staff_id ?? '')),
                'staff_name' => trim((string) ($row->staff_name ?? '')),
                'display_name_ja' => trim((string) ($row->display_name_ja ?? '')),
            ])
            ->filter(fn(array $row): bool => $row['staff_id'] !== '')
            ->unique('staff_id')
            ->values()
            ->all();
    }
}

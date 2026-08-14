<?php

namespace App\Http\Controllers\StaffPortal\hv_office;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DistanceController extends Controller
{
    use HandlesStaffPortalContext;

    public function index(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $staffRow = $this->staffPortalStaffRow($staffId);
        if (!$this->isVisitManagement($staffRow) && !$this->isViewOnly($staffRow)) {
            abort(403);
        }

        $targetMonth = trim((string) $request->query('target_month', now()->format('Y-m')));
        if (!preg_match('/^\d{4}-\d{2}$/', $targetMonth)) {
            return redirect()->route('hv_office.distance')->withErrors(['target_month' => '月度が正しくありません。']);
        }

        $targetStaffId = trim((string) $request->query('staff_name', $staffId));
        if ($targetStaffId === '') {
            $targetStaffId = $staffId;
        }

        $monthStart = Carbon::createFromFormat('Y-m-d', $targetMonth . '-01')->startOfDay();

        $staffNameOptions = $this->homeVisitStaffOptions($monthStart);
        $distanceData = $this->distanceData($monthStart, $targetStaffId);

        return view('staff_portal.hv_office.distance.index',  $this->commonViewData($request, [
            'target_month' => $targetMonth,
            'target_staff_id' => $targetStaffId,
            'staff_name_options' => $staffNameOptions,
            'rows' => $distanceData['rows'],
            'summary' => $distanceData['summary'],
        ]));
    }

    public function print(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $staffRow = $this->staffPortalStaffRow($staffId);
        if (!$this->isVisitManagement($staffRow) && !$this->isViewOnly($staffRow)) {
            abort(403);
        }

        $targetMonth = trim((string) $request->query('target_month', now()->format('Y-m')));
        if (!preg_match('/^\d{4}-\d{2}$/', $targetMonth)) {
            return redirect()->route('hv_office.distance')->withErrors(['target_month' => '月度が正しくありません。']);
        }

        $targetStaffId = trim((string) $request->query('staff_name', $staffId));
        if ($targetStaffId === '') {
            $targetStaffId = $staffId;
        }

        $monthStart = Carbon::createFromFormat('Y-m-d', $targetMonth . '-01')->startOfDay();
        $distanceData = $this->distanceData($monthStart, $targetStaffId);

        return view('staff_portal.hv_office.distance.print', $this->commonViewData($request, [
            'target_month' => $targetMonth,
            'target_staff_id' => $targetStaffId,
            'rows' => $distanceData['rows'],
            'summary' => $distanceData['summary'],
        ]));
    }

    private function distanceData(Carbon $monthStart, string $targetStaffId): array
    {
        $monthNext = $monthStart->copy()->addMonthNoOverflow();

        $rows = DB::connection('sqlsrv')
            ->table('dbo.hv_nippou as n')
            ->leftJoin('dbo.mx_staffs as s', 's.staff_id', '=', 'n.staff_name')
            ->selectRaw("
                n.treatment_date,
                n.staff_name,
                n.holiday_category,
                SUM(n.distance) as total_distance,
                COUNT(CASE WHEN n.is_no_treatment = 0 THEN n.patient_id ELSE NULL END) as patient_count,
                COUNT(CASE WHEN n.is_same_day_duplicate = 1 THEN n.patient_id ELSE NULL END) as duplicate_patient_count,
                MAX(s.staff_name) as staff_master_name,
                MAX(s.display_name_ja) as staff_display_name_ja
            ")
            ->where('n.treatment_date', '>=', $monthStart->format('Y-m-d'))
            ->where('n.treatment_date', '<', $monthNext->format('Y-m-d'))
            ->whereRaw('LTRIM(RTRIM(n.staff_name)) = ?', [$targetStaffId])
            ->groupBy('n.staff_name', 'n.treatment_date', 'n.holiday_category')
            ->orderBy('n.treatment_date')
            ->get();

        $summary = DB::connection('sqlsrv')
            ->table('dbo.hv_nippou')
            ->selectRaw("
                SUM(distance) as total_distance,
                COUNT(CASE WHEN is_no_treatment = 0 THEN patient_id ELSE NULL END) as patient_count,
                COUNT(CASE WHEN is_same_day_duplicate = 1 THEN patient_id ELSE NULL END) as duplicate_patient_count
            ")
            ->where('treatment_date', '>=', $monthStart->format('Y-m-d'))
            ->where('treatment_date', '<', $monthNext->format('Y-m-d'))
            ->whereRaw('LTRIM(RTRIM(staff_name)) = ?', [$targetStaffId])
            ->first();

        return [
            'rows' => $rows,
            'summary' => $summary,
        ];
    }
}

<?php

namespace App\Http\Controllers\StaffPortal\home_visit;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SalesController extends Controller
{
    use HandlesStaffPortalContext;

    public function index(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $target_month = trim((string) $request->query('target_month', now()->format('Y-m')));
        $staff_name = trim((string) $request->query('staff_name', ''));
        $sort = trim((string) $request->query('sort', 'monthly'));
        if (!in_array($sort, ['monthly', 'all'], true)) {
            $sort = 'monthly';
        }

        try {
            $targetMonthStart = Carbon::createFromFormat('Y-m-d', $target_month . '-01')->startOfMonth();
        } catch (\Throwable $e) {
            $target_month = now()->format('Y-m');
            $targetMonthStart = Carbon::createFromFormat('Y-m-d', $target_month . '-01')->startOfMonth();
        }
        $targetMonthEnd = $targetMonthStart->copy()->endOfMonth();

        $staff_name_options = $this->homeVisitStaffOptions($targetMonthStart);

        $rowsQuery = DB::connection('sqlsrv')
            ->table('dbo.hv_nippou as n')
            ->leftJoin('dbo.mx_staffs as staff', 'staff.staff_id', '=', 'n.staff_name')
            ->select([
                'n.treatment_date',
                'n.staff_name',
                'n.is_management_fixed',
                'n.confirmed_at',
                'staff.display_name_ja as staff_display_name_ja',
                'staff.staff_name as staff_master_name',
            ])
            ->selectRaw('COUNT(n.patient_id) as patient_count')
            ->selectRaw('SUM(n.sales_amount) * 100 as sales_amount_total')
            ->whereBetween('n.treatment_date', [
                $targetMonthStart->format('Y-m-d'),
                $targetMonthEnd->format('Y-m-d'),
            ])
            ->whereNull('n.holiday_category')
            ->groupBy([
                'n.treatment_date',
                'n.staff_name',
                'n.is_management_fixed',
                'n.confirmed_at',
                'staff.display_name_ja',
                'staff.staff_name',
            ]);

        if ($staff_name !== '') {
            $rowsQuery->where('n.staff_name', $staff_name);
        }

        if ($sort === 'all') {
            $rowsQuery->orderByDesc('n.confirmed_at');
        } else {
            $rowsQuery->orderBy('n.treatment_date');
        }

        $rows = $rowsQuery->get();

        return view('staff_portal.home_visit.sales.index', $this->commonViewData($request, [
            'target_month' => $target_month,
            'staff_name' => $staff_name,
            'sort' => $sort,
            'staff_name_options' => $staff_name_options,
            'rows' => $rows,
        ]));
    }
}

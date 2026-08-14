<?php

namespace App\Http\Controllers\StaffPortal\hv_office;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentConfirmedController extends Controller
{
    use HandlesStaffPortalContext;

    public function index(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $year = trim((string) $request->query('year', now()->format('Y')));
        if (!preg_match('/^\d{4}$/', $year)) {
            $year = now()->format('Y');
        }

        $rows = DB::connection('sqlsrv')
            ->table('dbo.hv_ryoukin')
            ->whereRaw('LTRIM(RTRIM(payment_staff)) = ?', [$staffId])
            ->whereYear('target_month', $year)
            ->selectRaw('target_month, is_payment_confirmed, payment_confirmed_at, COUNT(patient_id) as billing_count')
            ->groupBy('target_month', 'is_payment_confirmed', 'payment_confirmed_at')
            ->orderBy('target_month')
            ->get();

        return view('staff_portal.hv_office.payment_confirmed.index', $this->commonViewData($request, [
            'year' => $year,
            'rows' => $rows,
        ]));
    }

    // 入金確定
    public function confirm(Request $request): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $targetMonth = trim((string) $request->input('target_month', ''));
        if ($targetMonth === '') {
            return back()->withErrors(['payment_confirmed' => '対象月がありません。']);
        }

        $targetMonthStart = date('Y-m-01', strtotime($targetMonth));
        $targetMonthNext = date('Y-m-d', strtotime($targetMonthStart . ' +1 month'));

        DB::connection('sqlsrv')
            ->table('dbo.hv_ryoukin')
            ->whereRaw('LTRIM(RTRIM(payment_staff)) = ?', [$staffId])
            ->where('target_month', '>=', $targetMonthStart)
            ->where('target_month', '<', $targetMonthNext)
            ->update([
                'is_payment_confirmed' => 1,
                'payment_confirmed_at' => now(),
            ]);

        return redirect()->route('hv_office.payment_confirmed', [
            'year' => date('Y', strtotime($targetMonthStart)),
        ])->with('status', '確定しました。');
    }

    // 管理者側：全スタッフ分の入金確定状況（旧システムのkanri_rireki_ryoshu.phpを踏襲）
    public function management(Request $request): RedirectResponse|View
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
        try {
            $targetMonthStart = \Carbon\Carbon::createFromFormat('Y-m-d', $targetMonth . '-01')->startOfMonth();
        } catch (\Throwable $e) {
            $targetMonth = now()->format('Y-m');
            $targetMonthStart = \Carbon\Carbon::createFromFormat('Y-m-d', $targetMonth . '-01')->startOfMonth();
        }
        $targetMonthNext = $targetMonthStart->copy()->addMonthNoOverflow();

        $rows = DB::connection('sqlsrv')
            ->table('dbo.hv_ryoukin as r')
            ->leftJoin('dbo.mx_staffs as s', 's.staff_id', '=', 'r.payment_staff')
            ->select([
                'r.payment_staff',
                's.display_name_ja',
                's.staff_name',
                'r.is_payment_confirmed',
                'r.payment_confirmed_at',
            ])
            ->where('r.target_month', '>=', $targetMonthStart->format('Y-m-d'))
            ->where('r.target_month', '<', $targetMonthNext->format('Y-m-d'))
            ->groupBy(['r.payment_staff', 's.display_name_ja', 's.staff_name', 'r.is_payment_confirmed', 'r.payment_confirmed_at'])
            ->orderBy('r.payment_staff')
            ->get();

        return view('staff_portal.hv_office.payment_confirmed.management', $this->commonViewData($request, [
            'target_month' => $targetMonth,
            'rows' => $rows,
        ]));
    }

    // 確定解除
    public function unconfirm(Request $request): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $targetMonth = trim((string) $request->input('target_month', ''));
        if ($targetMonth === '') {
            return back()->withErrors(['payment_confirmed' => '対象月がありません。']);
        }

        $targetMonthStart = date('Y-m-01', strtotime($targetMonth));
        $targetMonthNext = date('Y-m-d', strtotime($targetMonthStart . ' +1 month'));

        DB::connection('sqlsrv')
            ->table('dbo.hv_ryoukin')
            ->whereRaw('LTRIM(RTRIM(payment_staff)) = ?', [$staffId])
            ->where('target_month', '>=', $targetMonthStart)
            ->where('target_month', '<', $targetMonthNext)
            ->update([
                'is_payment_confirmed' => 0,
                'payment_confirmed_at' => null,
            ]);

        return redirect()->route('hv_office.payment_confirmed', [
            'year' => date('Y', strtotime($targetMonthStart)),
        ])->with('status', '解除しました。');
    }
}

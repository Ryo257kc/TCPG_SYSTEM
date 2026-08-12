<?php

namespace App\Http\Controllers\StaffPortal\hv_office;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DepositManagementController extends Controller
{
    use HandlesStaffPortalContext;

    private function canSelectPaymentStaff(?array $staffRow): bool
    {
        return $this->isVisitManagement($staffRow) || $this->isAccounting($staffRow) || $this->isViewOnly($staffRow);
    }

    private function targetPaymentStaffId(Request $request, string $loginStaffId, ?array $staffRow): string
    {
        if (!$this->canSelectPaymentStaff($staffRow)) {
            return $loginStaffId;
        }

        $targetStaffId = trim((string) $request->input('payment_staff', $request->query('payment_staff', $loginStaffId)));
        return $targetStaffId !== '' ? $targetStaffId : $loginStaffId;
    }

    private function depositData(string $targetMonth, string $targetStaffId): array
    {
        $targetMonthStart = $targetMonth . '-01';
        $targetMonthNext = date('Y-m-d', strtotime($targetMonthStart . ' +1 month'));

        $rows = DB::connection('sqlsrv')
            ->table('dbo.hv_ryoukin as r')
            ->leftJoin('dbo.hv_kanjya_info as k', 'r.patient_id', '=', 'k.patient_id')
            ->select([
                'r.charge_id',
                'r.patient_id',
                'k.patient_name',
                'k.burden_ratio',
                'k.window_distance',
                'k.subsidy_limit_count',
                'r.unit_price',
                'r.billing_count',
                'r.is_private_charge',
                'r.adjustment_amount',
                'r.collected_amount',
                'r.is_bank_transfer',
                'r.is_payment_confirmed',
                'r.payment_confirmed_at',
            ])
            ->where('r.payment_staff', $targetStaffId)
            ->where('r.target_month', '>=', $targetMonthStart)
            ->where('r.target_month', '<', $targetMonthNext)
            ->orderBy('k.patient_name')
            ->orderBy('r.charge_id')
            ->get();

        $totals = [
            'billing_total' => $rows->where('is_private_charge', '!=', 1)->sum(fn($r) => (float) $r->unit_price * (float) $r->billing_count),
            'private_total' => $rows->where('is_private_charge', 1)->sum(fn($r) => (float) $r->unit_price * (float) $r->billing_count),
            'adjustment_total' => $rows->sum(fn($r) => (float) ($r->adjustment_amount ?? 0)),
            'collected_total' => $rows->sum(fn($r) => (float) ($r->collected_amount ?? 0)),
        ];

        $isAllConfirmed = $rows->isNotEmpty() && $rows->every(fn($r) => (int) $r->is_payment_confirmed === 1);
        $confirmedAt = $isAllConfirmed ? $rows->max('payment_confirmed_at') : null;

        return [
            'rows' => $rows,
            'totals' => $totals,
            'isAllConfirmed' => $isAllConfirmed,
            'confirmedAt' => $confirmedAt,
        ];
    }

    public function index(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $staffRow = $this->staffPortalStaffRow($staffId);
        $targetMonth = trim((string) $request->query('target_month', now()->format('Y-m')));
        $targetStaffId = $this->targetPaymentStaffId($request, $staffId, $staffRow);
        $canSelectStaff = $this->canSelectPaymentStaff($staffRow);

        try {
            $targetMonthStart = \Carbon\Carbon::createFromFormat('Y-m-d', $targetMonth . '-01')->startOfMonth();
        } catch (\Throwable $e) {
            $targetMonth = now()->format('Y-m');
            $targetMonthStart = \Carbon\Carbon::createFromFormat('Y-m-d', $targetMonth . '-01')->startOfMonth();
        }

        $depositData = $this->depositData($targetMonth, $targetStaffId);

        return view('staff_portal.hv_office.deposit_management.index', $this->commonViewData($request, [
            'target_month' => $targetMonth,
            'target_staff_id' => $targetStaffId,
            'canSelectStaff' => $canSelectStaff,
            'staffOptions' => $canSelectStaff ? $this->homeVisitStaffOptions($targetMonthStart) : [],
            'rows' => $depositData['rows'],
            'totals' => $depositData['totals'],
            'isAllConfirmed' => $depositData['isAllConfirmed'],
            'confirmedAt' => $depositData['confirmedAt'],
        ]));
    }

    public function print(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $staffRow = $this->staffPortalStaffRow($staffId);
        $targetMonth = trim((string) $request->query('target_month', now()->format('Y-m')));
        $targetStaffId = $this->targetPaymentStaffId($request, $staffId, $staffRow);

        $depositData = $this->depositData($targetMonth, $targetStaffId);

        $staffName = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->where('staff_id', $targetStaffId)
            ->value('staff_name');

        return view('staff_portal.hv_office.deposit_management.print', $this->commonViewData($request, [
            'target_month' => $targetMonth,
            'staffName' => $staffName,
            'rows' => $depositData['rows'],
            'totals' => $depositData['totals'],
            'isAllConfirmed' => $depositData['isAllConfirmed'],
            'confirmedAt' => $depositData['confirmedAt'],
        ]));
    }
}

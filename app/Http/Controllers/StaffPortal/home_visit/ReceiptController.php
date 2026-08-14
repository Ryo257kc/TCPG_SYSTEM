<?php

namespace App\Http\Controllers\StaffPortal\home_visit;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    use HandlesStaffPortalContext;

    public function index(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $month = trim((string) $request->query('month', now()->format('Y-m')));
        $patient_name = trim((string) $request->query('patient_name', ''));
        $payment_store_name = trim((string) $request->query('payment_store_name', ''));
        $facility_name = trim((string) $request->query('facility_name', ''));
        $payment_staff = trim((string) $request->query('payment_staff', ''));
        $selected_patient_id = trim((string) $request->query('patient_id', ''));

        $targetMonthStart = $month . '-01';
        $targetMonthNext = date('Y-m-d', strtotime($targetMonthStart . ' +1 month'));
        $staffRow = $this->staffPortalStaffRow($staffId);
        $canFilterPaymentStaff = $this->isVisitManagement($staffRow)
            || $this->isAccounting($staffRow)
            || $this->isAdmin($staffRow)
            || $this->isViewOnly($staffRow);

        if (!$canFilterPaymentStaff) {
            $payment_staff = $staffId;
        }

        $allStaffOptions = $this->allStaffOptions();

        $payment_staff_display_name = '';
        foreach ($allStaffOptions as $option) {
            if ((string) $option['staff_id'] === (string) $payment_staff) {
                $payment_staff_display_name =
                    trim((string) ($option['display_name_ja'] ?? '')) !== ''
                    ? trim((string) $option['display_name_ja'])
                    : trim((string) ($option['staff_name'] ?? ''));
                break;
            }
        }

        $salesQuery = DB::connection('sqlsrv')
            ->table('dbo.hv_ryoukin')
            ->select([
                'patient_id',
                'payment_confirmed_at',
                'is_payment_confirmed',
                'is_bank_transfer',
                'payment_staff',
                'payment_store_name',
                'target_month as ryoukin_target_month',
            ])
            ->selectRaw('SUM(unit_price * billing_count) as unit_price_billing_count_total')
            ->selectRaw('SUM(collected_amount) as collected_amount_total')
            ->where('target_month', '>=', $targetMonthStart)
            ->where('target_month', '<', $targetMonthNext)
            ->groupBy([
                'patient_id',
                'target_month',
                'payment_confirmed_at',
                'is_payment_confirmed',
                'is_bank_transfer',
                'payment_staff',
                'payment_store_name',
            ]);

        if ($payment_staff !== '') {
            $salesQuery->whereRaw('LTRIM(RTRIM(payment_staff)) = ?', [$payment_staff]);
        }

        if ($payment_store_name !== '') {
            $salesQuery->where('payment_visit_area', $payment_store_name);
        }

        $dailyReportQuery = DB::connection('sqlsrv')
            ->table('dbo.hv_nippou')
            ->select([
                'patient_id',
                'target_month as daily_report_target_month',
                'daily_report_store_name',
                'daily_report_facility_name',
            ])
            ->selectRaw('SUM(copayment_amount + private_fee) as copayment_amount_private_fee_total')
            ->where('target_month', '>=', $targetMonthStart)
            ->where('target_month', '<', $targetMonthNext)
            ->groupBy([
                'patient_id',
                'target_month',
                'daily_report_store_name',
                'daily_report_facility_name',
            ]);

        $rowsQuery = DB::connection('sqlsrv')
            ->table('dbo.hv_kanjya_info as master')
            ->leftJoinSub($salesQuery, 'sales', 'master.patient_id', '=', 'sales.patient_id')
            ->leftJoinSub($dailyReportQuery, 'travels', function ($join): void {
                $join->on('master.patient_id', '=', 'travels.patient_id')
                    ->on('sales.ryoukin_target_month', '=', 'travels.daily_report_target_month');
            })
            ->leftJoin('dbo.mx_staffs as payment_staff_master', 'payment_staff_master.staff_id', '=', 'sales.payment_staff')
            ->select([
                'master.patient_id',
                'master.patient_name',
                'master.visit_store_name',
                'master.facility_name',
                'master.burden_ratio',
                'sales.payment_confirmed_at',
                'sales.is_payment_confirmed',
                'sales.is_bank_transfer',
                'sales.ryoukin_target_month',
                'sales.unit_price_billing_count_total',
                'sales.collected_amount_total',
                'sales.payment_store_name',
                'sales.payment_staff',
                'payment_staff_master.display_name_ja as payment_staff_display_name_ja',
                'payment_staff_master.staff_name as payment_staff_staff_name',
                'travels.daily_report_target_month',
                'travels.copayment_amount_private_fee_total',
                'travels.daily_report_store_name',
                'travels.daily_report_facility_name',
            ])
            ->whereNotNull('sales.ryoukin_target_month');

        if ($facility_name !== '') {
            $rowsQuery->where('master.facility_name', $facility_name);
        }

        if ($patient_name !== '') {
            $rowsQuery->where('master.patient_name', 'like', '%' . $patient_name . '%');
        }

        $items = $rowsQuery
            ->orderBy('master.facility_name')
            ->orderBy('master.patient_name')
            ->get();

        $payment_store_name_options = $this->receiptVisitAreaOptions();
        $facility_name_options = $this->patientFacilityOptions();
        $payment_staff_options = $this->homeVisitStaffOptions($targetMonthStart);

        $payment_confirmed_at = $items->first()?->payment_confirmed_at;
        $is_payment_confirmed = (int) ($items->first()?->is_payment_confirmed ?? 0);

        $selectedPatient = null;
        $receiptDetailRows = collect();
        $treatmentHistoryRows = collect();
        $unit_price_billing_count_detail_total = 0.0;
        $detail_payment_staff = $payment_staff !== '' ? $payment_staff : $staffId;

        if ($selected_patient_id !== '') {
            $selectedPatient = DB::connection('sqlsrv')
                ->table('dbo.hv_kanjya_info')
                ->select([
                    'patient_id',
                    'patient_name',
                    'burden_ratio',
                    'facility_name',
                    'standard_distance',
                ])
                ->where('patient_id', $selected_patient_id)
                ->first();

            $receiptDetailRows = DB::connection('sqlsrv')
                ->table('dbo.hv_ryoukin')
                ->select([
                    'charge_id',
                    'patient_id',
                    'target_month',
                    'collection_date',
                    'collected_amount',
                    'unit_price',
                    'billing_count',
                    'adjustment_amount',
                    'is_bank_transfer',
                    'description',
                    'treatment_count',
                    'payment_remarks',
                    'is_private_charge',
                    'is_payment_confirmed',
                    'payment_confirmed_at',
                    'payment_staff',
                    'payment_visit_area',
                ])
                ->whereRaw('LTRIM(RTRIM(payment_staff)) = ?', [$detail_payment_staff])
                ->where('patient_id', $selected_patient_id)
                ->where('target_month', '>=', $targetMonthStart)
                ->where('target_month', '<', $targetMonthNext)
                ->orderBy('charge_id')
                ->get();

            $unit_price_billing_count_detail_total = (float) $receiptDetailRows->sum(function ($row): float {
                return (float) ($row->unit_price ?? 0) * (float) ($row->billing_count ?? 0);
            });

            $treatmentHistoryRows = DB::connection('sqlsrv')
                ->table('dbo.hv_nippou as n')
                ->leftJoin('dbo.hv_kanjya_info as k', 'n.patient_id', '=', 'k.patient_id')
                ->leftJoin('dbo.mx_staffs as staff', 'staff.staff_id', '=', 'n.staff_name')
                ->select([
                    'n.daily_report_id',
                    'n.treatment_date',
                    'n.target_month',
                    'n.patient_id',
                    'n.start_time',
                    'n.end_time',
                    'n.is_home_visit',
                    'n.distance',
                    'n.remarks',
                    'n.staff_name',
                    'n.is_no_treatment',
                    'n.private_fee',
                    'n.copayment_amount',
                    'n.is_ma_treatment',
                    'n.daily_report_store_name',
                    'k.standard_distance',
                    'staff.display_name_ja as staff_display_name_ja',
                ])
                ->whereRaw('LTRIM(RTRIM(n.staff_name)) = ?', [$detail_payment_staff])
                ->where('n.patient_id', $selected_patient_id)
                ->where('n.target_month', '>=', $targetMonthStart)
                ->where('n.target_month', '<', $targetMonthNext)
                ->orderBy('n.treatment_date')
                ->orderBy('n.daily_report_id')
                ->get();
        }

        return view('staff_portal.home_visit.receipts.index', $this->commonViewData($request, [
            'month' => $month,
            'patient_name' => $patient_name,
            'payment_store_name' => $payment_store_name,
            'facility_name' => $facility_name,
            'payment_staff' => $payment_staff,
            'payment_staff_display_name' => $payment_staff_display_name,
            'allStaffOptions' => $allStaffOptions,
            'canFilterPaymentStaff' => $canFilterPaymentStaff,
            'payment_staff_options' => $payment_staff_options,
            'selected_patient_id' => $selected_patient_id,
            'payment_store_name_options' => $payment_store_name_options,
            'facility_name_options' => $facility_name_options,
            'items' => $items,
            'payment_confirmed_at' => $payment_confirmed_at,
            'is_payment_confirmed' => $is_payment_confirmed,
            'selectedPatient' => $selectedPatient,
            'receiptDetailRows' => $receiptDetailRows,
            'treatmentHistoryRows' => $treatmentHistoryRows,
            'unit_price_billing_count_detail_total' => $unit_price_billing_count_detail_total,
        ]));
    }

    // 入金管理詳細更新
    public function update(Request $request, string $id): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $staffRow = $this->staffPortalStaffRow($staffId);
        $canManage = $this->isVisitManagement($staffRow);

        if ($this->isViewOnly($staffRow) && !$canManage) {
            abort(403);
        }

        $existing = DB::connection('sqlsrv')
            ->table('dbo.hv_ryoukin')
            ->where('charge_id', $id)
            ->first(['payment_staff', 'is_payment_confirmed']);

        if (!$existing) {
            return back()->withErrors(['receipt' => '対象の入金データが見つかりません。']);
        }

        if (!$canManage) {
            if (trim((string) $existing->payment_staff) !== $staffId) {
                abort(403);
            }
            if ((int) $existing->is_payment_confirmed === 1) {
                abort(403);
            }
        }

        DB::connection('sqlsrv')
            ->table('dbo.hv_ryoukin')
            ->where('charge_id', $id)
            ->update([
                'description' => $request->input('description'),
                'is_private_charge' => $request->has('is_private_charge') ? 1 : 0,
                'unit_price' => str_replace(',', '', (string) $request->input('unit_price')),
                'billing_count' => str_replace(',', '', (string) $request->input('billing_count')),
                'collected_amount' => str_replace(',', '', (string) $request->input('collected_amount')),
                'adjustment_amount' => str_replace(',', '', (string) $request->input('adjustment_amount')),
                'payment_visit_area' => $request->input('payment_visit_area'),
            ]);

        return back()->with('status', '更新しました。');
    }

    // 入金管理詳細新規追加
    public function store(Request $request): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $staffRow = $this->staffPortalStaffRow($staffId);
        $canManage = $this->isVisitManagement($staffRow);

        if ($this->isViewOnly($staffRow) && !$canManage) {
            abort(403);
        }

        $patientId = trim((string) $request->input('patient_id', ''));

        if ($patientId === '') {
            return back()->withErrors(['receipt' => '患者を選択してから追加してください。']);
        }

        $paymentStaff = $canManage
            ? trim((string) $request->input('payment_staff', ''))
            : $staffId;

        DB::connection('sqlsrv')
            ->table('dbo.hv_ryoukin')
            ->insert([
                'patient_id' => $patientId,
                'target_month' => $request->input('target_month'),
                'payment_staff' => $paymentStaff,
                'payment_visit_area' => $request->input('payment_visit_area'),
                'unit_price' => 0,
                'billing_count' => 1,
                'collected_amount' => 0,
                'adjustment_amount' => 0,
                'is_private_charge' => 0,
                'is_payment_confirmed' => 0,
            ]);

        return back()->with('status', '追加しました。');
    }

    // 入金管理行削除
    public function destroy(Request $request, string $id): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $staffRow = $this->staffPortalStaffRow($staffId);
        $canManage = $this->isVisitManagement($staffRow);

        if ($this->isViewOnly($staffRow) && !$canManage) {
            abort(403);
        }

        $existing = DB::connection('sqlsrv')
            ->table('dbo.hv_ryoukin')
            ->where('charge_id', $id)
            ->first(['payment_staff', 'is_payment_confirmed']);

        if (!$existing) {
            return back()->withErrors(['receipt' => '対象の入金データが見つかりません。']);
        }

        if (!$canManage) {
            if (trim((string) $existing->payment_staff) !== $staffId) {
                abort(403);
            }
            if ((int) $existing->is_payment_confirmed === 1) {
                abort(403);
            }
        }

        DB::connection('sqlsrv')
            ->table('dbo.hv_ryoukin')
            ->where('charge_id', $id)
            ->delete();

        return back()->with('status', '削除しました。');
    }

    // 領収書（保険／自費）印刷。旧システムのryoushu_print.php / ryoushu_jihi_print.phpを踏襲。
    public function printReceipt(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $isPrivate = trim((string) $request->query('type', 'insurance')) === 'private';
        $month = trim((string) $request->query('month', now()->format('Y-m')));
        $targetMonthStart = $month . '-01';
        $targetMonthNext = date('Y-m-d', strtotime($targetMonthStart . ' +1 month'));

        $staffRow = $this->staffPortalStaffRow($staffId);
        $canFilterPaymentStaff = $this->isVisitManagement($staffRow)
            || $this->isAccounting($staffRow)
            || $this->isAdmin($staffRow)
            || $this->isViewOnly($staffRow);
        $paymentStaff = trim((string) $request->query('payment_staff', ''));
        if (!$canFilterPaymentStaff || $paymentStaff === '') {
            $paymentStaff = $staffId;
        }
        $selectedPatientId = trim((string) $request->query('patient_id', ''));

        $rowsQuery = DB::connection('sqlsrv')
            ->table('dbo.hv_ryoukin as r')
            ->leftJoin('dbo.hv_kanjya_info as k', 'r.patient_id', '=', 'k.patient_id')
            ->leftJoin('dbo.mx_stores as s', 's.visit_area', '=', 'r.payment_store_name')
            ->select([
                'r.charge_id',
                'r.patient_id',
                'r.description',
                'r.unit_price',
                'r.billing_count',
                'r.is_bank_transfer',
                'k.patient_name',
                'k.burden_ratio',
                'k.facility_name',
                's.store_name',
                's.store_address',
                's.phone',
            ])
            ->whereRaw('LTRIM(RTRIM(r.payment_staff)) = ?', [$paymentStaff])
            ->where('r.target_month', '>=', $targetMonthStart)
            ->where('r.target_month', '<', $targetMonthNext)
            ->where('r.is_private_charge', $isPrivate ? 1 : 0);

        if ($selectedPatientId !== '') {
            $rowsQuery->where('r.patient_id', $selectedPatientId);
        }

        $rows = $rowsQuery
            ->orderBy('k.patient_name')
            ->orderBy('r.charge_id')
            ->get();

        $billingDateLabel = \Carbon\Carbon::createFromFormat('Y-m-d', $targetMonthStart)->endOfMonth()->format('Y年n月j日');

        $receipts = $rows
            ->groupBy('patient_id')
            ->map(function ($group) use ($billingDateLabel): array {
                $first = $group->first();
                $total = $group->sum(fn($row) => (float) $row->unit_price * (float) $row->billing_count);
                $isBankTransfer = (int) $first->is_bank_transfer === 1;
                $facilityName = trim((string) ($first->facility_name ?? ''));

                if ($isBankTransfer) {
                    $heading = '請求書';
                } elseif ($facilityName === '') {
                    $heading = '請求書 兼 領収書';
                } else {
                    $heading = '領収書';
                }

                return [
                    'heading' => $heading,
                    'patient_name' => $first->patient_name,
                    'burden_ratio' => $first->burden_ratio,
                    'store_name' => $first->store_name,
                    'store_address' => $first->store_address,
                    'phone' => $first->phone,
                    'billing_date_label' => $billingDateLabel,
                    'total' => $total,
                    'lines' => $group->map(fn($row): array => [
                        'description' => $row->description,
                        'unit_price' => $row->unit_price,
                        'billing_count' => $row->billing_count,
                        'subtotal' => (float) $row->unit_price * (float) $row->billing_count,
                    ])->values()->all(),
                ];
            })
            ->values();

        return view('staff_portal.home_visit.receipts.print_receipt', [
            'isPrivate' => $isPrivate,
            'month' => $month,
            'receipts' => $receipts,
        ]);
    }
}

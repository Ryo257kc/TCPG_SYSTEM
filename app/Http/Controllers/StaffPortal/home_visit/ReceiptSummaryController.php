<?php

namespace App\Http\Controllers\StaffPortal\home_visit;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReceiptSummaryController extends Controller
{
    use HandlesStaffPortalContext;

    public function index(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $target_month = trim((string) $request->query('target_month', now()->format('Y-m')));
        $summary_type = trim((string) $request->query('summary_type', '保険'));
        $daily_report_store_name = trim((string) $request->query('daily_report_store_name', ''));
        $patient_name = trim((string) $request->query('patient_name', ''));
        $daily_report_billing_staff = trim((string) $request->query('daily_report_billing_staff', ''));
        if (!in_array($summary_type, ['保険', '自費'], true)) {
            $summary_type = '保険';
        }

        $targetMonthStart = $target_month . '-01';
        $targetMonthNext = date('Y-m-d', strtotime($targetMonthStart . ' +1 month'));
        $is_private_charge = $summary_type === '自費' ? 1 : 0;

        $daily_report_store_name_options = DB::connection('sqlsrv')
            ->table('dbo.hv_nippou')
            ->where('target_month', '>=', $targetMonthStart)
            ->where('target_month', '<', $targetMonthNext)
            ->whereNotNull('daily_report_store_name')
            ->where('daily_report_store_name', '<>', '')
            ->orderBy('daily_report_store_name')
            ->pluck('daily_report_store_name')
            ->map(fn ($value): string => trim((string) $value))
            ->filter(fn (string $value): bool => $value !== '')
            ->unique()
            ->values()
            ->all();

        $daily_report_billing_staff_options = $this->homeVisitStaffOptions($targetMonthStart);

        $existingCount = DB::connection('sqlsrv')
            ->table('dbo.hv_ryoukin')
            ->where('target_month', '>=', $targetMonthStart)
            ->where('target_month', '<', $targetMonthNext)
            ->where('is_private_charge', $is_private_charge)
            ->count();

        $rows = collect();

        if ($existingCount === 0) {
            $rowsQuery = DB::connection('sqlsrv')
                ->table('dbo.hv_nippou as n')
                ->leftJoin('dbo.hv_kanjya_info as k', 'n.patient_id', '=', 'k.patient_id')
                ->leftJoin('dbo.mx_staffs as billing_staff', 'billing_staff.staff_id', '=', 'n.daily_report_billing_staff')
                ->select([
                    'n.patient_id',
                    'n.target_month',
                    'n.daily_report_town_name',
                    'n.daily_report_billing_staff',
                    'n.daily_report_store_name',
                    'k.patient_name',
                    'k.standard_distance',
                    'k.burden_ratio',
                    'k.subsidy_limit_count',
                    'billing_staff.display_name_ja as daily_report_billing_staff_display_name_ja',
                ])
                ->selectRaw('COUNT(n.patient_id) as billing_count')
                ->selectRaw('SUM(n.copayment_amount) as copayment_amount_total')
                ->selectRaw('SUM(n.private_fee) as private_fee_total')
                ->selectRaw('MAX(n.treatment_details) as treatment_details')
                ->where('n.target_month', '>=', $targetMonthStart)
                ->where('n.target_month', '<', $targetMonthNext)
                ->where(function ($query): void {
                    $query->where('n.is_no_treatment', 0)
                        ->orWhereNull('n.is_no_treatment');
                })
                ->where('n.copayment_amount', '<>', 0);

            if ($summary_type === '保険') {
                $rowsQuery->where(function ($query): void {
                    $query->where('n.private_fee', 0)
                        ->orWhereNull('n.private_fee');
                });
            } else {
                $rowsQuery->where('n.private_fee', '<>', 0);
            }

            if ($daily_report_store_name !== '') {
                $rowsQuery->where('n.daily_report_store_name', $daily_report_store_name);
            }

            if ($patient_name !== '') {
                $rowsQuery->where('k.patient_name', 'like', '%' . $patient_name . '%');
            }

            if ($daily_report_billing_staff !== '') {
                $rowsQuery->where(function ($query) use ($daily_report_billing_staff): void {
                    $query->where('n.daily_report_billing_staff', 'like', '%' . $daily_report_billing_staff . '%')
                        ->orWhere('billing_staff.display_name_ja', 'like', '%' . $daily_report_billing_staff . '%');
                });
            }

            $rows = $rowsQuery
                ->groupBy([
                    'n.patient_id',
                    'n.target_month',
                    'n.daily_report_town_name',
                    'n.daily_report_billing_staff',
                    'n.daily_report_store_name',
                    'k.patient_name',
                    'k.standard_distance',
                    'k.burden_ratio',
                    'k.subsidy_limit_count',
                    'billing_staff.display_name_ja',
                ])
                ->orderBy('n.daily_report_billing_staff')
                ->orderBy('k.patient_name')
                ->get();
        }

        return view('staff_portal.home_visit.receipt_summary.index', $this->commonViewData($request, [
            'target_month' => $target_month,
            'summary_type' => $summary_type,
            'daily_report_store_name' => $daily_report_store_name,
            'patient_name' => $patient_name,
            'daily_report_billing_staff' => $daily_report_billing_staff,
            'daily_report_store_name_options' => $daily_report_store_name_options,
            'daily_report_billing_staff_options' => $daily_report_billing_staff_options,
            'existingCount' => $existingCount,
            'rows' => $rows,
        ]));
    }

    // 集計確定（hv_nippouの日報からhv_ryoukinへ一括作成）
    public function confirm(Request $request): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        if (!$this->isVisitManagement($this->staffPortalStaffRow($staffId))) {
            abort(403);
        }

        $target_month = trim((string) $request->input('target_month', ''));
        $summary_type = trim((string) $request->input('summary_type', ''));
        if (!in_array($summary_type, ['保険', '自費'], true) || $target_month === '') {
            return redirect()->route('home_visit.receipt_summary')->withErrors([
                'receipt_summary' => '対象月・区分が不正です。',
            ]);
        }

        $targetMonthStart = $target_month . '-01';
        $targetMonthNext = date('Y-m-d', strtotime($targetMonthStart . ' +1 month'));
        $is_private_charge = $summary_type === '自費' ? 1 : 0;

        // 二重確定防止（表示時と確定操作の間に他の操作で作られていないか再確認）
        $existingCount = DB::connection('sqlsrv')
            ->table('dbo.hv_ryoukin')
            ->where('target_month', '>=', $targetMonthStart)
            ->where('target_month', '<', $targetMonthNext)
            ->where('is_private_charge', $is_private_charge)
            ->count();

        if ($existingCount > 0) {
            return redirect()->route('home_visit.receipt_summary', [
                'target_month' => $target_month,
                'summary_type' => $summary_type,
            ])->withErrors(['receipt_summary' => 'すでに集計済みです。']);
        }

        // 旧システム(insert-shukei.php)のロジックを踏襲：単価=1回あたりの実額、請求回数=件数
        // 同一患者・同一月でも実額が異なれば別行として作成される
        if ($summary_type === '自費') {
            DB::connection('sqlsrv')->insert("
                INSERT INTO dbo.hv_ryoukin (
                    patient_id, target_month, unit_price, billing_count, description,
                    is_private_charge, payment_staff, payment_store_name, payment_visit_area
                )
                SELECT
                    n.patient_id, n.target_month, n.private_fee, COUNT(n.patient_id), MAX(n.treatment_details),
                    1, n.daily_report_billing_staff, n.daily_report_store_name, d.store_short_name
                FROM dbo.hv_nippou n
                LEFT JOIN dbo.mx_departments d ON d.visit_area = n.daily_report_store_name
                WHERE n.target_month >= ? AND n.target_month < ?
                  AND (n.is_no_treatment = 0 OR n.is_no_treatment IS NULL)
                  AND n.private_fee <> 0
                GROUP BY n.patient_id, n.target_month, n.private_fee, n.daily_report_billing_staff, n.daily_report_store_name, d.store_short_name
            ", [$targetMonthStart, $targetMonthNext]);
        } else {
            DB::connection('sqlsrv')->insert("
                INSERT INTO dbo.hv_ryoukin (
                    patient_id, target_month, unit_price, billing_count, description,
                    payment_staff, payment_store_name, payment_visit_area
                )
                SELECT
                    n.patient_id, n.target_month, n.copayment_amount, COUNT(n.patient_id), N'鍼灸治療',
                    n.daily_report_billing_staff, n.daily_report_store_name, d.store_short_name
                FROM dbo.hv_nippou n
                LEFT JOIN dbo.mx_departments d ON d.visit_area = n.daily_report_store_name
                WHERE n.target_month >= ? AND n.target_month < ?
                  AND (n.is_no_treatment = 0 OR n.is_no_treatment IS NULL)
                  AND n.copayment_amount <> 0
                  AND (n.private_fee = 0 OR n.private_fee IS NULL)
                GROUP BY n.patient_id, n.target_month, n.copayment_amount, n.daily_report_billing_staff, n.daily_report_store_name, d.store_short_name
            ", [$targetMonthStart, $targetMonthNext]);
        }

        return redirect()->route('home_visit.receipt_summary', [
            'target_month' => $target_month,
            'summary_type' => $summary_type,
        ])->with('status', '集計を確定しました。');
    }
}

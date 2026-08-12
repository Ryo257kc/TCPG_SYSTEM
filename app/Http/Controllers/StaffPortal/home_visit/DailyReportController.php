<?php

namespace App\Http\Controllers\StaffPortal\home_visit;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class DailyReportController extends Controller
{
    use HandlesStaffPortalContext;

    private function canSelectDailyReportStaff(?array $staffRow): bool
    {
        return $this->isVisitManagement($staffRow) || $this->isAdmin($staffRow);
    }

    // 往診閲覧: 編集権限は無いが全スタッフのデータを閲覧できる
    private function canViewAnyDailyReportStaff(?array $staffRow): bool
    {
        return $this->canSelectDailyReportStaff($staffRow) || $this->isViewOnly($staffRow);
    }

    private function targetDailyReportStaffId(Request $request, string $loginStaffId, ?array $staffRow): string
    {
        if (!$this->canSelectDailyReportStaff($staffRow)) {
            return $loginStaffId;
        }

        $targetStaffId = trim((string) $request->input('staff_name', $request->query('staff_name', $loginStaffId)));
        return $targetStaffId !== '' ? $targetStaffId : $loginStaffId;
    }

    private function targetDailyReportViewStaffId(Request $request, string $loginStaffId, ?array $staffRow): string
    {
        if (!$this->canViewAnyDailyReportStaff($staffRow)) {
            return $loginStaffId;
        }

        $targetStaffId = trim((string) $request->input('staff_name', $request->query('staff_name', $loginStaffId)));
        return $targetStaffId !== '' ? $targetStaffId : $loginStaffId;
    }
    public function index(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $staffRow = $this->staffPortalStaffRow($staffId);
        $date = trim((string) $request->query('date', now()->format('Y-m-d')));
        $targetStaffId = $this->targetDailyReportViewStaffId($request, $staffId, $staffRow);

        $items = DB::connection('sqlsrv')
            ->table('dbo.hv_nippou as n')
            ->leftJoin('dbo.hv_kanjya_info as k', 'n.patient_id', '=', 'k.patient_id')
            ->select([
                'n.daily_report_id',
                'n.treatment_date',
                'n.patient_id',
                'n.is_home_visit',
                'n.is_no_treatment',
                'n.is_same_day_duplicate',
                'k.patient_name',
                'n.start_time',
                'n.end_time',
                'n.distance',
                'n.sales_amount',
                'n.private_fee',
                'n.uncollected_amount',
                'n.staff_name',
                'n.is_confirmed',
                'n.confirmed_at',
                'n.is_management_fixed',
                'n.daily_report_store_name',
                'n.remarks',
                'k.standard_distance',
                'k.burden_ratio',
                'k.standard_burden_amount',
                'k.subsidy_limit_count',
                'k.subsidy_burden_amount',
            ])
            ->whereDate('n.treatment_date', $date)
            ->whereRaw('LTRIM(RTRIM(n.staff_name)) = ?', [$targetStaffId])
            ->orderBy('n.is_no_treatment')
            ->orderByRaw("
            CASE
                WHEN n.start_time IS NULL THEN 2
                WHEN CONVERT(varchar, n.start_time, 108) = '00:00:00' THEN 1
                ELSE 0
            END
        ")
            ->orderBy('n.start_time')
            ->orderBy('n.daily_report_id')
            ->get();

        $summary = DB::connection('sqlsrv')
            ->table('dbo.hv_nippou')
            ->selectRaw("
            SUM(distance) as total_distance,
            COUNT(CASE WHEN is_no_treatment = 0 THEN patient_id ELSE NULL END) as total_patients,
            COUNT(CASE WHEN is_same_day_duplicate = 1 THEN patient_id ELSE NULL END) as duplicate_patients
        ")
            ->whereDate('treatment_date', $date)
            ->whereRaw('LTRIM(RTRIM(staff_name)) = ?', [$targetStaffId])
            ->first();

        $canSelectStaff = $this->canViewAnyDailyReportStaff($staffRow);
        $targetMonthStart = date('Y-m-01', strtotime($date));

        return view('staff_portal.home_visit.daily_report.index', $this->commonViewData($request, [
            'date' => $date,
            'targetStaffId' => $targetStaffId,
            'canSelectStaff' => $canSelectStaff,
            'staffOptions' => $canSelectStaff ? $this->homeVisitStaffOptions($targetMonthStart) : [],
            'items' => $items,
            'summary' => $summary,
        ]));
    }

    //
    public function print(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $staffRow = $this->staffPortalStaffRow($staffId);
        $date = trim((string) $request->query('date', now()->format('Y-m-d')));
        $targetStaffId = $this->targetDailyReportViewStaffId($request, $staffId, $staffRow);

        $items = DB::connection('sqlsrv')
            ->table('dbo.hv_nippou as n')
            ->leftJoin('dbo.hv_kanjya_info as k', 'n.patient_id', '=', 'k.patient_id')
            ->select([
                'n.daily_report_id',
                'n.treatment_date',
                'n.patient_id',
                'k.patient_name',
                'n.daily_report_town_name',
                'n.distance',
                'n.start_time',
                'n.end_time',
                'n.is_home_visit',
                'n.is_no_treatment',
                'n.is_same_day_duplicate',
                'n.sales_amount',
                'n.private_fee',
                'n.uncollected_amount',
                'n.daily_report_store_name',
                'n.remarks',
            ])
            ->whereDate('n.treatment_date', $date)
            ->whereRaw('LTRIM(RTRIM(n.staff_name)) = ?', [$targetStaffId])
            ->orderBy('n.is_no_treatment')
            ->orderByRaw("
            CASE
                WHEN n.start_time IS NULL THEN 2
                WHEN CONVERT(varchar, n.start_time, 108) = '00:00:00' THEN 1
                ELSE 0
            END
        ")
            ->orderBy('n.start_time')
            ->orderBy('n.daily_report_id')
            ->get();

        $summary = DB::connection('sqlsrv')
            ->table('dbo.hv_nippou')
            ->selectRaw("
            SUM(distance) as total_distance,
            COUNT(CASE WHEN is_no_treatment = 0 THEN patient_id ELSE NULL END) as total_patients,
            COUNT(CASE WHEN is_same_day_duplicate = 1 THEN patient_id ELSE NULL END) as duplicate_patients
        ")
            ->whereDate('treatment_date', $date)
            ->whereRaw('LTRIM(RTRIM(staff_name)) = ?', [$targetStaffId])
            ->first();

        $staffName = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->where('staff_id', $targetStaffId)
            ->value('staff_name');

        return view('staff_portal.home_visit.daily_report.print', $this->commonViewData($request, [
            'date' => $date,
            'items' => $items,
            'summary' => $summary,
            'staffName' => $staffName,
        ]));
    }


    // 翌週へ複製
    public function copyWeek(Request $request)
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $staffRow = $this->staffPortalStaffRow($staffId);
        $targetStaffId = $this->targetDailyReportStaffId($request, $staffId, $staffRow);
        $date = trim((string) $request->query('date', now()->format('Y-m-d')));
        $newDate = \Carbon\Carbon::parse($date)->addDays(7)->format('Y-m-d');

        $items = DB::connection('sqlsrv')
            ->table('dbo.hv_nippou as n')
            ->leftJoin('dbo.hv_kanjya_info as k', 'n.patient_id', '=', 'k.patient_id')
            ->select([
                'n.patient_id',
                'k.patient_name',
                'n.staff_name',
                'n.daily_report_store_name',
                'n.is_home_visit',
                'n.daily_visit_area',
                'n.massage_visit_area',
            ])
            ->whereDate('n.treatment_date', $date)
            ->whereRaw('LTRIM(RTRIM(n.staff_name)) = ?', [$targetStaffId])
            ->orderBy('n.daily_report_id')
            ->get();

        $insertRows = $items->map(function ($item) use ($newDate, $targetStaffId) {
            return [
                'treatment_date' => $newDate,
                'target_month' => \Carbon\Carbon::parse($newDate)->startOfMonth()->format('Y-m-d'),
                'patient_id' => $item->patient_id,
                'staff_name' => $targetStaffId,
                'daily_report_store_name' => $item->daily_report_store_name,
                'is_home_visit' => $item->is_home_visit,
                'is_no_treatment' => 0,
                'is_confirmed' => 0,
                'is_management_fixed' => 0,
                'start_time' => null,
                'end_time' => null,
                'distance' => null,
                'remarks' => null,
                'private_fee' => null,
                'uncollected_amount' => null,
                'insurance_amount' => null,
                'copayment_amount' => null,
            ];
        })->filter(function ($row) {
            return !DB::connection('sqlsrv')
                ->table('dbo.hv_nippou')
                ->whereDate('treatment_date', $row['treatment_date'])
                ->where('staff_name', $row['staff_name'])
                ->where('patient_id', $row['patient_id'])
                ->exists();
        })->values()->all();

        if (!empty($insertRows)) {
            DB::connection('sqlsrv')
                ->table('dbo.hv_nippou')
                ->insert($insertRows);
        }

        return redirect()
            ->route('home_visit.daily_report', ['date' => $newDate, 'staff_name' => $targetStaffId])
            ->with('status', count($insertRows) . '件を翌週へ複製しました。');
    }

    // 新規追加
    public function quickStore(Request $request): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $staffRow = $this->staffPortalStaffRow($staffId);
        $targetStaffId = $this->targetDailyReportStaffId($request, $staffId, $staffRow);
        $date = trim((string) $request->input('date', now()->format('Y-m-d')));

        DB::connection('sqlsrv')
            ->table('dbo.hv_nippou')
            ->insert([
                'treatment_date' => $date,
                'staff_name' => $targetStaffId,
                'is_home_visit' => 1,
            ]);
        return redirect()->route('home_visit.daily_report', [
            'date' => $date,
            'staff_name' => $targetStaffId,
        ]);
    }

    // 往診編集
    public function edit(Request $request, string $nippouNo): View|RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $staffRow = $this->staffPortalStaffRow($staffId);
        $canManage = $this->isVisitManagement($staffRow);
        $isViewOnlyRole = $this->isViewOnly($staffRow);

        $item = DB::connection('sqlsrv')
            ->table('dbo.hv_nippou as n')
            ->leftJoin('dbo.hv_kanjya_info as k', 'n.patient_id', '=', 'k.patient_id')
            ->select([
                'n.daily_report_id',
                'n.treatment_date',
                'n.patient_id',
                'n.start_time',
                'n.end_time',
                'n.is_home_visit',
                'n.distance',
                'n.remarks',
                'n.staff_name',
                'n.is_no_treatment',
                'n.private_fee',
                'n.uncollected_amount',
                'n.insurance_amount',
                'n.copayment_amount',
                'n.treatment_details',
                'n.added_at',
                'n.is_same_day_duplicate',
                'n.daily_report_store_name',
                'n.daily_report_facility_name',
                'n.daily_report_town_name',
                'n.daily_report_address',
                'n.is_confirmed',
                'n.confirmed_at',
                'n.is_management_fixed',
                'n.receipt_home_visit_distance',
                'n.receipt_home_visit_distance_ma',
                'n.is_receipt_home_visit',
                'n.is_ma_treatment',
                'n.home_visit_start_point',
                'k.patient_name',
                'k.standard_distance',
                'k.standard_burden_amount',
                'k.subsidy_limit_count',
                'k.full_address',
                'k.facility_name',
                'k.treatment_fee',
            ])
            ->where('n.daily_report_id', $nippouNo)
            ->first();

        if (!$item) {
            return redirect()->route('home_visit.daily_report')->withErrors([
                'visits' => '往診データが見つかりませんでした。',
            ]);
        }

        if (!$canManage && !$isViewOnlyRole && trim((string) $item->staff_name) !== $staffId) {
            abort(403);
        }

        $isReadOnly = $isViewOnlyRole
            || (int) $item->is_management_fixed === 1
            || (!$canManage && (int) $item->is_confirmed === 1);

        $itemMonthStart = date('Y-m-01', strtotime($item->treatment_date));
        $itemMonthNext = date('Y-m-d', strtotime($itemMonthStart . ' +1 month'));

        $kaisu = DB::connection('sqlsrv')
            ->table('dbo.hv_nippou as n')
            ->join('dbo.hv_kanjya_info as k', 'n.patient_id', '=', 'k.patient_id')
            ->where('n.treatment_date', '>=', $itemMonthStart)
            ->where('n.treatment_date', '<', $itemMonthNext)
            ->where('k.patient_id', $item->patient_id)
            ->where('n.treatment_date', '<', $item->treatment_date)
            ->count('n.patient_id');

        $patients = DB::connection('sqlsrv')
            ->table('dbo.hv_kanjya_info')
            ->select('patient_id', 'patient_name', 'full_address', 'facility_name')
            ->orderBy('patient_name')
            ->get();

        $stores = $this->receiptVisitAreaOptions();
        $facilities = $this->patientFacilityOptions();

        $now = now();
        $currentMonthStart = $now->copy()->startOfMonth()->format('Y-m-d');
        $currentMonthNext = $now->copy()->startOfMonth()->addMonthNoOverflow()->format('Y-m-d');

        $monthlyCount = DB::connection('sqlsrv')
            ->table('dbo.hv_nippou')
            ->where('treatment_date', '>=', $currentMonthStart)
            ->where('treatment_date', '<', $currentMonthNext)
            ->where('patient_id', $item->patient_id)
            ->whereDate('treatment_date', '<', $item->treatment_date)
            ->count();

        $staffName = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs') // 実テーブル名に合わせる
            ->where('staff_id', $item->staff_name)
            ->value('staff_name');

        return view('staff_portal.home_visit.daily_report.edit', $this->commonViewData($request, [
            'item' => $item,
            'patients' => $patients,
            'stores' => $stores,
            'facilities' => $facilities,
            'monthlyCount' => $monthlyCount,
            'staffName' => $staffName,
            'kaisu' => $kaisu,
            'isReadOnly' => $isReadOnly,
        ]));
    }

    // 往診更新
    public function update(Request $request, string $nippouNo): RedirectResponse
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
            ->table('dbo.hv_nippou')
            ->where('daily_report_id', $nippouNo)
            ->first(['staff_name', 'is_confirmed', 'is_management_fixed']);

        if (!$existing) {
            return back()->withInput()->withErrors([
                'visits' => '対象の往診データが見つかりません。',
            ]);
        }

        if ((int) $existing->is_management_fixed === 1) {
            abort(403);
        }

        if (!$canManage) {
            if (trim((string) $existing->staff_name) !== $staffId) {
                abort(403);
            }
            if ((int) $existing->is_confirmed === 1) {
                abort(403);
            }
        }

        $validated = $request->validate([
            'patient_id' => ['required'],
            'treatment_date' => ['required', 'date'],
            'distance' => ['nullable'],
            'start_time' => ['nullable'],
            'end_time' => ['nullable'],
            'daily_report_store_name' => ['nullable', 'string', 'max:50'],
            'daily_report_facility_name' => ['nullable', 'string', 'max:50'],
            'daily_report_town_name' => ['nullable', 'string', 'max:50'],
            'treatment_details' => ['nullable', 'string', 'max:50'],
            'private_fee' => ['nullable'],
            'uncollected_amount' => ['nullable'],
            'copayment_amount' => ['nullable'],
            'insurance_amount' => ['nullable'],
            'remarks' => ['nullable', 'string'],
            'added_at' => ['nullable', 'date'],
            'receipt_home_visit_distance' => ['nullable', 'string', 'max:10'],
            'receipt_home_visit_distance_ma' => ['nullable', 'string', 'max:10'],

            // 起点患者
            'kiten_patient_id' => ['nullable'],
        ]);

        $updateData = [
            'patient_id' => $validated['patient_id'],
            'treatment_date' => $validated['treatment_date'],
            'distance' => $validated['distance'] !== '' ? $validated['distance'] : null,
            'start_time' => $validated['start_time'] !== '' ? $validated['start_time'] : null,
            'end_time' => $validated['end_time'] !== '' ? $validated['end_time'] : null,
            'daily_report_store_name' => $validated['daily_report_store_name'] ?? null,
            'daily_report_facility_name' => $validated['daily_report_facility_name'] ?? null,
            'daily_report_town_name' => $validated['daily_report_town_name'] ?? null,
            'treatment_details' => $validated['treatment_details'] ?? null,
            'private_fee' => $validated['private_fee'] !== '' ? $validated['private_fee'] : null,
            'uncollected_amount' => $validated['uncollected_amount'] !== '' ? $validated['uncollected_amount'] : null,
            'copayment_amount' => $validated['copayment_amount'] !== '' ? $validated['copayment_amount'] : null,
            'insurance_amount' => $validated['insurance_amount'] !== '' ? $validated['insurance_amount'] : null,
            'remarks' => $validated['remarks'] ?? null,
            'added_at' => $validated['added_at'] ?? null,
            'is_home_visit' => $request->boolean('is_home_visit') ? 1 : 0,
            'is_no_treatment' => $request->boolean('is_no_treatment') ? 1 : 0,
            'is_same_day_duplicate' => $request->boolean('is_same_day_duplicate') ? 1 : 0,
            'is_ma_treatment' => $request->boolean('is_ma_treatment') ? 1 : 0,
            'is_receipt_home_visit' => $request->boolean('is_receipt_home_visit') ? 1 : 0,
            'receipt_home_visit_distance' => $validated['receipt_home_visit_distance'] ?? null,
            'receipt_home_visit_distance_ma' => $validated['receipt_home_visit_distance_ma'] ?? null,
        ];

        // 🔥 起点処理（確定版）
        if (!empty($validated['kiten_patient_id'])) {

            $kitenPatient = DB::connection('sqlsrv')
                ->table('dbo.hv_kanjya_info')
                ->select(['patient_id', 'patient_name', 'full_address'])
                ->where('patient_id', $validated['kiten_patient_id'])
                ->first();

            if (!$kitenPatient) {
                return back()->withInput()->withErrors([
                    'kiten_patient_id' => '起点患者が見つかりません。',
                ]);
            }

            $updateData['home_visit_start_point'] = $kitenPatient->patient_id;
            $updateData['daily_report_address'] = $kitenPatient->full_address;
        }

        DB::connection('sqlsrv')
            ->table('dbo.hv_nippou')
            ->where('daily_report_id', $nippouNo)
            ->update($updateData);

        return redirect()->route('home_visit.daily_report', [
            'date' => $validated['treatment_date'],
        ])->with('status', '往診データを更新しました。');
    }

    // 往診1件削除
    public function destroy(Request $request, string $nippouNo): RedirectResponse
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
            ->table('dbo.hv_nippou')
            ->where('daily_report_id', $nippouNo)
            ->first(['staff_name', 'is_confirmed', 'is_management_fixed']);

        if (!$existing) {
            return redirect()->back()->withErrors([
                'visits' => '往診データの削除に失敗しました。',
            ]);
        }

        if ((int) $existing->is_management_fixed === 1) {
            abort(403);
        }

        if (!$canManage) {
            if (trim((string) $existing->staff_name) !== $staffId) {
                abort(403);
            }
            if ((int) $existing->is_confirmed === 1) {
                abort(403);
            }
        }

        $date = trim((string) $request->input('date', now()->format('Y-m-d')));

        $deleted = DB::connection('sqlsrv')
            ->table('dbo.hv_nippou')
            ->where('daily_report_id', $nippouNo)
            ->delete();

        if ($deleted === 0) {
            return redirect()->back()->withErrors([
                'visits' => '往診データの削除に失敗しました。',
            ]);
        }

        return redirect()
            ->route('home_visit.daily_report', ['date' => $date])
            ->with('status', '往診データを削除しました。');
    }

    // 患者情報取得＆表示
    public function patientInfo(Request $request, string $patientId): JsonResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $patient = DB::connection('sqlsrv')
            ->table('dbo.hv_kanjya_info')
            ->select([
                'patient_id',
                'standard_distance',
                'burden_ratio',
                'standard_burden_amount',
                'subsidy_limit_count',
                'subsidy_burden_amount',
            ])
            ->where('patient_id', $patientId)
            ->first();

        if (!$patient) {
            return response()->json(['message' => 'Patient not found'], 404);
        }

        return response()->json([
            'standard_distance' => $patient->standard_distance,
            'burden_ratio' => $patient->burden_ratio,
            'standard_burden_amount' => $patient->standard_burden_amount,
            'subsidy_limit_count' => $patient->subsidy_limit_count,
            'subsidy_burden_amount' => $patient->subsidy_burden_amount,
        ]);
    }

    // 月間日報
    public function monthlyIndex(Request $request)
    {
        $staffId = session('staff_id');
        $month = $request->input('month') ?? now()->format('Y-m');
        $monthStart = date('Y-m-01', strtotime($month));
        $monthNext = date('Y-m-d', strtotime($monthStart . ' +1 month'));

        $rows = DB::connection('sqlsrv')
            ->table('dbo.hv_nippou as n')
            ->selectRaw("
            n.treatment_date as treatment_date,
            n.is_confirmed as is_confirmed,
            n.confirmed_at as confirmed_at,
            COUNT(n.patient_id) as count,
            MAX(n.staff_name) as staff_id,
            n.is_management_fixed as is_management_fixed
        ")
            ->where('n.treatment_date', '>=', $monthStart)
            ->where('n.treatment_date', '<', $monthNext)
            ->where('n.staff_name', $staffId)
            // ->whereNull('n.休日区分')
            ->groupBy('n.treatment_date', 'is_confirmed', 'confirmed_at', 'is_management_fixed')
            ->orderBy('n.treatment_date')
            ->get();

        return view('staff_portal.home_visit.monthly_report.index', $this->commonViewData($request, [
            'rows' => $rows,
            'month' => $month,
        ]));
    }

    // 往診日報　本人確定
    public function confirm(Request $request)
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $staffRow = $this->staffPortalStaffRow($staffId);
        $targetStaffId = $this->targetDailyReportStaffId($request, $staffId, $staffRow);
        $date = $request->input('date');
        if (!$date) {
            return back()->withErrors(['date' => '日付がありません。']);
        }

        DB::connection('sqlsrv')
            ->table('dbo.hv_nippou')
            ->whereDate('treatment_date', $date)
            ->where('staff_name', $targetStaffId)
            ->update([
                'is_confirmed' => 1,
                'confirmed_at' => now(),
            ]);

        return redirect()->route('home_visit.daily_report', [
            'date' => date('Y-m-d', strtotime($date)),
            'staff_name' => $targetStaffId,
        ])->with('status', '本人確定しました。');
    }

    // 確定解除
    public function unconfirm(Request $request)
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $staffRow = $this->staffPortalStaffRow($staffId);
        if (!$this->isVisitManagement($staffRow) && !$this->isAdmin($staffRow)) {
            abort(403);
        }

        $date = $request->input('date');
        if (!$date) {
            return back()->withErrors(['date' => '日付がありません。']);
        }

        DB::connection('sqlsrv')
            ->table('dbo.hv_nippou')
            ->whereDate('treatment_date', $date)
            ->where('staff_name', $staffId)
            ->update([
                'is_confirmed' => 0,
                'confirmed_at' => null,
                'is_management_fixed' => 0,
            ]);

        return redirect()->route('home_visit.monthly_report', [
            'month' => date('Y-m', strtotime($date)),
        ])->with('status', '確定解除しました。');
    }

    // 管理確定
    public function adminConfirm(Request $request)
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $staffRow = $this->staffPortalStaffRow($staffId);
        if (!$this->isVisitManagement($staffRow) && !$this->isAdmin($staffRow)) {
            abort(403);
        }

        $date = $request->input('date');
        if (!$date) {
            return back()->withErrors(['date' => '日付がありません。']);
        }

        DB::connection('sqlsrv')
            ->table('dbo.hv_nippou')
            ->whereDate('treatment_date', $date)
            ->where('staff_name', $staffId)
            ->update([
                'is_management_fixed' => 1,
            ]);

        return redirect()->route('home_visit.monthly_report', [
            'month' => date('Y-m', strtotime($date)),
        ])->with('status', '管理確定しました。');
    }

}

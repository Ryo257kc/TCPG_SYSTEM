<?php

namespace App\Http\Controllers\StaffPortal\home_visit;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class PatientController extends Controller
{
    use HandlesStaffPortalContext;

    private function canEditPatients(?array $staffRow): bool
    {
        return $this->isVisitManagement($staffRow) || $this->isAccounting($staffRow);
    }

    private function patientValidationRules(): array
    {
        return [
            'common_id' => ['nullable', 'string', 'max:50'],
            'massage_id' => ['nullable', 'string', 'max:50'],
            'patient_name' => ['required', 'string', 'max:100'],
            'collection_staff' => ['nullable', 'string', 'max:10'],
            'billing_staff' => ['nullable', 'string', 'max:10'],
            'visit_town_name' => ['nullable', 'string', 'max:100'],
            'full_address' => ['nullable', 'string', 'max:200'],
            'facility_name' => ['nullable', 'string', 'max:100'],
            'visit_store_name' => ['nullable', 'string', 'max:100'],
            'standard_distance' => ['nullable', 'numeric'],
            'burden_ratio' => ['nullable', 'integer'],
            'standard_burden_amount' => ['nullable', 'numeric'],
            'treatment_fee' => ['nullable', 'numeric'],
            'window_distance' => ['nullable', 'integer'],
            'subsidy_limit_count' => ['nullable', 'integer'],
            'consent_category' => ['nullable', 'string', 'max:20'],
            'consent_date' => ['nullable', 'date'],
        ];
    }

    // 患者一覧
    public function index(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        $keyword  = trim((string) $request->input('q', ''));
        $shop     = trim((string) $request->input('shop', ''));
        $facility = trim((string) $request->input('facility', ''));
        $staff    = trim((string) $request->input('staff', ''));

        $query = DB::connection('sqlsrv')
            ->table('dbo.hv_kanjya_info as k')

            // 請求担当
            ->leftJoin('dbo.mx_staffs as s1', 's1.staff_id', '=', 'k.billing_staff')

            // 集金担当
            ->leftJoin('dbo.mx_staffs as s2', 's2.staff_id', '=', 'k.collection_staff')

            ->select([
                'k.patient_id',
                'k.patient_name',
                'k.visit_town_name',
                'k.visit_store_name',
                'k.facility_name',
                'k.billing_staff',
                'k.collection_staff',

                // 表示名
                's1.display_name_ja as billing_staff_name',
                's2.display_name_ja as collection_staff_name',
            ]);

        if ($keyword !== '') {
            $query->where('k.patient_name', 'like', '%' . $keyword . '%');
        }

        if ($shop !== '') {
            $query->where('k.visit_store_name', $shop);
        }

        if ($facility !== '') {
            $query->where('k.facility_name', $facility);
        }

        if ($staff !== '') {
            $query->where('k.collection_staff', $staff);
        }

        $items = $query
            ->orderBy('k.patient_name')
            ->get();

        $storeOptions = $this->receiptVisitAreaOptions();
        $facilityOptions = $this->patientFacilityOptions();
        $staffOptions = $this->patientRegisteredStaffOptions();

        return view('staff_portal.home_visit.patients.index', $this->commonViewData($request, [
            'items' => $items,
            'shop' => $shop,
            'facility' => $facility,
            'staff' => $staff,
            'storeOptions' => $storeOptions,
            'facilityOptions' => $facilityOptions,
            'staffOptions' => $staffOptions,
        ]));
    }

    // 患者詳細
    public function edit(Request $request, string $patient_id): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        if (!$this->canEditPatients($this->staffPortalStaffRow($staffId))) {
            abort(403);
        }

        $item = DB::connection('sqlsrv')
            ->table('dbo.hv_kanjya_info as k')
            ->leftJoin('dbo.mx_staffs as s1', 's1.staff_id', '=', 'k.billing_staff')
            ->leftJoin('dbo.mx_staffs as s2', 's2.staff_id', '=', 'k.collection_staff')
            ->select([
                'k.*',
                's1.staff_name as billing_staff_name',
                's2.staff_name as collection_staff_name',
            ])
            ->where('k.patient_id', $patient_id)
            ->first();

        $storeOptions = $this->receiptVisitAreaOptions();
        $facilityOptions = $this->patientFacilityOptions();
        $targetMonth = $this->targetMonth($request, 'target_month');
        $targetMonthStart = $this->targetMonthStartDate($targetMonth);

        $staffOptions = $this->homeVisitStaffOptions($targetMonthStart);

        return view('staff_portal.home_visit.patients.edit', $this->commonViewData($request, [
            'item' => $item,
            'mode' => 'edit',
            'staffOptions' => $staffOptions,
            'storeOptions' => $storeOptions,
            'facilityOptions' => $facilityOptions,
            'consentTypeOptions' => [
                '同意',
                '再同意',
            ],
            'patientOptions' => DB::connection('sqlsrv')
                ->table('dbo.hv_kanjya_info')
                ->select('patient_id', 'patient_name', 'full_address', 'facility_name')
                ->orderBy('patient_name')
                ->get()
                ->map(fn($row): array => [
                    'patient_id' => (int) $row->patient_id,
                    'patient_name' => trim((string) $row->patient_name),
                    'full_address' => trim((string) $row->full_address),
                    'facility_name' => trim((string) $row->facility_name),
                ])
                ->all(),
        ]));
    }

    // 更新
    public function update(Request $request, string $patient_id): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        if (!$this->canEditPatients($this->staffPortalStaffRow($staffId))) {
            abort(403);
        }

        $validated = $request->validate($this->patientValidationRules());

        DB::connection('sqlsrv')
            ->table('dbo.hv_kanjya_info')
            ->where('patient_id', $patient_id)
            ->update([
                'common_id' => $validated['common_id'] ?? null,
                'massage_id' => $validated['massage_id'] ?? null,
                'patient_name' => $validated['patient_name'],
                'collection_staff' => $validated['collection_staff'] ?? null,
                'billing_staff' => $validated['billing_staff'] ?? null,
                'visit_town_name' => $validated['visit_town_name'] ?? null,
                'full_address' => $validated['full_address'] ?? null,
                'facility_name' => $validated['facility_name'] ?? null,
                'visit_store_name' => $validated['visit_store_name'] ?? null,
                'is_massage_target' => $request->boolean('is_massage_target') ? 1 : 0,
                'standard_distance' => $validated['standard_distance'] ?? null,
                'burden_ratio' => $validated['burden_ratio'] ?? null,
                'standard_burden_amount' => $validated['standard_burden_amount'] ?? null,
                'is_excluded_from_count' => $request->boolean('is_excluded_from_count') ? 1 : 0,
                'treatment_fee' => $validated['treatment_fee'] ?? null,
                'window_distance' => $validated['window_distance'] ?? null,
                'subsidy_limit_count' => $validated['subsidy_limit_count'] ?? null,
                'consent_category' => $validated['consent_category'] ?? null,
                'consent_date' => $validated['consent_date'] ?? null,
            ]);

        return redirect()
            ->route('patients.edit', ['patient_id' => $patient_id])
            ->with('status', '更新しました');
    }

    // 削除
    public function delete(Request $request, string $patientNo): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        if (!$this->canEditPatients($this->staffPortalStaffRow($staffId))) {
            abort(403);
        }

        DB::connection('sqlsrv')
            ->table('dbo.hv_kanjya_info')
            ->where('patient_id', $patientNo)
            ->delete();

        return redirect()
            ->route('home_visit.patients.index')
            ->with('status', '削除しました');
    }

    // 新規追加
    public function create(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        if (!$this->canEditPatients($this->staffPortalStaffRow($staffId))) {
            abort(403);
        }

        $staffOptions = $this->patientStaffOptions($request);
        $storeOptions = $this->receiptVisitAreaOptions();

        $facilityOptions = DB::connection('sqlsrv')
            ->table('dbo.hv_kanjya_info')
            ->whereNotNull('facility_name')
            ->where('facility_name', '<>', '')
            ->distinct()
            ->orderBy('facility_name')
            ->pluck('facility_name')
            ->toArray();

        return view('staff_portal.home_visit.patients.edit', $this->commonViewData($request, [
            'item' => null,
            'mode' => 'create',
            'staffOptions' => $staffOptions,
            'storeOptions' => $storeOptions,
            'facilityOptions' => $facilityOptions,
            'consentTypeOptions' => [
                '同意',
                '再同意',
            ],
        ]));
    }

    // 保存
    public function store(Request $request): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);
        if ($staffId === '') {
            return $this->redirectToStaffPortalLogin();
        }

        if (!$this->canEditPatients($this->staffPortalStaffRow($staffId))) {
            abort(403);
        }

        $validated = $request->validate($this->patientValidationRules());

        DB::connection('sqlsrv')
            ->table('dbo.hv_kanjya_info')
            ->insert([
                'common_id' => $validated['common_id'] ?? null,
                'massage_id' => $validated['massage_id'] ?? null,
                'patient_name' => $validated['patient_name'],
                'collection_staff' => $validated['collection_staff'] ?? null,
                'billing_staff' => $validated['billing_staff'] ?? null,
                'visit_town_name' => $validated['visit_town_name'] ?? null,
                'full_address' => $validated['full_address'] ?? null,
                'facility_name' => $validated['facility_name'] ?? null,
                'visit_store_name' => $validated['visit_store_name'] ?? null,
                'is_massage_target' => $request->boolean('is_massage_target') ? 1 : 0,
                'standard_distance' => $validated['standard_distance'] ?? null,
                'burden_ratio' => $validated['burden_ratio'] ?? null,
                'standard_burden_amount' => $validated['standard_burden_amount'] ?? null,
                'is_excluded_from_count' => $request->boolean('is_excluded_from_count') ? 1 : 0,
                'treatment_fee' => $validated['treatment_fee'] ?? null,
                'window_distance' => $validated['window_distance'] ?? null,
                'subsidy_limit_count' => $validated['subsidy_limit_count'] ?? null,
                'consent_category' => $validated['consent_category'] ?? null,
                'consent_date' => $validated['consent_date'] ?? null,
            ]);

        return redirect()
            ->route('home_visit.patients.index')
            ->with('status', '患者データを登録しました。');
    }

    private function patientStaffOptions(Request $request): array
    {
        $targetMonth = $this->targetMonth($request, 'target_month');
        $targetMonthStart = $this->targetMonthStartDate($targetMonth);

        return $this->homeVisitStaffOptions($targetMonthStart);
    }
}

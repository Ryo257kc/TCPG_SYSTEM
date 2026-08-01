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
        DB::connection('sqlsrv')
            ->table('dbo.hv_kanjya_info')
            ->where('patient_id', $patient_id)
            ->update([
                'common_id' => $request->input('common_id'),
                'massage_id' => $request->input('massage_id'),
                'patient_name' => $request->input('patient_name'),
                'collection_staff' => $request->input('collection_staff'),
                'billing_staff' => $request->input('billing_staff'),
                'visit_town_name' => $request->input('visit_town_name'),
                'full_address' => $request->input('full_address'),
                'facility_name' => $request->input('facility_name'),
                'visit_store_name' => $request->input('visit_store_name'),
                'is_massage_target' => $request->input('is_massage_target'),
                'standard_distance' => $request->input('standard_distance'),
                'burden_ratio' => $request->input('burden_ratio'),
                'standard_burden_amount' => $request->input('standard_burden_amount'),
                'is_excluded_from_count' => $request->input('is_excluded_from_count'),
                'treatment_fee' => $request->input('treatment_fee'),
                'window_distance' => $request->input('window_distance'),
                'subsidy_limit_count' => $request->input('subsidy_limit_count'),
                'consent_category' => $request->input('consent_category'),
                'consent_date' => $request->input('consent_date'),
            ]);

        return redirect()
            ->route('patients.edit', ['patient_id' => $patient_id])
            ->with('status', '更新しました');
    }

    // 削除
    public function delete(Request $request, string $patientNo): RedirectResponse
    {
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

        $staffOptions = $this->patientStaffOptions($request);

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
        DB::connection('sqlsrv')
            ->table('dbo.hv_kanjya_info')
            ->insert([
                'common_id' => $request->input('common_id'),
                'massage_id' => $request->input('massage_id'),
                'patient_name' => $request->input('patient_name'),
                'collection_staff' => $request->input('collection_staff'),
                'billing_staff' => $request->input('billing_staff'),
                'visit_town_name' => $request->input('visit_town_name'),
                'full_address' => $request->input('full_address'),
                'facility_name' => $request->input('facility_name'),
                'visit_store_name' => $request->input('visit_store_name'),
                'is_massage_target' => $request->input('is_massage_target', 0),
                'standard_distance' => $request->input('standard_distance'),
                'burden_ratio' => $request->input('burden_ratio'),
                'standard_burden_amount' => $request->input('standard_burden_amount'),
                'is_excluded_from_count' => $request->input('is_excluded_from_count', 0),
                'treatment_fee' => $request->input('treatment_fee'),
                'window_distance' => $request->input('window_distance'),
                'subsidy_limit_count' => $request->input('subsidy_limit_count'),
                'consent_category' => $request->input('consent_category'),
                'consent_date' => $request->input('consent_date'),
            ]);

        return redirect()
            ->route('home_visit.patients.index')
            ->with('status', '患者データを登録しました。');
    }

    // public function show(Request $request, string $patientNo): View|RedirectResponse
    // {
    //     $detail = $this->patientDetailService->find($patientNo);
    //     $options = $this->patientDetailService->editOptions();

    //     if ($detail === null) {
    //         return redirect()->route('patients.index')->withErrors([
    //             'patients' => '患者データが見つかりませんでした。',
    //         ]);
    //     }

    //     return view('patients.show', [
    //         ...$this->headerData($request),
    //         'detail' => $detail,
    //         'facilityOptions' => $options['facilities'],
    //         'consentTypeOptions' => $options['consentTypes'],
    //         'staffOptions' => $options['staffOptions'],
    //     ]);
    // }

    // private function patientStaffOptions(Request $request): array
    // {
    //     $targetMonth = $this->targetMonth($request, 'target_month');
    //     $targetMonthStart = $this->targetMonthStartDate($targetMonth);

    //     return collect($this->homeVisitStaffOptions($targetMonthStart))
    //         ->map(fn(array $row): array => [
    //             'id' => $row['staff_id'],
    //             'label' => $row['display_name_ja'] !== '' ? $row['display_name_ja'] : $row['staff_name'],
    //         ])
    //         ->all();
    // }





    /**
     * @return array{staffId: string, staffName: string}
     */
    // private function headerData(Request $request): array
    // {
    //     $staffId = (string) $request->session()->get('staff_id', '');
    //     $staffName = (string) $request->session()->get('staff_name', $staffId);

    //     return [
    //         'staffId' => $staffId,
    //         'staffName' => $staffName,
    //     ];
    // }
}

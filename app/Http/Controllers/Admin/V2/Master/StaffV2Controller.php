<?php

namespace App\Http\Controllers\Admin\V2\Master;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\Master\StaffV2Service;
use App\Services\Admin\V2\Master\CompanyV2Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffV2Controller extends Controller
{
    public function __construct(private readonly StaffV2Service $service, private readonly CompanyV2Service $companyService)
    {
    }

    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));
        $isCreatingStaff = $request->boolean('new');
        $employmentFilter = trim((string) $request->query('employment_filter', 'active'));
        if (!in_array($employmentFilter, ['active', 'all', 'retired'], true)) {
            $employmentFilter = 'active';
        }

        $companyFilter = trim((string) $request->query('company_filter', ''));

        $rows = $this->service->list($keyword, $employmentFilter, $companyFilter);
        $selectedStaffId = trim((string) $request->query('staff_id', ''));
        if (!$isCreatingStaff && $selectedStaffId === '' && $rows !== []) {
            $selectedStaffId = (string) ($rows[0]['staff_id'] ?? '');
        }
        $selectedTab = trim((string) $request->query('tab', 'staff'));
        if (!in_array($selectedTab, ['staff', 'shift', 'kihon', 'shaho', 'fuyo', 'resident'], true)) {
            $selectedTab = 'staff';
        }
        if ($isCreatingStaff) {
            $selectedTab = 'staff';
        }
        $selectedRow = $isCreatingStaff ? $this->service->blankDetail() : $this->service->detail($selectedStaffId);
        $hasSelectedStaff = $selectedStaffId !== '';

        return view('admin_v2.master.staff.index', [
            'keyword' => $keyword,
            'employmentFilter' => $employmentFilter,
            'companyFilter' => $companyFilter,
            'companyOptions' => $this->companyService->list('')['rows'],
            'rows' => $rows,
            'rowCount' => count($rows),
            'source' => 'mx_staffs',
            'selectedStaffId' => $selectedStaffId,
            'selectedTab' => $selectedTab,
            'selectedRow' => $selectedRow,
            'isCreatingStaff' => $isCreatingStaff,
            'storeOptions' => $this->service->storeOptions(),
            'residentSubmissionOptions' => $this->service->residentSubmissionOptions(),
            'shiftRows' => $hasSelectedStaff && $selectedTab === 'shift' ? $this->service->basicShiftRows($selectedStaffId) : [],
            'kihonRows' => $hasSelectedStaff && $selectedTab === 'kihon' ? $this->service->kihonRows($selectedStaffId) : [],
            'shahoRows' => $hasSelectedStaff && $selectedTab === 'shaho' ? $this->service->shahoRows($selectedStaffId) : [],
            'fuyoRows' => $hasSelectedStaff && $selectedTab === 'fuyo' ? $this->service->fuyoRows($selectedStaffId) : [],
            'residentRows' => $hasSelectedStaff && $selectedTab === 'resident' ? $this->service->residentRows($selectedStaffId) : [],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'staff_id' => ['required', 'string', 'max:50'],
            'q' => ['nullable', 'string', 'max:255'],
            'employment_filter' => ['nullable', 'in:active,all,retired'],
            'company_filter' => ['nullable', 'string', 'max:50'],
            'tab' => ['nullable', 'in:staff,shift,kihon,shaho,fuyo,resident'],
            'staff_name_furi' => ['nullable', 'string', 'max:255'],
            'staff_name' => ['nullable', 'string', 'max:255'],
            'display_name_ja' => ['nullable', 'string', 'max:255'],
            'birthday' => ['nullable', 'string', 'max:50'],
            'sex' => ['nullable', 'string', 'max:50'],
            'section' => ['nullable', 'string', 'max:50'],
            'staff_division' => ['nullable', 'string', 'max:100'],
            'employment' => ['nullable', 'string', 'max:100'],
            'nyu_date' => ['nullable', 'string', 'max:50'],
            'tai_date' => ['nullable', 'string', 'max:50'],
            'my_number' => ['nullable', 'string', 'max:50'],
            'post_num' => ['nullable', 'string', 'max:50'],
            'address_furi' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'home_tel' => ['nullable', 'string', 'max:50'],
            'mobile_tel' => ['nullable', 'string', 'max:50'],
            'head_house' => ['nullable', 'string', 'max:255'],
            'relationship' => ['nullable', 'string', 'max:255'],
            'spouse' => ['nullable', 'in:1'],
            'syaho_num' => ['nullable', 'string', 'max:100'],
            'koyou_num' => ['nullable', 'string', 'max:100'],
            'syaho_seiri_num' => ['nullable', 'string', 'max:100'],
            'kiso_nenkin_num' => ['nullable', 'string', 'max:100'],
            'syaho_date' => ['nullable', 'string', 'max:50'],
            'koyou_date' => ['nullable', 'string', 'max:50'],
            'tax_amount' => ['nullable', 'string', 'max:100'],
            'submission' => ['nullable', 'string', 'max:100'],
            'business_content' => ['nullable', 'string', 'max:255'],
            'has_fixed_term' => ['nullable', 'in:1'],
            'fixed_term_detail' => ['nullable', 'string', 'max:255'],
            'work_schedule_1' => ['nullable', 'string', 'max:255'],
            'work_schedule_2' => ['nullable', 'string', 'max:255'],
            'trial' => ['nullable', 'in:1'],
            'yukyu' => ['nullable', 'in:1'],
            'yukyu_month' => ['nullable', 'string', 'max:100'],
            'working_time' => ['nullable', 'string', 'max:100'],
            'weekly_working_time' => ['nullable', 'string', 'max:100'],
            'year_working_time' => ['nullable', 'string', 'max:100'],
            'car_km' => ['nullable', 'string', 'max:100'],
            'traffic_day' => ['nullable', 'string', 'max:100'],
            'traffic_day_tuika' => ['nullable', 'string', 'max:100'],
            'percentage_1' => ['nullable', 'string', 'max:100'],
            'percentage_2' => ['nullable', 'string', 'max:100'],
            'transfer_purpose' => ['nullable', 'string', 'max:255'],
            'bank_name_1' => ['nullable', 'string', 'max:255'],
            'bank_branch_1' => ['nullable', 'string', 'max:255'],
            'account_type' => ['nullable', 'string', 'max:100'],
            'account_num' => ['nullable', 'string', 'max:100'],
            'bank_name_2' => ['nullable', 'string', 'max:255'],
            'bank_branch_2' => ['nullable', 'string', 'max:255'],
            'account_type2' => ['nullable', 'string', 'max:100'],
            'account_num2' => ['nullable', 'string', 'max:100'],
            'change_history' => ['nullable', 'string'],
            'is_admin' => ['nullable', 'in:1'],
            'oushin_staff' => ['nullable', 'in:1'],
            'front_staff' => ['nullable', 'in:1'],
            'is_accounting_user' => ['nullable', 'in:1'],
            'is_payment_check_user' => ['nullable', 'in:1'],
            'is_visit_management_user' => ['nullable', 'in:1'],
            'is_view_only_user' => ['nullable', 'in:1'],
            'is_store_management_user' => ['nullable', 'in:1'],
            'is_daily_report_user' => ['nullable', 'in:1'],
            'password' => ['nullable', 'string', 'max:255'],
            'memo' => ['nullable', 'string'],
            'syaho' => ['nullable', 'in:1'],
            'koyou' => ['nullable', 'in:1'],
        ]);

        $this->service->update($v);

        return redirect()->route('admin.master.staff', [
            'q' => trim((string) ($v['q'] ?? '')),
            'employment_filter' => trim((string) ($v['employment_filter'] ?? 'active')),
            'company_filter' => trim((string) ($v['company_filter'] ?? '')),
            'staff_id' => trim((string) $v['staff_id']),
            'tab' => trim((string) ($v['tab'] ?? 'staff')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'staff_id' => ['required', 'string', 'max:50'],
            'q' => ['nullable', 'string', 'max:255'],
            'employment_filter' => ['nullable', 'in:active,all,retired'],
            'company_filter' => ['nullable', 'string', 'max:50'],
            'tab' => ['nullable', 'in:staff,shift,kihon,shaho,fuyo,resident'],
            'staff_name_furi' => ['nullable', 'string', 'max:255'],
            'staff_name' => ['nullable', 'string', 'max:255'],
            'display_name_ja' => ['nullable', 'string', 'max:255'],
            'birthday' => ['nullable', 'string', 'max:50'],
            'sex' => ['nullable', 'string', 'max:50'],
            'section' => ['nullable', 'string', 'max:50'],
            'staff_division' => ['nullable', 'string', 'max:100'],
            'employment' => ['nullable', 'string', 'max:100'],
            'nyu_date' => ['nullable', 'string', 'max:50'],
            'tai_date' => ['nullable', 'string', 'max:50'],
            'my_number' => ['nullable', 'string', 'max:50'],
            'post_num' => ['nullable', 'string', 'max:50'],
            'address_furi' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'home_tel' => ['nullable', 'string', 'max:50'],
            'mobile_tel' => ['nullable', 'string', 'max:50'],
            'head_house' => ['nullable', 'string', 'max:255'],
            'relationship' => ['nullable', 'string', 'max:255'],
            'spouse' => ['nullable', 'in:1'],
            'syaho_num' => ['nullable', 'string', 'max:100'],
            'koyou_num' => ['nullable', 'string', 'max:100'],
            'syaho_seiri_num' => ['nullable', 'string', 'max:100'],
            'kiso_nenkin_num' => ['nullable', 'string', 'max:100'],
            'syaho_date' => ['nullable', 'string', 'max:50'],
            'koyou_date' => ['nullable', 'string', 'max:50'],
            'tax_amount' => ['nullable', 'string', 'max:100'],
            'submission' => ['nullable', 'string', 'max:100'],
            'business_content' => ['nullable', 'string', 'max:255'],
            'has_fixed_term' => ['nullable', 'in:1'],
            'fixed_term_detail' => ['nullable', 'string', 'max:255'],
            'work_schedule_1' => ['nullable', 'string', 'max:255'],
            'work_schedule_2' => ['nullable', 'string', 'max:255'],
            'trial' => ['nullable', 'in:1'],
            'yukyu' => ['nullable', 'in:1'],
            'yukyu_month' => ['nullable', 'string', 'max:100'],
            'working_time' => ['nullable', 'string', 'max:100'],
            'weekly_working_time' => ['nullable', 'string', 'max:100'],
            'year_working_time' => ['nullable', 'string', 'max:100'],
            'car_km' => ['nullable', 'string', 'max:100'],
            'traffic_day' => ['nullable', 'string', 'max:100'],
            'traffic_day_tuika' => ['nullable', 'string', 'max:100'],
            'percentage_1' => ['nullable', 'string', 'max:100'],
            'percentage_2' => ['nullable', 'string', 'max:100'],
            'transfer_purpose' => ['nullable', 'string', 'max:255'],
            'bank_name_1' => ['nullable', 'string', 'max:255'],
            'bank_branch_1' => ['nullable', 'string', 'max:255'],
            'account_type' => ['nullable', 'string', 'max:100'],
            'account_num' => ['nullable', 'string', 'max:100'],
            'bank_name_2' => ['nullable', 'string', 'max:255'],
            'bank_branch_2' => ['nullable', 'string', 'max:255'],
            'account_type2' => ['nullable', 'string', 'max:100'],
            'account_num2' => ['nullable', 'string', 'max:100'],
            'change_history' => ['nullable', 'string'],
            'is_admin' => ['nullable', 'in:1'],
            'oushin_staff' => ['nullable', 'in:1'],
            'front_staff' => ['nullable', 'in:1'],
            'is_accounting_user' => ['nullable', 'in:1'],
            'is_payment_check_user' => ['nullable', 'in:1'],
            'is_visit_management_user' => ['nullable', 'in:1'],
            'is_view_only_user' => ['nullable', 'in:1'],
            'is_store_management_user' => ['nullable', 'in:1'],
            'is_daily_report_user' => ['nullable', 'in:1'],
            'password' => ['nullable', 'string', 'max:255'],
            'memo' => ['nullable', 'string'],
            'syaho' => ['nullable', 'in:1'],
            'koyou' => ['nullable', 'in:1'],
        ]);

        $this->service->create($v);

        return redirect()->route('admin.master.staff', [
            'q' => trim((string) ($v['q'] ?? '')),
            'employment_filter' => trim((string) ($v['employment_filter'] ?? 'active')),
            'company_filter' => trim((string) ($v['company_filter'] ?? '')),
            'staff_id' => trim((string) $v['staff_id']),
            'tab' => 'staff',
        ]);
    }

    public function updateSubmission(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'staff_id' => ['required', 'string', 'max:50'],
            'q' => ['nullable', 'string', 'max:255'],
            'employment_filter' => ['nullable', 'in:active,all,retired'],
            'company_filter' => ['nullable', 'string', 'max:50'],
            'submission' => ['nullable', 'string', 'max:100'],
            'addressee_no' => ['nullable', 'string', 'max:100'],
        ]);

        $this->service->updateSubmission($v);

        return $this->redirectToResident($v, '住民税提出先を保存しました。');
    }

    public function updateInsurance(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'staff_id' => ['required', 'string', 'max:50'],
            'q' => ['nullable', 'string', 'max:255'],
            'employment_filter' => ['nullable', 'in:active,all,retired'],
            'company_filter' => ['nullable', 'string', 'max:50'],
            'syaho_num' => ['nullable', 'string', 'max:100'],
            'koyou_num' => ['nullable', 'string', 'max:100'],
            'syaho_seiri_num' => ['nullable', 'string', 'max:100'],
            'kiso_nenkin_num' => ['nullable', 'string', 'max:100'],
            'syaho' => ['nullable', 'in:1'],
            'koyou' => ['nullable', 'in:1'],
            'syaho_date' => ['nullable', 'string', 'max:50'],
            'koyou_date' => ['nullable', 'string', 'max:50'],
        ]);

        $this->service->updateInsurance($v);

        return $this->redirectToShaho($v, '保険情報を保存しました。');
    }

    public function storeKihon(Request $request): RedirectResponse
    {
        $v = $request->validate($this->kihonRules());

        $this->service->createKihon($v);

        return $this->redirectToKihon($v);
    }

    public function storeBasicShift(Request $request): RedirectResponse
    {
        $v = $request->validate($this->basicShiftRules());

        $this->service->createBasicShift($v);

        return $this->redirectToBasicShift($v);
    }

    public function updateBasicShift(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'shift_no' => ['required', 'string', 'max:50'],
            '_action' => ['nullable', 'in:register,clear'],
            ...$this->basicShiftRules(),
        ]);

        $this->service->updateBasicShift($v, (string) ($v['_action'] ?? 'register') === 'clear');

        return $this->redirectToBasicShift($v);
    }

    public function updateKihon(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'kihon_no' => ['required', 'string', 'max:50'],
            ...$this->kihonRules(),
        ]);

        $this->service->updateKihon($v);

        return $this->redirectToKihon($v);
    }

    public function deleteKihon(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'kihon_no' => ['required', 'string', 'max:50'],
            'staff_id' => ['required', 'string', 'max:50'],
            'q' => ['nullable', 'string', 'max:255'],
            'employment_filter' => ['nullable', 'in:active,all,retired'],
            'company_filter' => ['nullable', 'string', 'max:50'],
        ]);

        $this->service->deleteKihon($v);

        return $this->redirectToKihon($v);
    }

    public function storeShaho(Request $request): RedirectResponse
    {
        $v = $request->validate($this->shahoRules());

        $this->service->createShaho($v);

        return $this->redirectToShaho($v);
    }

    public function updateShaho(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'staff_shou_id' => ['required', 'string', 'max:50'],
            ...$this->shahoRules(),
        ]);

        $this->service->updateShaho($v);

        return $this->redirectToShaho($v);
    }

    public function deleteShaho(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'staff_shou_id' => ['required', 'string', 'max:50'],
            'staff_id' => ['required', 'string', 'max:50'],
            'q' => ['nullable', 'string', 'max:255'],
            'employment_filter' => ['nullable', 'in:active,all,retired'],
            'company_filter' => ['nullable', 'string', 'max:50'],
        ]);

        $this->service->deleteShaho($v);

        return $this->redirectToShaho($v);
    }


    public function storeResident(Request $request): RedirectResponse
    {
        $v = $request->validate($this->residentRules());

        $this->service->createResident($v);

        return $this->redirectToResident($v, '住民税を登録しました。');
    }

    public function updateResident(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'resident_no' => ['required', 'string', 'max:50'],
            ...$this->residentRules(),
        ]);

        $this->service->updateResident($v);

        return $this->redirectToResident($v, '住民税を保存しました。');
    }

    public function deleteResident(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'resident_no' => ['required', 'string', 'max:50'],
            'staff_id' => ['required', 'string', 'max:50'],
            'q' => ['nullable', 'string', 'max:255'],
            'employment_filter' => ['nullable', 'in:active,all,retired'],
            'company_filter' => ['nullable', 'string', 'max:50'],
        ]);

        $this->service->deleteResident($v);

        return $this->redirectToResident($v, '住民税を削除しました。');
    }

    public function storeFuyo(Request $request): RedirectResponse
    {
        $v = $request->validate($this->fuyoRules(), $this->fuyoMessages());

        $this->service->createFuyo($v);

        return $this->redirectToFuyo($v);
    }

    public function updateFuyo(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'fuyo_no' => ['required', 'string', 'max:50'],
            ...$this->fuyoRules(),
        ], $this->fuyoMessages());

        $this->service->updateFuyo($v);

        return $this->redirectToFuyo($v);
    }

    public function deleteFuyo(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'fuyo_no' => ['required', 'string', 'max:50'],
            'staff_id' => ['required', 'string', 'max:50'],
            'q' => ['nullable', 'string', 'max:255'],
            'employment_filter' => ['nullable', 'in:active,all,retired'],
            'company_filter' => ['nullable', 'string', 'max:50'],
        ]);

        $this->service->deleteFuyo($v);

        return $this->redirectToFuyo($v);
    }

    /** @return array<string, array<int, string>> */
    private function kihonRules(): array
    {
        return [
            'staff_id' => ['required', 'string', 'max:50'],
            'q' => ['nullable', 'string', 'max:255'],
            'employment_filter' => ['nullable', 'in:active,all,retired'],
            'company_filter' => ['nullable', 'string', 'max:50'],
            'decision_date' => ['required', 'string', 'max:50'],
            'monthly_salary' => ['nullable', 'string', 'max:100'],
            'hourly_pay' => ['nullable', 'string', 'max:100'],
            'hourly_salary' => ['nullable', 'string', 'max:100'],
            'executive_remu' => ['nullable', 'string', 'max:100'],
            'position_allow' => ['nullable', 'string', 'max:100'],
            'duties_allow' => ['nullable', 'string', 'max:100'],
            'qualification_allow' => ['nullable', 'string', 'max:100'],
            'claim_allow' => ['nullable', 'string', 'max:100'],
            'traffic_pay' => ['nullable', 'string', 'max:100'],
            'adjustment_add' => ['nullable', 'string', 'max:100'],
            'rent_subsidies' => ['nullable', 'string', 'max:100'],
            'rent_pay' => ['nullable', 'string', 'max:100'],
            'adjustment_pay' => ['nullable', 'string', 'max:100'],
            'fixed_overtime' => ['nullable', 'string', 'max:100'],
        ];
    }

    /** @return array<string, array<int, string>> */
    private function basicShiftRules(): array
    {
        return [
            'staff_id' => ['required', 'string', 'max:50'],
            'q' => ['nullable', 'string', 'max:255'],
            'employment_filter' => ['nullable', 'in:active,all,retired'],
            'company_filter' => ['nullable', 'string', 'max:50'],
            'week' => ['required', 'string', 'max:20'],
            'shift_start' => ['nullable', 'string', 'max:20'],
            'shift_exit' => ['nullable', 'string', 'max:20'],
            'shift_in_out' => ['nullable', 'string', 'max:20'],
            'shift_end' => ['nullable', 'string', 'max:20'],
            'shop_code' => ['nullable', 'string', 'max:50'],
        ];
    }

    private function redirectToKihon(array $values): RedirectResponse
    {
        return redirect()->route('admin.master.staff', [
            'q' => trim((string) ($values['q'] ?? '')),
            'employment_filter' => trim((string) ($values['employment_filter'] ?? 'active')),
            'company_filter' => trim((string) ($values['company_filter'] ?? '')),
            'staff_id' => trim((string) $values['staff_id']),
            'tab' => 'kihon',
        ]);
    }

    private function redirectToBasicShift(array $values): RedirectResponse
    {
        return redirect()->route('admin.master.staff', [
            'q' => trim((string) ($values['q'] ?? '')),
            'employment_filter' => trim((string) ($values['employment_filter'] ?? 'active')),
            'company_filter' => trim((string) ($values['company_filter'] ?? '')),
            'staff_id' => trim((string) $values['staff_id']),
            'tab' => 'shift',
        ]);
    }

    /** @return array<string, array<int, string>> */
    private function residentRules(): array
    {
        return [
            'staff_id' => ['required', 'string', 'max:50'],
            'q' => ['nullable', 'string', 'max:255'],
            'employment_filter' => ['nullable', 'in:active,all,retired'],
            'company_filter' => ['nullable', 'string', 'max:50'],
            'target_month' => ['required', 'date_format:Y-m'],
            'resident_tax1' => ['nullable', 'string', 'max:100'],
            'resident_tax2' => ['nullable', 'string', 'max:100'],
            'resident_tax3' => ['nullable', 'string', 'max:100'],
            'resident_tax4' => ['nullable', 'string', 'max:100'],
            'resident_tax5' => ['nullable', 'string', 'max:100'],
            'resident_tax6' => ['nullable', 'string', 'max:100'],
            'resident_tax7' => ['nullable', 'string', 'max:100'],
            'resident_tax8' => ['nullable', 'string', 'max:100'],
            'resident_tax9' => ['nullable', 'string', 'max:100'],
            'resident_tax10' => ['nullable', 'string', 'max:100'],
            'resident_tax11' => ['nullable', 'string', 'max:100'],
            'resident_tax12' => ['nullable', 'string', 'max:100'],
            'memo' => ['nullable', 'string'],
        ];
    }

    /** @return array<string, array<int, string>> */
    private function fuyoRules(): array
    {
        return [
            'staff_id' => ['required', 'string', 'max:50'],
            'q' => ['nullable', 'string', 'max:255'],
            'employment_filter' => ['nullable', 'in:active,all,retired'],
            'company_filter' => ['nullable', 'string', 'max:50'],
            'deduction_target' => ['nullable', 'in:1'],
            'fuyo_name_furi' => ['nullable', 'string', 'max:255'],
            'fuyo_name' => ['nullable', 'string', 'max:255'],
            'fuyo_relationship' => ['nullable', 'string', 'max:100'],
            'kyojyu' => ['nullable', 'string', 'max:100'],
            'fuyo_address' => ['nullable', 'string', 'max:1000'],
            'failure_notebook' => ['nullable', 'string', 'max:255'],
            'failure_judgment' => ['nullable', 'string', 'max:100'],
            'fuyo_birthday' => ['nullable', 'string', 'max:50'],
            'fuyo_sex' => ['nullable', 'string', 'max:50'],
            'fuyo_my_number' => ['nullable', 'string', 'max:12'],
            'fuyo_shunyu' => ['nullable', 'string', 'max:100'],
            'registration_date' => ['required', 'string', 'max:50'],
            'widow' => ['nullable', 'in:1'],
        ];
    }

    /** @return array<string, string> */
    private function fuyoMessages(): array
    {
        return [
            'fuyo_my_number.max' => 'マイナンバーは12桁以内で入力してください。',
        ];
    }
    /** @return array<string, array<int, string>> */
    private function shahoRules(): array
    {
        return [
            'staff_id' => ['required', 'string', 'max:50'],
            'q' => ['nullable', 'string', 'max:255'],
            'employment_filter' => ['nullable', 'in:active,all,retired'],
            'company_filter' => ['nullable', 'string', 'max:50'],
            'raise_year' => ['required', 'string', 'max:50'],
            'kenpo_monthly_amo' => ['nullable', 'string', 'max:100'],
            'kounen_monthly_amo' => ['nullable', 'string', 'max:100'],
            'kenpo_toukyu' => ['nullable', 'string', 'max:100'],
            'kounen_toukyu' => ['nullable', 'string', 'max:100'],
        ];
    }

    private function redirectToResident(array $values, string $status = ''): RedirectResponse
    {
        $redirect = redirect()->route('admin.master.staff', [
            'q' => trim((string) ($values['q'] ?? '')),
            'employment_filter' => trim((string) ($values['employment_filter'] ?? 'active')),
            'company_filter' => trim((string) ($values['company_filter'] ?? '')),
            'staff_id' => trim((string) $values['staff_id']),
            'tab' => 'resident',
        ]);

        return $status !== '' ? $redirect->with('status', $status) : $redirect;
    }

    private function redirectToFuyo(array $values): RedirectResponse
    {
        return redirect()->route('admin.master.staff', [
            'q' => trim((string) ($values['q'] ?? '')),
            'employment_filter' => trim((string) ($values['employment_filter'] ?? 'active')),
            'company_filter' => trim((string) ($values['company_filter'] ?? '')),
            'staff_id' => trim((string) $values['staff_id']),
            'tab' => 'fuyo',
        ]);
    }

    private function redirectToShaho(array $values, string $status = ''): RedirectResponse
    {
        $redirect = redirect()->route('admin.master.staff', [
            'q' => trim((string) ($values['q'] ?? '')),
            'employment_filter' => trim((string) ($values['employment_filter'] ?? 'active')),
            'company_filter' => trim((string) ($values['company_filter'] ?? '')),
            'staff_id' => trim((string) $values['staff_id']),
            'tab' => 'shaho',
        ]);

        return $status !== '' ? $redirect->with('status', $status) : $redirect;
    }
}

<?php

namespace App\Http\Controllers\Admin\V2\Master;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\Master\StaffV2Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffV2Controller extends Controller
{
    public function __construct(private readonly StaffV2Service $service)
    {
    }

    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));
        $employmentFilter = trim((string) $request->query('employment_filter', 'active'));
        if (!in_array($employmentFilter, ['active', 'all', 'retired'], true)) {
            $employmentFilter = 'active';
        }

        $rows = $this->service->list($keyword, $employmentFilter);
        $selectedStaffId = trim((string) $request->query('staff_id', ''));
        if ($selectedStaffId === '' && $rows !== []) {
            $selectedStaffId = (string) ($rows[0]['staff_id'] ?? '');
        }
        $selectedTab = trim((string) $request->query('tab', 'staff'));
        if (!in_array($selectedTab, ['staff', 'shift', 'kihon', 'fuyo', 'resident'], true)) {
            $selectedTab = 'staff';
        }
        $selectedRow = $this->service->detail($selectedStaffId);

        return view('admin_v2.master.staff.index', [
            'keyword' => $keyword,
            'employmentFilter' => $employmentFilter,
            'rows' => $rows,
            'rowCount' => count($rows),
            'source' => 'mx_staffs',
            'selectedStaffId' => $selectedStaffId,
            'selectedTab' => $selectedTab,
            'selectedRow' => $selectedRow,
            'storeOptions' => $this->service->storeOptions(),
            'fieldLabels' => $this->service->fieldLabels(),
            'shiftRows' => $selectedStaffId !== '' ? $this->service->relatedRows($selectedStaffId, 'mx_kihon_shifts', ['id', 'kihon_shift_no']) : ['columns' => [], 'rows' => []],
            'kihonRows' => $selectedStaffId !== '' ? $this->service->relatedRows($selectedStaffId, 'mx_kihon', ['decision_date', 'kihon_no']) : ['columns' => [], 'rows' => []],
            'fuyoRows' => $selectedStaffId !== '' ? $this->service->relatedRows($selectedStaffId, 'mx_fuyo', ['registration_date', 'fuyo_no']) : ['columns' => [], 'rows' => []],
            'residentRows' => $selectedStaffId !== '' ? $this->service->relatedRows($selectedStaffId, 'mx_resident', ['target_month', 'resident_no']) : ['columns' => [], 'rows' => []],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'staff_id' => ['required', 'string', 'max:50'],
            'q' => ['nullable', 'string', 'max:255'],
            'employment_filter' => ['nullable', 'in:active,all,retired'],
            'tab' => ['nullable', 'in:staff,shift,kihon,fuyo,resident'],
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
            'oushin_staff' => ['nullable', 'in:1'],
            'front_staff' => ['nullable', 'in:1'],
            'is_accounting_user' => ['nullable', 'in:1'],
            'is_payment_check_user' => ['nullable', 'in:1'],
            'is_visit_management_user' => ['nullable', 'in:1'],
            'is_view_only_user' => ['nullable', 'in:1'],
            'is_store_management_user' => ['nullable', 'in:1'],
            'is_daily_report_user' => ['nullable', 'in:1'],
            'memo' => ['nullable', 'string'],
            'syaho' => ['nullable', 'in:1'],
            'koyou' => ['nullable', 'in:1'],
        ]);

        $this->service->update($v);

        return redirect()->route('admin.master.staff', [
            'q' => trim((string) ($v['q'] ?? '')),
            'employment_filter' => trim((string) ($v['employment_filter'] ?? 'active')),
            'staff_id' => trim((string) $v['staff_id']),
            'tab' => trim((string) ($v['tab'] ?? 'staff')),
        ]);
    }
}

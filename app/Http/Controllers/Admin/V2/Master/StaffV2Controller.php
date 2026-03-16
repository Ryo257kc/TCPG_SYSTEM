<?php

namespace App\Http\Controllers\Admin\V2\Master;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\Master\StaffV2Service;
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
            'fieldLabels' => $this->service->fieldLabels(),
            'shiftRows' => $selectedStaffId !== '' ? $this->service->relatedRows($selectedStaffId, 'mx_kihon_shifts', ['id', 'kihon_shift_no']) : ['columns' => [], 'rows' => []],
            'kihonRows' => $selectedStaffId !== '' ? $this->service->relatedRows($selectedStaffId, 'mx_kihon', ['decision_date', 'kihon_no']) : ['columns' => [], 'rows' => []],
            'fuyoRows' => $selectedStaffId !== '' ? $this->service->relatedRows($selectedStaffId, 'mx_fuyo', ['registration_date', 'fuyo_no']) : ['columns' => [], 'rows' => []],
            'residentRows' => $selectedStaffId !== '' ? $this->service->relatedRows($selectedStaffId, 'mx_resident', ['target_month', 'resident_no']) : ['columns' => [], 'rows' => []],
        ]);
    }
}

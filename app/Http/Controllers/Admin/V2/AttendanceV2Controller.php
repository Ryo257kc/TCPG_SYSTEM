<?php

namespace App\Http\Controllers\Admin\V2;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\Attendance\AttendanceV2CompanyService;
use App\Services\Admin\V2\Attendance\AttendanceV2MetricService;
use App\Services\Admin\V2\Attendance\AttendanceV2MonthService;
use App\Services\Admin\V2\Attendance\AttendanceV2StaffService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceV2Controller extends Controller
{
    public function __construct(
        private readonly AttendanceV2MonthService $monthService,
        private readonly AttendanceV2CompanyService $companyService,
        private readonly AttendanceV2StaffService $staffService,
        private readonly AttendanceV2MetricService $metricService,
    ) {
    }

    public function index(Request $request): View
    {
        $availableMonths = $this->monthService->availableMonths();
        $selectedMonth = $this->monthService->normalize((string) $request->query('month', ''), $availableMonths);

        [$year, $month] = array_map('intval', explode('-', $selectedMonth));
        $fromDate = sprintf('%04d-%02d-01', $year, $month);
        $toDate = date('Y-m-t', strtotime($fromDate));

        $companies = $this->companyService->companies();
        $selectedCompanyId = trim((string) $request->query('company_id', ''));
        $selectedStaffId = trim((string) $request->query('staff_id', ''));

        $staffRows = $this->staffService->staffs($fromDate, $toDate, $selectedCompanyId);
        if ($selectedStaffId !== '' && !in_array($selectedStaffId, array_column($staffRows, 'staff_id'), true)) {
            $selectedStaffId = '';
        }

        $metricMap = $this->metricService->metricMap($year, $month);
        $rows = $this->metricService->mergeRows($staffRows, $metricMap, $selectedStaffId);

        return view('admin_v2.attendance.index', [
            'availableMonths' => $availableMonths,
            'selectedMonth' => $selectedMonth,
            'companyOptions' => $companies,
            'selectedCompanyId' => $selectedCompanyId,
            'selectedStaffId' => $selectedStaffId,
            'staffRows' => $staffRows,
            'rows' => $rows,
        ]);
    }
}

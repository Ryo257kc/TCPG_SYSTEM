<?php

namespace App\Http\Controllers\Admin\V2;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\Payroll\PayrollV2AllowanceLabelService;
use App\Services\Admin\V2\Payroll\PayrollV2CompanyService;
use App\Services\Admin\V2\Payroll\PayrollV2EmploymentInsuranceService;
use App\Services\Admin\V2\Payroll\PayrollV2IncomeTaxService;
use App\Services\Admin\V2\Payroll\PayrollV2KihonService;
use App\Services\Admin\V2\Payroll\PayrollV2MonthService;
use App\Services\Admin\V2\Payroll\PayrollV2OvertimeDeductionService;
use App\Services\Admin\V2\Payroll\PayrollV2RecalculateService;
use App\Services\Admin\V2\Payroll\PayrollV2ResidentService;
use App\Services\Admin\V2\Payroll\PayrollV2ShahoService;
use App\Services\Admin\V2\Payroll\PayrollV2StaffMasterService;
use App\Services\Admin\V2\Payroll\PayrollV2StaffService;
use App\Services\Admin\V2\Payroll\PayrollV2SummaryService;
use App\Services\Admin\V2\Payroll\PayrollV2UpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollV2Controller extends Controller
{
    public function __construct(
        private readonly PayrollV2MonthService $monthService,
        private readonly PayrollV2CompanyService $companyService,
        private readonly PayrollV2StaffService $staffService,
        private readonly PayrollV2SummaryService $summaryService,
        private readonly PayrollV2KihonService $kihonService,
        private readonly PayrollV2StaffMasterService $staffMasterService,
        private readonly PayrollV2ShahoService $shahoService,
        private readonly PayrollV2ResidentService $residentService,
        private readonly PayrollV2AllowanceLabelService $allowanceLabelService,
        private readonly PayrollV2UpdateService $updateService,
        private readonly PayrollV2RecalculateService $recalculateService,
        private readonly PayrollV2EmploymentInsuranceService $employmentInsuranceService,
        private readonly PayrollV2IncomeTaxService $incomeTaxService,
        private readonly PayrollV2OvertimeDeductionService $overtimeDeductionService,
    ) {
    }

    public function index(Request $request): View
    {
        $availableMonths = $this->monthService->availableMonths();
        $selectedMonth = $this->monthService->normalize((string) $request->query('month', ''), $availableMonths);

        [$year, $month] = array_map('intval', explode('-', $selectedMonth));

        $companyOptions = $this->companyService->companies();
        $selectedCompanyId = trim((string) $request->query('company_id', ''));
        $selectedStaffId = trim((string) $request->query('staff_id', ''));

        if ($selectedCompanyId !== '' && !in_array($selectedCompanyId, $companyOptions, true)) {
            $selectedCompanyId = '';
        }

        $staffRows = $this->staffService->staffs($selectedCompanyId);
        $summaryMap = $this->summaryService->summaryMap($year, $month);
        $previousSummaryMap = $this->summaryService->previousSummaryMap($year, $month);
        $kihonMap = $this->kihonService->map($year, $month);
        $staffMasterMap = $this->staffMasterService->map();
        $shahoMap = $this->shahoService->map($year, $month);
        $residentMap = $this->residentService->map($year, $month);

        // Usually we show only staff that has payroll rows in the selected month.
        // If month data is empty (e.g. after month fallback mismatch), keep staff list
        // so the UI does not collapse to an empty main panel.
        if (!empty($summaryMap)) {
            $staffRows = array_values(array_filter(
                $staffRows,
                static fn (array $staff): bool => isset($summaryMap[$staff['staff_id']])
            ));
        }

        if ($selectedStaffId !== '' && !in_array($selectedStaffId, array_column($staffRows, 'staff_id'), true)) {
            $selectedStaffId = '';
        }

        $rows = $this->summaryService->mergeRows(
            $staffRows,
            $summaryMap,
            $previousSummaryMap,
            $kihonMap,
            $staffMasterMap,
            $shahoMap,
            $residentMap,
            ''
        );

        $allowanceEntries = $this->allowanceLabelService->entries();
        $labelOverrides = $this->allowanceLabelService->labelMap();

        return view('admin_v2.payroll.index', [
            'availableMonths' => $availableMonths,
            'selectedMonth' => $selectedMonth,
            'companyOptions' => $companyOptions,
            'selectedCompanyId' => $selectedCompanyId,
            'selectedStaffId' => $selectedStaffId,
            'staffRows' => $staffRows,
            'rows' => $rows,
            'allowanceEntries' => $allowanceEntries,
            'labelOverrides' => $labelOverrides,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $v = $request->validate([
            'staff_id' => ['required', 'string', 'max:20'],
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'values' => ['required', 'array'],
            'company_id' => ['nullable', 'string', 'max:200'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        $updated = $this->updateService->save((string) $v['staff_id'], $year, $month, (array) $v['values'], (string) ($v['company_id'] ?? ''));

        return response()->json([
            'ok' => true,
            'updated' => $updated,
        ]);
    }

    public function recalculate(Request $request): JsonResponse
    {
        $v = $request->validate([
            'staff_id' => ['required', 'string', 'max:20'],
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'company_id' => ['nullable', 'string', 'max:200'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        $updated = $this->recalculateService->recalculate(
            (string) $v['staff_id'],
            $year,
            $month,
            (string) ($v['company_id'] ?? '')
        );

        return response()->json([
            'ok' => true,
            'updated' => $updated,
        ]);
    }

    public function calcKoyou(Request $request): JsonResponse
    {
        $v = $request->validate([
            'staff_id' => ['required', 'string', 'max:20'],
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        $updated = $this->employmentInsuranceService->recalculate((string) $v['staff_id'], $year, $month);

        return response()->json([
            'ok' => true,
            'updated' => $updated,
        ]);
    }

    public function calcOvertimeDeduction(Request $request): JsonResponse
    {
        $v = $request->validate([
            'staff_id' => ['required', 'string', 'max:20'],
            'month' => ['required', 'regex:/^\\d{4}-\\d{2}$/'],
            'company_id' => ['nullable', 'string', 'max:200'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        $companyId = (string) ($v['company_id'] ?? '');

        $updated = 0;
        $updated += (int) $this->overtimeDeductionService->recalculate(
            (string) $v['staff_id'],
            $year,
            $month,
            $companyId
        );
        $updated += (int) $this->employmentInsuranceService->recalculate((string) $v['staff_id'], $year, $month);
        $updated += (int) $this->incomeTaxService->recalculate((string) $v['staff_id'], $year, $month);
        $updated += (int) $this->updateService->refreshTotals((string) $v['staff_id'], $year, $month, $companyId);

        return response()->json([
            'ok' => true,
            'updated' => $updated,
        ]);
    }

    public function calcIncomeTax(Request $request): JsonResponse
    {
        $v = $request->validate([
            'staff_id' => ['required', 'string', 'max:20'],
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        $result = $this->incomeTaxService->recalculateWithTrace((string) $v['staff_id'], $year, $month);

        return response()->json([
            'ok' => true,
            'updated' => (int) ($result['updated'] ?? 0),
            'trace' => $result['trace'] ?? [],
        ]);
    }

    public function confirm(Request $request): JsonResponse
    {
        $v = $request->validate([
            'staff_id' => ['required', 'string', 'max:20'],
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'checked' => ['required', 'boolean'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        $checked = ((int) $v['checked']) === 1;
        $attendanceChecked = $this->updateService->isAttendanceChecked((string) $v['staff_id'], $year, $month);

        if ($checked && !$attendanceChecked) {
            return response()->json([
                'ok' => false,
                'message' => '勤怠未確定のため給与確定できません。',
            ], 422);
        }

        $updated = $this->updateService->setPayrollConfirmed((string) $v['staff_id'], $year, $month, $checked);

        return response()->json([
            'ok' => true,
            'updated' => (int) $updated,
            'checked' => $checked,
        ]);
    }
}

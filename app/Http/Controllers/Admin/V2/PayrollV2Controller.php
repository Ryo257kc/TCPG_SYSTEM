<?php

namespace App\Http\Controllers\Admin\V2;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\Attendance\AttendanceV2ListSummaryService;
use App\Services\Admin\V2\Attendance\AttendanceV2ConfirmedStateService;
use App\Services\Admin\V2\Payroll\PayrollV2AllowanceLabelService;
use App\Services\Admin\V2\Payroll\PayrollV2CompanyService;
use App\Services\Admin\V2\Payroll\PayrollV2CreateCandidatesService;
use App\Services\Admin\V2\Payroll\PayrollV2CreateService;
use App\Services\Admin\V2\Payroll\PayrollV2DeleteService;
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
        private readonly PayrollV2CreateCandidatesService $createCandidatesService,
        private readonly PayrollV2CreateService $createService,
        private readonly PayrollV2DeleteService $deleteService,
        private readonly PayrollV2StaffService $staffService,
        private readonly PayrollV2SummaryService $summaryService,
        private readonly PayrollV2KihonService $kihonService,
        private readonly PayrollV2StaffMasterService $staffMasterService,
        private readonly PayrollV2ShahoService $shahoService,
        private readonly PayrollV2ResidentService $residentService,
        private readonly PayrollV2AllowanceLabelService $allowanceLabelService,
        private readonly AttendanceV2ListSummaryService $attendanceListSummaryService,
        private readonly AttendanceV2ConfirmedStateService $confirmedStateService,
        private readonly PayrollV2UpdateService $updateService,
        private readonly PayrollV2RecalculateService $recalculateService,
        private readonly PayrollV2EmploymentInsuranceService $employmentInsuranceService,
        private readonly PayrollV2IncomeTaxService $incomeTaxService,
        private readonly PayrollV2OvertimeDeductionService $overtimeDeductionService,
    ) {
    }

    public function index(Request $request): View
    {
        $availablePaymentDates = $this->monthService->availablePaymentDates();
        $selectedPaymentDate = $this->monthService->normalizePaymentDate((string) $request->query('payment_date', ''), $availablePaymentDates);
        $selectedMonth = $this->monthService->monthFromPaymentDate($selectedPaymentDate);

        [$year, $month] = array_map('intval', explode('-', $selectedMonth));
        [$attendanceYear, $attendanceMonth] = $month === 1
            ? [$year - 1, 12]
            : [$year, $month - 1];

        $companyOptions = $this->companyService->companies();
        $selectedCompanyId = trim((string) $request->query('company_id', ''));
        $selectedStaffId = trim((string) $request->query('staff_id', ''));

        if ($selectedCompanyId !== '' && !in_array($selectedCompanyId, $companyOptions, true)) {
            $selectedCompanyId = '';
        }

        $staffRows = $this->staffService->staffs($selectedCompanyId);
        $summaryMap = $this->summaryService->summaryMapByPaymentDate($selectedPaymentDate);
        $previousSummaryMap = $this->summaryService->previousSummaryMapByPaymentDate($selectedPaymentDate);
        $kihonMap = $this->kihonService->map($year, $month);
        $staffMasterMap = $this->staffMasterService->map();
        $shahoMap = $this->shahoService->map($year, $month);
        $residentMap = $this->residentService->map($year, $month);

        $staffRows = array_values(array_filter(
            $staffRows,
            static fn (array $staff): bool => isset($summaryMap[$staff['staff_id']])
        ));

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

        $attendanceSourceMap = $this->attendanceListSummaryService->summaryMap($attendanceYear, $attendanceMonth);
        $attendanceConfirmedMap = $this->confirmedStateService->mapByStaffIds(array_column($rows, 'staff_id'), $attendanceYear, $attendanceMonth);
        $rows = array_map(function (array $row) use ($attendanceSourceMap): array {
            $staffId = trim((string) ($row['staff_id'] ?? ''));
            $row['attendance_source'] = $attendanceSourceMap[$staffId] ?? [];
            $referenceCalc = $this->overtimeDeductionService->referenceBases(
                (array) ($row['summary'] ?? []),
                (string) ($row['company_name'] ?? '')
            );
            $row['reference_calc'] = $referenceCalc;
            return $row;
            return $row;
        }, $rows);
        $rows = array_map(function (array $row) use ($attendanceConfirmedMap): array {
            $staffId = trim((string) ($row['staff_id'] ?? ''));
            $row['summary'] = (array) ($row['summary'] ?? []);
            $row['summary']['attendance_checked'] = ((bool) ($attendanceConfirmedMap[$staffId] ?? false)) ? 1 : 0;
            return $row;
        }, $rows);

        $allowanceEntries = $this->allowanceLabelService->entries();
        $labelOverrides = $this->allowanceLabelService->labelMap();

        return view('admin_v2.payroll.index', [
            'availablePaymentDates' => $availablePaymentDates,
            'selectedPaymentDate' => $selectedPaymentDate,
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
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
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

    public function createCandidates(Request $request): JsonResponse
    {
        $v = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'company_id' => ['nullable', 'string', 'max:200'],
            'payment_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        $payload = $this->createCandidatesService->payload(
            $year,
            $month,
            (string) ($v['company_id'] ?? ''),
            (string) ($v['payment_date'] ?? '')
        );

        return response()->json([
            'ok' => true,
            'candidates' => $payload['candidates'],
            'suggested_payment_date' => $payload['suggested_payment_date'],
            'payment_date_options' => $payload['payment_date_options'],
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        $v = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'company_id' => ['nullable', 'string', 'max:200'],
            'payment_date' => ['nullable', 'date_format:Y-m-d'],
            'staff_ids' => ['required', 'array', 'min:1'],
            'staff_ids.*' => ['required', 'string', 'max:20'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        $result = $this->createService->create(
            $year,
            $month,
            array_map('strval', (array) $v['staff_ids']),
            (string) ($v['company_id'] ?? ''),
            (string) ($v['payment_date'] ?? '')
        );

        if ($result['created'] === 0 && $result['skipped'] === 0) {
            return response()->json([
                'ok' => false,
                'message' => '対象者がありません。表示条件を確認してください。',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'created' => $result['created'],
            'skipped' => $result['skipped'],
            'created_ids' => $result['created_ids'],
            'skipped_ids' => $result['skipped_ids'],
        ]);
    }

    public function delete(Request $request): JsonResponse
    {
        $v = $request->validate([
            'payment_date' => ['required', 'date_format:Y-m-d'],
            'staff_ids' => ['required', 'array', 'min:1'],
            'staff_ids.*' => ['required', 'string', 'max:20'],
        ]);

        $result = $this->deleteService->delete(
            array_map('strval', (array) $v['staff_ids']),
            (string) $v['payment_date']
        );

        if ($result['deleted'] === 0 && $result['skipped'] === 0) {
            return response()->json([
                'ok' => false,
                'message' => '対象者がありません。表示条件を確認してください。',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'deleted' => $result['deleted'],
            'skipped' => $result['skipped'],
            'deleted_ids' => $result['deleted_ids'],
            'skipped_ids' => $result['skipped_ids'],
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
                'message' => '勤怠未確定のため、給与を確定できません。',
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

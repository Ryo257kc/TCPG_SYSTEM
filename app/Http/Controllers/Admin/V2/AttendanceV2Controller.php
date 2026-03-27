<?php

namespace App\Http\Controllers\Admin\V2;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\Attendance\AttendanceV2AttendanceStaffService;
use App\Services\Admin\V2\Attendance\AttendanceV2BulkReflectService;
use App\Services\Admin\V2\Attendance\AttendanceV2ConfirmService;
use App\Services\Admin\V2\Attendance\AttendanceV2CompanyService;
use App\Services\Admin\V2\Attendance\AttendanceV2ConfirmedStateService;
use App\Services\Admin\V2\Attendance\AttendanceV2DailyService;
use App\Services\Admin\V2\Attendance\AttendanceV2DailyEditService;
use App\Services\Admin\V2\Attendance\AttendanceV2ListSummaryService;
use App\Services\Admin\V2\Attendance\AttendanceV2MetricService;
use App\Services\Admin\V2\Attendance\AttendanceV2MonthService;
use App\Services\Admin\V2\Attendance\AttendanceV2PayrollTargetService;
use App\Services\Admin\V2\Attendance\AttendanceV2ShiftCandidatesService;
use App\Services\Admin\V2\Attendance\AttendanceV2ShiftCreateService;
use App\Services\Admin\V2\Attendance\AttendanceV2ShiftDeleteService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceV2Controller extends Controller
{
    public function __construct(
        private readonly AttendanceV2MonthService $monthService,
        private readonly AttendanceV2CompanyService $companyService,
        private readonly AttendanceV2ConfirmedStateService $confirmedStateService,
        private readonly AttendanceV2BulkReflectService $bulkReflectService,
        private readonly AttendanceV2ConfirmService $confirmService,
        private readonly AttendanceV2AttendanceStaffService $attendanceStaffService,
        private readonly AttendanceV2MetricService $metricService,
        private readonly AttendanceV2ListSummaryService $listSummaryService,
        private readonly AttendanceV2PayrollTargetService $payrollTargetService,
        private readonly AttendanceV2DailyService $dailyService,
        private readonly AttendanceV2DailyEditService $dailyEditService,
        private readonly AttendanceV2ShiftCandidatesService $shiftCandidatesService,
        private readonly AttendanceV2ShiftCreateService $shiftCreateService,
        private readonly AttendanceV2ShiftDeleteService $shiftDeleteService,
    ) {
    }

    public function index(Request $request): View
    {
        $availableMonths = $this->monthService->availableMonths();
        $selectedMonth = $this->monthService->normalize((string) $request->query('month', ''), $availableMonths);

        [$year, $month] = array_map('intval', explode('-', $selectedMonth));
        $fromDate = sprintf('%04d-%02d-01', $year, $month);
        $toDate = date('Y-m-t', strtotime($fromDate));
        $payrollLinkPaymentDate = $this->payrollTargetService->resolvePaymentDate($year, $month);

        $companies = $this->companyService->companies();
        $storeOptions = DB::connection('sqlsrv')
            ->table('dbo.mx_stores')
            ->select(['store_code', 'store_name', 'store_short_name'])
            ->orderBy('store_code')
            ->get()
            ->map(static function ($row): array {
                $value = trim((string) ($row->store_short_name ?? ''));
                if ($value === '') {
                    $value = trim((string) ($row->store_name ?? ''));
                }

                return [
                    'value' => $value,
                    'label' => $value,
                ];
            })
            ->filter(static fn (array $row): bool => $row['value'] !== '')
            ->unique('value')
            ->values()
            ->all();
        $selectedCompanyId = trim((string) $request->query('company_id', ''));
        $selectedStaffId = trim((string) $request->query('staff_id', ''));

        $staffRows = $this->attendanceStaffService->staffs($fromDate, $toDate, $selectedCompanyId);
        if ($selectedStaffId !== '' && !in_array($selectedStaffId, array_column($staffRows, 'staff_id'), true)) {
            $selectedStaffId = '';
        }

        $metricMap = $this->metricService->metricMap($year, $month);
        $categoryTimeMap = $this->metricService->categoryTimeMap($year, $month);
        $attendanceCheckedMap = $this->confirmedStateService->mapByStaffIds(array_column($staffRows, 'staff_id'), $year, $month);
        $rows = $this->metricService->mergeRows($staffRows, $metricMap, $categoryTimeMap, $selectedStaffId);
        foreach ($rows as &$row) {
            $row['metrics']['attendance_checked'] = (bool) ($attendanceCheckedMap[$row['staff_id']] ?? false);
        }
        unset($row);

        $detailStaff = null;
        $dailyRows = [];
        $dailySummary = null;
        if ($selectedStaffId !== '') {
            foreach ($staffRows as $staff) {
                if ($staff['staff_id'] === $selectedStaffId) {
                    $detailStaff = $staff;
                    break;
                }
            }
            if ($detailStaff !== null) {
                $dailyRows = $this->dailyService->rows((array) ($detailStaff['time_card_keys'] ?? []), $year, $month);
                $dailySummary = $this->dailyService->summary($dailyRows);
            }
        }

        return view('admin_v2.work.attendance.index', [
            'availableMonths' => $availableMonths,
            'selectedMonth' => $selectedMonth,
            'companyOptions' => $companies,
            'storeOptions' => $storeOptions,
            'selectedCompanyId' => $selectedCompanyId,
            'selectedStaffId' => $selectedStaffId,
            'payrollLinkPaymentDate' => $payrollLinkPaymentDate,
            'staffRows' => $staffRows,
            'rows' => $rows,
            'detailStaff' => $detailStaff,
            'dailyRows' => $dailyRows,
            'dailySummary' => $dailySummary,
        ]);
    }

    public function updateDaily(Request $request)
    {
        $v = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'company_id' => ['nullable', 'string', 'max:100'],
            'staff_id' => ['required', 'string', 'max:100'],
            'time_card_key' => ['required', 'string', 'max:100'],
            'work_date' => ['required', 'date'],
            'attendance_category' => ['nullable', 'string', 'max:100'],
            'category_time' => ['nullable', 'numeric'],
            'paid_leave_used' => ['nullable', 'numeric'],
            'work_store' => ['nullable', 'string', 'max:100'],
            'shift_start' => ['nullable', 'string', 'max:50'],
            'shift_leave' => ['nullable', 'string', 'max:50'],
            'shift_break_out' => ['nullable', 'string', 'max:50'],
            'shift_end' => ['nullable', 'string', 'max:50'],
            'change_start' => ['nullable', 'string', 'max:50'],
            'change_leave' => ['nullable', 'string', 'max:50'],
            'change_break_out' => ['nullable', 'string', 'max:50'],
            'change_end' => ['nullable', 'string', 'max:50'],
            'change_scheduled' => ['nullable', 'numeric'],
            'overtime' => ['nullable', 'numeric'],
            'timecard_note' => ['nullable', 'string', 'max:1000'],
            'return_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $updated = $this->dailyEditService->update($v);

        return redirect()
            ->route('admin.attendance.index', [
                'month' => (string) $v['month'],
                'company_id' => (string) ($v['company_id'] ?? ''),
                'staff_id' => (string) $v['staff_id'],
            ])
            ->with('status', $updated > 0 ? '更新しました。' : '変更はありません。');
    }

    public function confirmAttendance(Request $request)
    {
        $v = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'company_id' => ['nullable', 'string', 'max:100'],
            'staff_id' => ['required', 'string', 'max:100'],
            'checked' => ['required', 'boolean'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        $checked = ((int) $v['checked']) === 1;
        $confirmedBy = trim((string) (auth()->user()->name ?? auth()->user()->staff_name ?? ''));

        $updated = $this->confirmService->setConfirmed(
            (string) $v['staff_id'],
            $year,
            $month,
            $checked,
            $confirmedBy,
        );

        return redirect()
            ->route('admin.attendance.index', [
                'month' => (string) $v['month'],
                'company_id' => (string) ($v['company_id'] ?? ''),
                'staff_id' => (string) $v['staff_id'],
            ])
            ->with('status', $updated > 0 ? ($checked ? '勤怠を確定しました。' : '勤怠確定を解除しました。') : '勤怠確定は更新されませんでした。');
    }

    public function reflectAttendance(Request $request)
    {
        $v = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'company_id' => ['nullable', 'string', 'max:100'],
        ]);

        [$year, $month] = array_map('intval', explode('-', (string) $v['month']));
        $fromDate = sprintf('%04d-%02d-01', $year, $month);
        $toDate = date('Y-m-t', strtotime($fromDate));
        $staffRows = $this->attendanceStaffService->staffs(
            $fromDate,
            $toDate,
            trim((string) ($v['company_id'] ?? ''))
        );
        $result = $this->bulkReflectService->reflect($staffRows, $year, $month);

        $messages = [];
        if (($result['updated'] ?? 0) > 0) {
            $messages[] = sprintf('勤怠を%d件反映しました', (int) $result['updated']);
        }
        if (($result['locked'] ?? 0) > 0) {
            $messages[] = sprintf('給与確定済み%d件は除外しました', (int) $result['locked']);
        }
        if (($result['missing'] ?? 0) > 0) {
            $messages[] = sprintf('給与データ未作成%d件は反映対象外です', (int) $result['missing']);
        }
        if ($messages === []) {
            $messages[] = '反映対象はありませんでした';
        }

        return redirect()
            ->route('admin.attendance.index', [
                'month' => (string) $v['month'],
                'company_id' => (string) ($v['company_id'] ?? ''),
            ])
            ->with('status', implode(' / ', $messages));
    }

    public function shiftCandidates(Request $request): JsonResponse
    {
        $month = trim((string) $request->query('month', ''));
        if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            return response()->json(['ok' => false, 'message' => 'invalid month'], 422);
        }

        [$year, $monthNum] = array_map('intval', explode('-', $month));
        $companyId = trim((string) $request->query('company_id', ''));
        $candidates = $this->shiftCandidatesService->candidates($year, $monthNum, $companyId);

        return response()->json([
            'ok' => true,
            'candidates' => $candidates,
        ]);
    }

    public function createShifts(Request $request): JsonResponse
    {
        $month = trim((string) $request->input('month', ''));
        if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            return response()->json(['ok' => false, 'message' => 'invalid month'], 422);
        }

        [$year, $monthNum] = array_map('intval', explode('-', $month));
        $staffIds = $request->input('staff_ids', []);
        if (!is_array($staffIds)) {
            $staffIds = [];
        }

        $result = $this->shiftCreateService->create($year, $monthNum, $staffIds);

        return response()->json([
            'ok' => true,
            'result' => $result,
        ]);
    }

    public function deleteShifts(Request $request): JsonResponse
    {
        $month = trim((string) $request->input('month', ''));
        if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            return response()->json(['ok' => false, 'message' => 'invalid month'], 422);
        }

        [$year, $monthNum] = array_map('intval', explode('-', $month));
        $staffIds = $request->input('staff_ids', []);
        if (!is_array($staffIds)) {
            $staffIds = [];
        }

        $result = $this->shiftDeleteService->delete($year, $monthNum, $staffIds);

        return response()->json([
            'ok' => true,
            'result' => $result,
        ]);
    }
}

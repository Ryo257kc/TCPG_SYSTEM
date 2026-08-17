<?php

namespace App\Http\Controllers\StaffPortal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use App\Services\Shared\StoreDisplayNameService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ShiftController extends Controller
{
    use HandlesStaffPortalContext;

    public function __construct(
        private readonly StoreDisplayNameService $storeDisplayNameService,
    ) {
    }

    public function adminShiftChange(Request $request): RedirectResponse|View
    {
        $staffId = $this->sessionStaffId($request);

        $selectedMonth = $this->resolveMonth((string) $request->query('month', ''));
        [$year, $month] = $this->splitMonth($selectedMonth);
        $monthStart = Carbon::create($year, $month, 1, 0, 0, 0, 'Asia/Tokyo')->toDateString();
        $staffRow = $this->staffPortalStaffRow($staffId);
        $canSelectBasicShiftStaff = $this->canSelectBasicShiftStaff($staffRow);
        $staffOptions = $canSelectBasicShiftStaff ? $this->staffOptions($monthStart) : [];

        $selectedStaffId = $canSelectBasicShiftStaff
            ? trim((string) $request->query('staff_id', ''))
            : $staffId;

        $selectedStaffName = '';
        if ($canSelectBasicShiftStaff) {
            foreach ($staffOptions as $staff) {
                if (($staff['staff_id'] ?? '') === $selectedStaffId) {
                    $selectedStaffName = (string) ($staff['staff_name'] ?? '');
                    break;
                }
            }
        } else {
            $selectedStaffName = $this->resolveDisplayName($staffId);
        }

        $rows = [];
        if ($selectedStaffId !== '') {
            $resultRows = DB::connection('sqlsrv')
                ->table('dbo.mx_time_cards as tc')
                ->leftJoin('dbo.mx_calendar as cal', function ($join) {
                    $join->whereRaw('CONVERT(date, cal.calendar_day) = CONVERT(date, tc.work_date)');
                })
                ->leftJoin('dbo.mx_stores as st', 'tc.work_store', '=', 'st.store_code')
                ->whereRaw('LTRIM(RTRIM(tc.staff_name)) = ?', [$selectedStaffId])
                ->whereRaw('YEAR(tc.work_date) = ?', [$year])
                ->whereRaw('MONTH(tc.work_date) = ?', [$month])
                ->orderBy('tc.work_date')
                ->select([
                    'tc.time_no',
                    'tc.work_date',
                    'tc.holiday_category',
                    'tc.shift_start',
                    'tc.shift_leave',
                    'tc.shift_break_out',
                    'tc.shift_end',
                    'tc.work_store',
                    'tc.attendance_checked',
                    'cal.week as cal_week',
                    'cal.work_holiday as cal_work_holiday',
                    'st.store_short_name',
                    'st.store_name',
                ])
                ->get();

            foreach ($resultRows as $row) {
                $workHoliday = trim((string) ($row->cal_work_holiday ?? ''));
                $holidayCategory = trim((string) ($row->holiday_category ?? ''));
                $rows[] = [
                    'time_no' => (int) ($row->time_no ?? 0),
                    'date_label' => $this->formatDateLabel($row->work_date),
                    'holiday_category' => $holidayCategory,
                    'shift_start' => $this->formatTimeForInput($row->shift_start),
                    'shift_exit' => $this->formatTimeForInput($row->shift_leave),
                    'shift_in_out' => $this->formatTimeForInput($row->shift_break_out),
                    'shift_end' => $this->formatTimeForInput($row->shift_end),
                    'shop_code' => trim((string) ($row->work_store ?? '')),
                    'shop_name' => $this->resolveStoreName(
                        trim((string) ($row->store_short_name ?? '')),
                        trim((string) ($row->store_name ?? '')),
                        trim((string) ($row->work_store ?? ''))
                    ),
                    'is_confirmed' => ((int) ($row->attendance_checked ?? 0)) === 1,
                    'is_holiday' => trim((string) ($row->cal_week ?? '')) === '日' || in_array($workHoliday, ['休日', '法休'], true),
                ];
            }
        }

        return view('staff_portal.office.attendance.index', [
            'displayName' => $this->resolveDisplayName($staffId),
            'selectedMonth' => $selectedMonth,
            'selectedStaffId' => $selectedStaffId,
            'selectedStaffName' => $selectedStaffName,
            'staffOptions' => $staffOptions,
            'storeOptions' => $this->storeOptions(),
            'rows' => $rows,
            'rowCount' => count($rows),
            'showPunchColumns' => false,
            'isSelfOnly' => !$canSelectBasicShiftStaff,
            'updateRouteName' => 'admin.shift.inline_update',
        ]);
    }

    public function officeAttendance(Request $request): RedirectResponse|View
    {
        $staffId = $this->sessionStaffId($request);

        $selectedMonth = $this->resolveMonth((string) $request->query('month', ''));
        [$year, $month] = $this->splitMonth($selectedMonth);
        $displayName = $this->resolveDisplayName($staffId);
        $rows = $this->buildShiftChangeRows($staffId, $year, $month, true);

        return view('staff_portal.admin.shift.change', [
            'displayName' => $displayName,
            'selectedMonth' => $selectedMonth,
            'selectedStaffId' => $staffId,
            'selectedStaffName' => $displayName,
            'staffOptions' => [],
            'storeOptions' => $this->storeOptions(),
            'rows' => $rows,
            'rowCount' => count($rows),
            'showPunchColumns' => true,
            'isSelfOnly' => true,
            'updateRouteName' => 'office.attendance.update',
        ]);
    }

    public function adminShiftUpdate(Request $request, int $timeNo): RedirectResponse
    {
        $staffId = $this->sessionStaffId($request);

        if (!$this->isStoreManager($this->staffPortalStaffRow($staffId))) {
            return redirect()->route('dashboard')->with('statusMessage', 'Admin access only.');
        }

        $selectedMonth = $this->resolveMonth((string) $request->input('month', ''));
        $selectedStaffId = trim((string) $request->input('staff_id', ''));
        if ($this->isTimeCardConfirmed($timeNo)) {
            return redirect()
                ->route('admin.shift.change', ['month' => $selectedMonth, 'staff_id' => $selectedStaffId])
                ->with('statusMessage', '勤怠確定済みのため、シフトは編集できません。');
        }

        $action = (string) $request->input('_action', 'register');
        $payload = $this->extractShiftPayload($request);
        $holidayCategory = $this->normalizeHolidayCategory((string) $request->input('holiday_category', ''));

        if ($action !== 'clear') {
            if ($payload['has_any_time'] && $payload['shop_code'] === null) {
                return redirect()->route('admin.shift.change', ['month' => $selectedMonth, 'staff_id' => $selectedStaffId]);
            }
            if ($payload['shift_start'] !== null && $payload['shift_end'] === null) {
                return redirect()->route('admin.shift.change', ['month' => $selectedMonth, 'staff_id' => $selectedStaffId]);
            }
            if ($payload['shift_exit'] !== null && $payload['shift_in_out'] === null) {
                return redirect()->route('admin.shift.change', ['month' => $selectedMonth, 'staff_id' => $selectedStaffId]);
            }
        }

        DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards')
            ->where('time_no', $timeNo)
            ->update($action === 'clear'
                ? [
                    'holiday_category' => null,
                    'shift_start' => null,
                    'shift_leave' => null,
                    'shift_break_out' => null,
                    'shift_end' => null,
                    'work_store' => null,
                ]
                : [
                    'holiday_category' => $holidayCategory,
                    'shift_start' => $payload['shift_start'],
                    'shift_leave' => $payload['shift_exit'],
                    'shift_break_out' => $payload['shift_in_out'],
                    'shift_end' => $payload['shift_end'],
                    'work_store' => $payload['shop_code'],
                ]);

        return redirect()
            ->route('admin.shift.change', ['month' => $selectedMonth, 'staff_id' => $selectedStaffId]);
    }

    public function officeAttendanceUpdate(Request $request, int $timeNo): RedirectResponse
    {
        $staffId = $this->sessionStaffId($request);

        $selectedMonth = $this->resolveMonth((string) $request->input('month', ''));
        if ($this->isTimeCardConfirmed($timeNo, $staffId)) {
            return redirect()
                ->route('office.attendance', ['month' => $selectedMonth])
                ->with('statusMessage', '勤怠確定済みのため、シフトは編集できません。');
        }

        $action = (string) $request->input('_action', 'register');
        $payload = $this->extractShiftPayload($request);
        $holidayCategory = $this->normalizeHolidayCategory((string) $request->input('holiday_category', ''));

        if ($action !== 'clear') {
            if ($payload['has_any_time'] && $payload['shop_code'] === null) {
                return redirect()->route('office.attendance', ['month' => $selectedMonth]);
            }
            if ($payload['shift_start'] !== null && $payload['shift_end'] === null) {
                return redirect()->route('office.attendance', ['month' => $selectedMonth]);
            }
            if ($payload['shift_exit'] !== null && $payload['shift_in_out'] === null) {
                return redirect()->route('office.attendance', ['month' => $selectedMonth]);
            }
        }

        DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards')
            ->where('time_no', $timeNo)
            ->whereRaw('LTRIM(RTRIM(staff_name)) = ?', [$staffId])
            ->update($action === 'clear'
                ? [
                    'holiday_category' => null,
                    'shift_start' => null,
                    'shift_leave' => null,
                    'shift_break_out' => null,
                    'shift_end' => null,
                    'work_store' => null,
                ]
                : [
                    'holiday_category' => $holidayCategory,
                    'shift_start' => $payload['shift_start'],
                    'shift_leave' => $payload['shift_exit'],
                    'shift_break_out' => $payload['shift_in_out'],
                    'shift_end' => $payload['shift_end'],
                    'work_store' => $payload['shop_code'],
                ]);

        return redirect()
            ->route('office.attendance', ['month' => $selectedMonth]);
    }

    public function adminBasicShift(Request $request): RedirectResponse|View
    {
        $staffId = $this->sessionStaffId($request);

        $selectedMonth = $this->resolveMonth((string) $request->query('month', ''));
        [$year, $month] = $this->splitMonth($selectedMonth);
        $monthStart = Carbon::create($year, $month, 1, 0, 0, 0, 'Asia/Tokyo')->toDateString();
        $staffRow = $this->staffPortalStaffRow($staffId);
        $canManageBasicShift = $this->canManageBasicShift($staffRow);
        $canSelectBasicShiftStaff = $this->canSelectBasicShiftStaff($staffRow);
        $staffOptions = $canSelectBasicShiftStaff ? $this->staffOptions($monthStart) : [];

        $selectedStaffId = $canSelectBasicShiftStaff
            ? trim((string) $request->query('staff_id', ''))
            : $staffId;

        $selectedStaffName = '';
        if ($canSelectBasicShiftStaff) {
            foreach ($staffOptions as $staff) {
                if (($staff['staff_id'] ?? '') === $selectedStaffId) {
                    $selectedStaffName = (string) ($staff['staff_name'] ?? '');
                    break;
                }
            }
        } else {
            $selectedStaffName = $this->resolveDisplayName($staffId);
        }

        $rows = [];
        if ($selectedStaffId !== '') {
            $resultRows = DB::connection('sqlsrv')
                ->table('dbo.mx_kihon_shifts as ks')
                ->leftJoin('dbo.mx_stores as st', 'ks.section', '=', 'st.store_code')
                ->whereRaw('LTRIM(RTRIM(ks.staff_name)) = ?', [$selectedStaffId])
                ->select([
                    'ks.shift_no',
                    'ks.week',
                    'ks.shift_in',
                    'ks.shift_exit',
                    'ks.shift_entry',
                    'ks.shift_out',
                    'ks.section',
                    'st.store_short_name',
                    'st.store_name',
                ])
                ->get();

            $weekOrder = ['月' => 1, '火' => 2, '水' => 3, '木' => 4, '金' => 5, '土' => 6, '日' => 7];
            foreach ($resultRows as $row) {
                $rows[] = [
                    'shift_no' => (int) ($row->shift_no ?? 0),
                    'week' => trim((string) ($row->week ?? '')),
                    'sort' => $weekOrder[trim((string) ($row->week ?? ''))] ?? 99,
                    'shift_start' => $this->formatTimeForInput($row->shift_in),
                    'shift_exit' => $this->formatTimeForInput($row->shift_exit),
                    'shift_in_out' => $this->formatTimeForInput($row->shift_entry),
                    'shift_end' => $this->formatTimeForInput($row->shift_out),
                    'shop_code' => trim((string) ($row->section ?? '')),
                    'shop_name' => $this->resolveStoreName(
                        trim((string) ($row->store_short_name ?? '')),
                        trim((string) ($row->store_name ?? '')),
                        trim((string) ($row->section ?? ''))
                    ),
                ];
            }

            usort($rows, static fn(array $a, array $b): int => ($a['sort'] ?? 99) <=> ($b['sort'] ?? 99));
        }

        return view('staff_portal.admin.shift.basic', [
            'displayName' => $this->resolveDisplayName($staffId),
            'statusMessage' => (string) $request->session()->get('statusMessage', ''),
            'selectedMonth' => $selectedMonth,
            'selectedStaffId' => $selectedStaffId,
            'selectedStaffName' => $selectedStaffName,
            'staffOptions' => $staffOptions,
            'storeOptions' => $this->storeOptions(),
            'rows' => $rows,
            'rowCount' => count($rows),
            'canManageBasicShift' => $canManageBasicShift,
            'canSelectBasicShiftStaff' => $canSelectBasicShiftStaff,
        ]);
    }

    public function adminBasicShiftUpdate(Request $request, int $shiftNo): RedirectResponse
    {
        $staffId = $this->sessionStaffId($request);

        if (!$this->canManageBasicShift($this->staffPortalStaffRow($staffId))) {
            return redirect()->route('dashboard')->with('statusMessage', 'Admin access only.');
        }

        $selectedMonth = $this->resolveMonth((string) $request->input('month', ''));
        $selectedStaffId = trim((string) $request->input('staff_id', ''));
        $action = (string) $request->input('_action', 'register');
        $payload = $this->extractShiftPayload($request);

        if ($action !== 'clear') {
            if ($payload['has_any_time'] && $payload['shop_code'] === null) {
                return redirect()->route('admin.basic-shift', ['month' => $selectedMonth, 'staff_id' => $selectedStaffId])->with('statusMessage', 'Store is required.');
            }
            if ($payload['shift_start'] !== null && $payload['shift_end'] === null) {
                return redirect()->route('admin.basic-shift', ['month' => $selectedMonth, 'staff_id' => $selectedStaffId])->with('statusMessage', 'End time is required.');
            }
            if ($payload['shift_exit'] !== null && $payload['shift_in_out'] === null) {
                return redirect()->route('admin.basic-shift', ['month' => $selectedMonth, 'staff_id' => $selectedStaffId])->with('statusMessage', 'Break time is required.');
            }
        }

        DB::connection('sqlsrv')
            ->table('dbo.mx_kihon_shifts')
            ->where('shift_no', $shiftNo)
            ->update($action === 'clear'
                ? [
                    'shift_in' => null,
                    'shift_exit' => null,
                    'shift_entry' => null,
                    'shift_out' => null,
                    'section' => null,
                ]
                : [
                    'shift_in' => $payload['shift_start'],
                    'shift_exit' => $payload['shift_exit'],
                    'shift_entry' => $payload['shift_in_out'],
                    'shift_out' => $payload['shift_end'],
                    'section' => $payload['shop_code'],
                ]);

        return redirect()
            ->route('admin.basic-shift', ['month' => $selectedMonth, 'staff_id' => $selectedStaffId])
            ->with('statusMessage', $action === 'clear' ? 'Basic shift cleared.' : '保存しました');
    }


    private function isTimeCardConfirmed(int $timeNo, ?string $staffId = null): bool
    {
        if (!Schema::connection('sqlsrv')->hasColumn('mx_time_cards', 'attendance_checked')) {
            return false;
        }

        $query = DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards')
            ->where('time_no', $timeNo);

        if ($staffId !== null && trim($staffId) !== '') {
            $query->whereRaw('LTRIM(RTRIM(staff_name)) = ?', [trim($staffId)]);
        }

        return ((int) ($query->value('attendance_checked') ?? 0)) === 1;
    }

    private function canManageBasicShift(?array $staffRow): bool
    {
        return (int) ($staffRow['is_admin'] ?? 0) === 1
            || (int) ($staffRow['is_store_management_user'] ?? 0) === 1
            || (int) ($staffRow['is_payment_check_user'] ?? 0) === 1;
    }

    private function canSelectBasicShiftStaff(?array $staffRow): bool
    {
        return (int) ($staffRow['is_admin'] ?? 0) === 1
            || (int) ($staffRow['is_store_management_user'] ?? 0) === 1;
    }

    private function sessionStaffId(Request $request): string
    {
        return (string) $request->session()->get('staff_id', '');
    }

    private function resolveMonth(string $month): string
    {
        $month = trim($month);
        if (preg_match('/^\d{4}-\d{2}$/', $month) === 1) {
            return $month;
        }

        return now('Asia/Tokyo')->format('Y-m');
    }

    private function splitMonth(string $month): array
    {
        return [(int) substr($month, 0, 4), (int) substr($month, 5, 2)];
    }

    private function resolveDisplayName(string $staffId): string
    {
        $row = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->whereRaw('LTRIM(RTRIM(staff_id)) = ?', [$staffId])
            ->first(['staff_name']);

        $name = trim((string) ($row->staff_name ?? ''));

        return $name !== '' ? $name : $staffId;
    }

    private function staffOptions(string $monthStart): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->selectRaw('staff_id as staff_id, staff_name')
            ->whereRaw('(tai_date IS NULL OR CONVERT(date, tai_date) >= ?)', [$monthStart])
            ->orderBy('staff_id')
            ->get()
            ->map(fn($row): array => [
                'staff_id' => trim((string) ($row->staff_id ?? '')),
                'staff_name' => trim((string) ($row->staff_name ?? '')),
            ])
            ->filter(fn(array $row): bool => $row['staff_id'] !== '')
            ->values()
            ->all();
    }

    private function storeOptions(): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_stores')
            ->where(function ($query) {
                $query->where('is_closed', false)
                    ->orWhere('is_closed', 0)
                    ->orWhereNull('is_closed');
            })
            ->whereNotNull('business_type')
            ->select(['store_code', 'store_short_name', 'store_name'])
            ->orderBy('store_code')
            ->get()
            ->map(function ($row): array {
                $storeCode = trim((string) ($row->store_code ?? ''));

                return [
                    'store_code' => $storeCode,
                    'label' => $this->storeDisplayNameService->resolve(
                        (string) ($row->store_name ?? ''),
                        (string) ($row->store_short_name ?? '')
                    ),
                ];
            })
            ->filter(fn(array $row): bool => $row['store_code'] !== '' && $row['label'] !== '')
            ->unique('store_code')
            ->unique('label')
            ->values()
            ->all();
    }

    private function buildShiftChangeRows(string $staffId, int $year, int $month, bool $includePunchColumns): array
    {
        $resultRows = DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards as tc')
            ->leftJoin('dbo.mx_calendar as cal', function ($join) {
                $join->whereRaw('CONVERT(date, cal.calendar_day) = CONVERT(date, tc.work_date)');
            })
            ->leftJoin('dbo.mx_stores as st', 'tc.work_store', '=', 'st.store_code')
            ->whereRaw('LTRIM(RTRIM(tc.staff_name)) = ?', [$staffId])
            ->whereRaw('YEAR(tc.work_date) = ?', [$year])
            ->whereRaw('MONTH(tc.work_date) = ?', [$month])
            ->orderBy('tc.work_date')
            ->select([
                'tc.time_no',
                'tc.work_date',
                'tc.holiday_category',
                'tc.shift_start',
                'tc.shift_leave',
                'tc.shift_break_out',
                'tc.shift_end',
                'tc.actual_start',
                'tc.actual_leave',
                'tc.actual_break_out',
                'tc.actual_end',
                'tc.work_store',
                'tc.attendance_checked',
                'cal.week as cal_week',
                'cal.work_holiday as cal_work_holiday',
                'st.store_short_name',
                'st.store_name',
            ])
            ->get();

        $rows = [];
        foreach ($resultRows as $row) {
            $workHoliday = trim((string) ($row->cal_work_holiday ?? ''));
            $holidayCategory = trim((string) ($row->holiday_category ?? ''));

            $item = [
                'time_no' => (int) ($row->time_no ?? 0),
                'date_label' => $this->formatDateLabel($row->work_date),
                'holiday_category' => $holidayCategory,
                'shift_start' => $this->formatTimeForInput($row->shift_start),
                'shift_exit' => $this->formatTimeForInput($row->shift_leave),
                'shift_in_out' => $this->formatTimeForInput($row->shift_break_out),
                'shift_end' => $this->formatTimeForInput($row->shift_end),
                'shop_code' => trim((string) ($row->work_store ?? '')),
                'shop_name' => $this->resolveStoreName(
                    trim((string) ($row->store_short_name ?? '')),
                    trim((string) ($row->store_name ?? '')),
                    trim((string) ($row->work_store ?? ''))
                ),
                'is_confirmed' => ((int) ($row->attendance_checked ?? 0)) === 1,
                'is_holiday' => in_array($holidayCategory, ['休日', '祝日', '法休'], true),
            ];

            if ($includePunchColumns) {
                $item['actual_start'] = $this->formatTimeForInput($row->actual_start);
                $item['actual_exit'] = $this->formatTimeForInput($row->actual_leave);
                $item['actual_in_out'] = $this->formatTimeForInput($row->actual_break_out);
                $item['actual_end'] = $this->formatTimeForInput($row->actual_end);
            }

            $rows[] = $item;
        }

        return $rows;
    }

    private function formatDateLabel(mixed $value): string
    {
        if ($value === null || trim((string) $value) === '') {
            return '';
        }

        try {
            $date = Carbon::parse((string) $value);
            return $date->format('n/j') . '(' . $this->weekdayLabel($date) . ')';
        } catch (\Throwable) {
            return trim((string) $value);
        }
    }

    private function weekdayLabel(mixed $value): string
    {
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        try {
            $date = $value instanceof Carbon ? $value : Carbon::parse((string) $value);
            return $weekdays[$date->dayOfWeek] ?? '';
        } catch (\Throwable) {
            return '';
        }
    }

    private function formatTimeForInput(mixed $value): string
    {
        if ($value === null || trim((string) $value) === '') {
            return '';
        }

        if (\App\Support\AttendanceTime::isZeroPlaceholder($value)) {
            return '';
        }

        try {
            return Carbon::parse((string) $value)->format('H:i');
        } catch (\Throwable) {
            return '';
        }
    }

    private function resolveStoreName(string $shortName, string $name, string $fallback): string
    {
        $resolved = $this->storeDisplayNameService->resolve($name, $shortName);

        return $resolved !== '' ? $resolved : $fallback;
    }

    private function extractShiftPayload(Request $request): array
    {
        $holidayCategory = trim((string) $request->input('holiday_category', ''));
        $holidayCategory = in_array($holidayCategory, ['平日', '休日', '祝日', '法休'], true) ? $holidayCategory : null;
        $shiftStart = $this->normalizeTimeValue($request->input('shift_start'));
        $shiftExit = $this->normalizeTimeValue($request->input('shift_exit'));
        $shiftInOut = $this->normalizeTimeValue($request->input('shift_in_out'));
        $shiftEnd = $this->normalizeTimeValue($request->input('shift_end'));
        $shopCode = trim((string) $request->input('shop_code', ''));
        $shopCode = in_array($shopCode, ['001', '003', '004', '005', '006', '007'], true) ? $shopCode : null;

        return [
            'holiday_category' => $holidayCategory,
            'shift_start' => $shiftStart,
            'shift_exit' => $shiftExit,
            'shift_in_out' => $shiftInOut,
            'shift_end' => $shiftEnd,
            'shop_code' => $shopCode,
            'has_any_time' => $shiftStart !== null || $shiftExit !== null || $shiftInOut !== null || $shiftEnd !== null,
        ];
    }

    private function normalizeTimeValue(mixed $value): ?string
    {
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        return preg_match('/^\d{2}:\d{2}$/', $text) === 1 ? $text : null;
    }

    private function normalizeHolidayCategory(string $value): ?string
    {
        $value = trim($value);

        return in_array($value, ['平日', '休日', '祝日', '法休'], true) ? $value : null;
    }
}

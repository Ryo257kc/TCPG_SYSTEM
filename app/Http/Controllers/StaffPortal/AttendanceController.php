<?php

namespace App\Http\Controllers\StaffPortal;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\Attendance\AttendanceV2DailyEditService;
use App\Services\StaffPortal\Attendance\AttendanceV2DailyTableItemBuilder;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function attendanceMonthly(Request $request): RedirectResponse|View
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'Please log in.');
        }

        $selectedMonth = $this->resolveMonth((string) $request->input('month', now('Asia/Tokyo')->format('Y-m')));
        [$year, $month] = $this->splitMonth($selectedMonth);

        $builder = new AttendanceV2DailyTableItemBuilder();
        $dailyTableData = $builder->build([$staffId], $year, $month, false);
        $latestAppliedAt = collect($dailyTableData['dailyRows'])
            ->pluck('staff_request')
            ->filter()
            ->max();
        $dailyRows = collect($dailyTableData['dailyRows'])
            ->map(function (array $row) use ($selectedMonth, $latestAppliedAt): array {
                $timeNo = (int) ($row['time_no'] ?? 0);
                $row['edit_url'] = ($latestAppliedAt || $timeNo === 0)
                    ? ''
                    : route('attendance.edit', ['timeNo' => $timeNo, 'month' => $selectedMonth]);

                return $row;
            })
            ->all();

        return view('staff_portal.attendance.monthly', [
            'displayName' => $this->resolveDisplayName($staffId),
            'hidePayrollLinks' => false,
            'statusMessage' => (string) $request->session()->get('statusMessage', ''),
            'selectedMonth' => $selectedMonth,
            'rowCount' => count($dailyRows),
            'dailyRows' => $dailyRows,
            'attendanceCategories' => $dailyTableData['attendanceCategories'],
            'storeOptions' => $dailyTableData['storeOptions'],
            'monthlyAppliedAt' => $latestAppliedAt ? Carbon::parse($latestAppliedAt)->format('Y/n/d G:i:s') : null,
            'canMonthlyApply' => $dailyTableData['dailyRows'] !== [] && empty($latestAppliedAt),
            'isAdmin' => false,
        ]);
    }

    public function attendanceMonthlyApply(Request $request): RedirectResponse
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'Please log in.');
        }

        $selectedMonth = $this->resolveMonth((string) $request->input('month', now('Asia/Tokyo')->format('Y-m')));
        [$year, $month] = $this->splitMonth($selectedMonth);

        DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards')
            ->whereRaw('LTRIM(RTRIM(staff_name)) = ?', [$staffId])
            ->whereRaw('YEAR(work_date) = ?', [$year])
            ->whereRaw('MONTH(work_date) = ?', [$month])
            ->update([
                'staff_request' => Carbon::now('Asia/Tokyo')->format('Y-m-d H:i:s'),
                'staff_request_ch' => 1,
            ]);

        return redirect()
            ->route('attendance.monthly', ['month' => $selectedMonth])
            ->with('statusMessage', 'Applied.');
    }

    public function attendanceEdit(Request $request, int $timeNo): RedirectResponse|View
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'Please log in.');
        }

        $card = DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards as tc')
            ->where('tc.time_no', $timeNo)
            ->whereRaw('LTRIM(RTRIM(tc.staff_name)) = ?', [$staffId])
            ->first();

        if ($card === null) {
            return redirect()->route('attendance.monthly')->with('statusMessage', 'Not found.');
        }

        $month = $this->resolveMonth((string) $request->query('month', date('Y-m', strtotime((string) $card->work_date))));

        return view('staff_portal.attendance.edit', [
            'displayName' => $this->resolveDisplayName($staffId),
            'hidePayrollLinks' => false,
            'timeNo' => $timeNo,
            'month' => $month,
            'staffName' => $this->resolveDisplayName($staffId),
            'dateLabel' => $this->formatFullDateLabel((string) ($card->work_date ?? '')),
            'row' => [
                'kubun' => (string) ($card->work_type ?? ''),
                'yukyu_shinsei' => $this->formatDateTimeLabel($card->staff_request ?? null) ?? '',
                'jitu_sigyo' => $this->formatTimeValue($card->actual_start ?? null),
                'jitu_taisitu' => $this->formatTimeValue($card->actual_leave ?? null),
                'jitu_irisitu' => $this->formatTimeValue($card->actual_break_out ?? null),
                'jitu_syugyo' => $this->formatTimeValue($card->actual_end ?? null),
                'shift_sigyo' => $this->formatTimeValue($card->shift_start ?? null),
                'shift_taisitu' => $this->formatTimeValue($card->shift_leave ?? null),
                'shift_irisitu' => $this->formatTimeValue($card->shift_break_out ?? null),
                'shift_syugyo' => $this->formatTimeValue($card->shift_end ?? null),
                'change_sigyo' => $this->formatTimeValue($card->change_start ?? null),
                'change_taisitu' => $this->formatTimeValue($card->change_leave ?? null),
                'change_irisitu' => $this->formatTimeValue($card->change_break_out ?? null),
                'change_syugyo' => $this->formatTimeValue($card->change_end ?? null),
                'overtime' => $this->formatNumericDisplay($card->overtime ?? null),
                'remark' => (string) ($card->timecard_note ?? ''),
                'sashimodoshi_biko' => (string) ($card->return_note ?? ''),
                'can_submit' => empty($card->staff_request),
            ],
            'backUrl' => route('attendance.monthly', ['month' => $month]),
        ]);
    }

    public function attendanceUpdate(Request $request, int $timeNo): RedirectResponse
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'Please log in.');
        }

        $card = DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards as tc')
            ->where('tc.time_no', $timeNo)
            ->whereRaw('LTRIM(RTRIM(tc.staff_name)) = ?', [$staffId])
            ->first();

        if ($card === null) {
            return redirect()->route('attendance.monthly')->with('statusMessage', 'Not found.');
        }

        $month = $this->resolveMonth((string) $request->input('month', date('Y-m', strtotime((string) $card->work_date))));

        if (!empty($card->staff_request)) {
            return redirect()->route('attendance.monthly', ['month' => $month])->with('statusMessage', 'Already applied.');
        }

        $submitted = array_values($request->except([
            '_token',
            'month',
            'return_mode',
            'return_staff_id',
            'return_month',
            '_action',
        ]));
        [
            $attendanceCategory,
            $changeStart,
            $changeLeave,
            $changeBreakOut,
            $changeEnd,
            $overtime,
            $timecardNote,
        ] = array_pad($submitted, 7, '');

        (new AttendanceV2DailyEditService())->update([
            'time_card_key' => trim((string) ($card->staff_name ?? '')),
            'work_date' => date('Y-m-d', strtotime((string) $card->work_date)),
            'attendance_category' => (string) $attendanceCategory,
            'change_start' => (string) $changeStart,
            'change_leave' => (string) $changeLeave,
            'change_break_out' => (string) $changeBreakOut,
            'change_end' => (string) $changeEnd,
            'overtime' => (string) $overtime,
            'timecard_note' => (string) $timecardNote,
        ]);

        return redirect()->route('attendance.monthly', ['month' => $month])->with('statusMessage', 'Updated.');
    }

    public function managementApprove(Request $request, string $staffId): RedirectResponse
    {
        $loginStaffId = (string) $request->session()->get('staff_id', '');
        if ($loginStaffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'Please log in.');
        }

        $selectedMonth = $this->resolveMonth((string) $request->input('month', now('Asia/Tokyo')->format('Y-m')));
        [$year, $month] = $this->splitMonth($selectedMonth);

        DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards')
            ->whereRaw('LTRIM(RTRIM(staff_name)) = ?', [$staffId])
            ->whereRaw('YEAR(work_date) = ?', [$year])
            ->whereRaw('MONTH(work_date) = ?', [$month])
            ->whereNotNull('staff_request')
            ->update([
                'manager_approval' => Carbon::now('Asia/Tokyo')->format('Y-m-d H:i:s'),
            ]);

        return redirect()
            ->route('attendance.management.detail', ['staffId' => $staffId, 'month' => $selectedMonth])
            ->with('statusMessage', 'Approved.');
    }

    public function managementRemand(Request $request, string $staffId): RedirectResponse
    {
        $loginStaffId = (string) $request->session()->get('staff_id', '');
        if ($loginStaffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'Please log in.');
        }

        $selectedMonth = $this->resolveMonth((string) $request->input('month', now('Asia/Tokyo')->format('Y-m')));
        [$year, $month] = $this->splitMonth($selectedMonth);

        DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards')
            ->whereRaw('LTRIM(RTRIM(staff_name)) = ?', [$staffId])
            ->whereRaw('YEAR(work_date) = ?', [$year])
            ->whereRaw('MONTH(work_date) = ?', [$month])
            ->whereNotNull('staff_request')
            ->update([
                'staff_request' => null,
                'staff_request_ch' => 0,
                'manager_approval' => null,
            ]);

        return redirect()
            ->route('attendance.management.detail', ['staffId' => $staffId, 'month' => $selectedMonth])
            ->with('statusMessage', 'Remanded.');
    }

    public function punchList(Request $request): RedirectResponse|View
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'Please log in.');
        }

        $selectedDate = $this->resolveDate((string) $request->query('date', now('Asia/Tokyo')->format('Y-m-d')));

        $rows = DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards as tc')
            ->leftJoin('dbo.mx_staffs as s', DB::raw('LTRIM(RTRIM(tc.staff_name))'), '=', DB::raw('LTRIM(RTRIM(s.staff_id))'))
            ->leftJoin('dbo.mx_stores as st', DB::raw('LTRIM(RTRIM(tc.work_store))'), '=', DB::raw('LTRIM(RTRIM(st.store_code))'))
            ->whereDate('tc.work_date', $selectedDate)
            ->whereNotNull('tc.work_store')
            ->orderByRaw("LTRIM(RTRIM(COALESCE(s.staff_name, tc.staff_name, '')))")
            ->selectRaw("
                LTRIM(RTRIM(COALESCE(s.staff_name, tc.staff_name, ''))) as staff_name,
                LTRIM(RTRIM(COALESCE(tc.holiday_category, ''))) as kubun,
                tc.actual_start as start,
                tc.actual_leave as [exit],
                tc.actual_break_out as in_out,
                tc.actual_end as [end],
                LTRIM(RTRIM(COALESCE(st.store_short_name, st.store_name, tc.work_store, ''))) as shop
            ")
            ->get()
            ->map(fn ($row): array => [
                'staff_name' => (string) ($row->staff_name ?? ''),
                'kubun' => (string) ($row->kubun ?? ''),
                'start' => $this->formatTimeValue($row->start ?? null),
                'exit' => $this->formatTimeValue($row->exit ?? null),
                'in_out' => $this->formatTimeValue($row->in_out ?? null),
                'end' => $this->formatTimeValue($row->end ?? null),
                'shop' => (string) ($row->shop ?? ''),
            ])
            ->all();

        return view('staff_portal.admin.attendance.punch_list', [
            'displayName' => $this->resolveDisplayName($staffId),
            'hidePayrollLinks' => false,
            'selectedDate' => $selectedDate,
            'rowCount' => count($rows),
            'rows' => $rows,
            'isAdmin' => true,
        ]);
    }

    public function management(Request $request): RedirectResponse|View
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'Please log in.');
        }

        $selectedMonth = $this->resolveMonth((string) $request->query('month', now('Asia/Tokyo')->format('Y-m')));
        [$year, $month] = $this->splitMonth($selectedMonth);

        $rows = DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards as tc')
            ->leftJoin('dbo.mx_staffs as s', DB::raw('LTRIM(RTRIM(tc.staff_name))'), '=', DB::raw('LTRIM(RTRIM(s.staff_id))'))
            ->whereRaw('YEAR(tc.work_date) = ?', [$year])
            ->whereRaw('MONTH(tc.work_date) = ?', [$month])
            ->whereNotNull('tc.staff_request')
            ->selectRaw("
                LTRIM(RTRIM(COALESCE(tc.staff_name, ''))) as staff_id,
                LTRIM(RTRIM(COALESCE(s.staff_name, tc.staff_name, ''))) as staff_name,
                CONVERT(char(7), tc.work_date, 126) as year_month,
                MAX(CONVERT(varchar(19), tc.staff_request, 120)) as self_applied_at,
                MAX(CONVERT(varchar(19), tc.manager_approval, 120)) as admin_approved
            ")
            ->groupByRaw("
                LTRIM(RTRIM(COALESCE(tc.staff_name, ''))),
                LTRIM(RTRIM(COALESCE(s.staff_name, tc.staff_name, ''))),
                CONVERT(char(7), tc.work_date, 126)
            ")
            ->orderBy('staff_id')
            ->get()
            ->map(function ($row) use ($selectedMonth): array {
                $staffId = trim((string) ($row->staff_id ?? ''));

                return [
                    'year_month' => (string) ($row->year_month ?? $selectedMonth),
                    'staff_id' => $staffId,
                    'staff_name' => (string) ($row->staff_name ?? ''),
                    'self_applied_at' => (string) ($row->self_applied_at ?? ''),
                    'admin_approved' => (string) ($row->admin_approved ?? ''),
                    'detail_url' => route('attendance.management.detail', [
                        'staffId' => $staffId,
                        'month' => $selectedMonth,
                    ]),
                ];
            })
            ->all();

        return view('staff_portal.admin.attendance.management', [
            'displayName' => $this->resolveDisplayName($staffId),
            'hidePayrollLinks' => false,
            'selectedMonth' => $selectedMonth,
            'rowCount' => count($rows),
            'rows' => $rows,
            'isAdmin' => true,
        ]);
    }

    public function paidLeave(Request $request): RedirectResponse|View
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'Please log in.');
        }

        $selectedMonth = $this->resolveMonth((string) $request->query('month', now('Asia/Tokyo')->format('Y-m')));
        [$year, $month] = $this->splitMonth($selectedMonth);

        $rows = DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards as tc')
            ->leftJoin('dbo.mx_staffs as s', DB::raw('LTRIM(RTRIM(tc.staff_name))'), '=', DB::raw('LTRIM(RTRIM(s.staff_id))'))
            ->whereRaw('YEAR(tc.work_date) = ?', [$year])
            ->whereRaw('MONTH(tc.work_date) = ?', [$month])
            ->whereRaw('ISNULL(tc.paid_leave_used, 0) > 0')
            ->orderBy('tc.work_date')
            ->orderByRaw("LTRIM(RTRIM(COALESCE(tc.staff_name, '')))")
            ->selectRaw("
                LTRIM(RTRIM(COALESCE(tc.staff_name, ''))) as staff_id,
                LTRIM(RTRIM(COALESCE(s.staff_name, tc.staff_name, ''))) as staff_name,
                CONVERT(char(10), tc.work_date, 120) as leave_date,
                LTRIM(RTRIM(COALESCE(CONVERT(nvarchar(50), tc.paid_leave_used), ''))) as paid_leave_used,
                CONVERT(varchar(19), tc.staff_request, 120) as paid_leave_applied_at,
                CONVERT(varchar(19), tc.manager_approval, 120) as admin_approved
            ")
            ->get()
            ->map(function ($row): array {
                return [
                    'leave_date' => $this->formatFullDateLabel($row->leave_date ?? ''),
                    'paid_leave_used' => $this->formatNumericDisplay($row->paid_leave_used ?? ''),
                    'applied_date' => $this->formatFullDateLabel(substr((string) ($row->paid_leave_applied_at ?? ''), 0, 10)),
                    'staff_id' => (string) ($row->staff_id ?? ''),
                    'staff_name' => (string) ($row->staff_name ?? ''),
                    'paid_leave_applied_at' => (string) ($row->paid_leave_applied_at ?? ''),
                    'admin_approved' => (string) ($row->admin_approved ?? ''),
                ];
            })
            ->all();

        return view('staff_portal.admin.attendance.paid_leave', [
            'displayName' => $this->resolveDisplayName($staffId),
            'hidePayrollLinks' => false,
            'selectedMonth' => $selectedMonth,
            'rowCount' => count($rows),
            'rows' => $rows,
            'isAdmin' => true,
        ]);
    }

    public function managementDetail(Request $request, string $staffId): RedirectResponse|View
    {
        $loginStaffId = (string) $request->session()->get('staff_id', '');
        if ($loginStaffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'Please log in.');
        }

        $selectedMonth = $this->resolveMonth((string) $request->query('month', now('Asia/Tokyo')->format('Y-m')));
        [$year, $month] = $this->splitMonth($selectedMonth);
        $monthlyStatus = $this->loadMonthlyApprovalStatus($staffId, $year, $month);

        $rows = DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards as tc')
            ->leftJoin('dbo.mx_stores as st', DB::raw('LTRIM(RTRIM(tc.work_store))'), '=', DB::raw('LTRIM(RTRIM(st.store_code))'))
            ->whereRaw('LTRIM(RTRIM(tc.staff_name)) = ?', [$staffId])
            ->whereRaw('YEAR(tc.work_date) = ?', [$year])
            ->whereRaw('MONTH(tc.work_date) = ?', [$month])
            ->orderBy('tc.work_date')
            ->selectRaw("
                CONVERT(char(10), tc.work_date, 111) as date_label,
                LTRIM(RTRIM(COALESCE(tc.holiday_category, ''))) as holiday_kubun,
                LTRIM(RTRIM(COALESCE(tc.work_type, ''))) as attendance_kubun,
                LTRIM(RTRIM(COALESCE(CONVERT(nvarchar(50), tc.work_type_time), ''))) as category_time,
                LTRIM(RTRIM(COALESCE(CONVERT(nvarchar(50), tc.paid_leave_used), ''))) as paid_leave_used,
                CAST(NULL as nvarchar(50)) as time_no,
                LTRIM(RTRIM(COALESCE(tc.actual_start, ''))) as jitu_sigyo,
                LTRIM(RTRIM(COALESCE(tc.actual_leave, ''))) as jitu_taisitu,
                LTRIM(RTRIM(COALESCE(tc.actual_break_out, ''))) as jitu_irisitu,
                LTRIM(RTRIM(COALESCE(tc.actual_end, ''))) as jitu_syugyo,
                LTRIM(RTRIM(COALESCE(tc.shift_start, ''))) as shift_sigyo,
                LTRIM(RTRIM(COALESCE(tc.shift_leave, ''))) as shift_taisitu,
                LTRIM(RTRIM(COALESCE(tc.shift_break_out, ''))) as shift_irisitu,
                LTRIM(RTRIM(COALESCE(tc.shift_end, ''))) as shift_syugyo,
                LTRIM(RTRIM(COALESCE(tc.change_start, ''))) as change_sigyo,
                LTRIM(RTRIM(COALESCE(tc.change_leave, ''))) as change_taisitu,
                LTRIM(RTRIM(COALESCE(tc.change_break_out, ''))) as change_irisitu,
                LTRIM(RTRIM(COALESCE(tc.change_end, ''))) as change_syugyo,
                LTRIM(RTRIM(COALESCE(CONVERT(nvarchar(50), tc.change_scheduled), ''))) as change_scheduled,
                LTRIM(RTRIM(COALESCE(CONVERT(nvarchar(50), tc.overtime), ''))) as overtime,
                CAST(NULL as nvarchar(50)) as night_overtime,
                LTRIM(RTRIM(COALESCE(st.store_short_name, st.store_name, CONVERT(nvarchar(50), tc.work_store), ''))) as shop_name,
                LTRIM(RTRIM(COALESCE(tc.timecard_note, ''))) as remark
            ")
            ->get()
            ->map(function ($row): array {
                $item = (array) $row;
                $item['date_label'] = $this->formatDateLabel($item['date_label'] ?? null);
                $holidayKubun = trim((string) ($item['holiday_kubun'] ?? ''));
                $attendanceKubun = trim((string) ($item['attendance_kubun'] ?? ''));
                $isHoliday = $holidayKubun !== '' && (mb_strpos($holidayKubun, '休日') !== false || mb_strpos($holidayKubun, '法休') !== false);
                if ($isHoliday && $attendanceKubun === '') {
                    $item['change_scheduled'] = '';
                }
                $item['change_scheduled'] = $this->formatNumericDisplay($item['change_scheduled'] ?? null);
                $item['overtime'] = $this->formatNumericDisplay($item['overtime'] ?? null);
                foreach ([
                    'jitu_sigyo',
                    'jitu_taisitu',
                    'jitu_irisitu',
                    'jitu_syugyo',
                    'shift_sigyo',
                    'shift_taisitu',
                    'shift_irisitu',
                    'shift_syugyo',
                    'change_sigyo',
                    'change_taisitu',
                    'change_irisitu',
                    'change_syugyo',
                ] as $key) {
                    $item[$key] = $this->formatTimeValue($item[$key] ?? null);
                }

                return $item;
            })
            ->all();

        return view('staff_portal.admin.attendance.management_detail', [
            'displayName' => $this->resolveDisplayName($loginStaffId),
            'hidePayrollLinks' => false,
            'selectedMonth' => $selectedMonth,
            'targetStaffId' => $staffId,
            'targetStaffName' => $this->resolveDisplayName($staffId),
            'rowCount' => count($rows),
            'rows' => $rows,
            'monthlyAppliedAt' => $monthlyStatus['monthlyAppliedAt'],
            'adminApprovedAt' => $monthlyStatus['adminApprovedAt'],
            'canAdminApprove' => $monthlyStatus['canAdminApprove'],
            'canAdminRemand' => $monthlyStatus['canAdminRemand'],
            'statusMessage' => (string) $request->session()->get('statusMessage', ''),
            'isAdmin' => true,
        ]);
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

    private function resolveDate(string $date): string
    {
        $date = trim($date);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            return $date;
        }

        return now('Asia/Tokyo')->format('Y-m-d');
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

    private function formatTimeValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            $formatted = $value->format('H:i');
            $year = (int) $value->format('Y');
            if (in_array($year, [1899, 1900], true) && $formatted === '00:00') {
                return '';
            }
            return $formatted;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return '';
        }

        if (
            preg_match('/\b(1899|1900)\b/', $text) === 1
            && preg_match('/\b(0?0:00|12:00\s*AM)\b/i', $text) === 1
        ) {
            return '';
        }

        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $text) === 1) {
            return substr($text, 0, 5);
        }

        if (preg_match('/(\d{1,2}):(\d{2})\s*([AP]M)$/i', $text, $matches) === 1) {
            $hour = (int) $matches[1];
            $minute = (int) $matches[2];
            $ampm = strtoupper($matches[3]);

            if ($ampm === 'AM' && $hour === 12) {
                $hour = 0;
            } elseif ($ampm === 'PM' && $hour < 12) {
                $hour += 12;
            }

            return sprintf('%02d:%02d', $hour, $minute);
        }

        if (preg_match('/\b(\d{1,2}):(\d{2})(?::\d{2})?\b/', $text, $matches) === 1) {
            return sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
        }

        try {
            $parsed = Carbon::parse($text);
            if (in_array((int) $parsed->format('Y'), [1899, 1900], true) && $parsed->format('H:i') === '00:00') {
                return '';
            }
            return $parsed->format('G:i');
        } catch (\Throwable) {
            return '';
        }
    }

    private function formatDateLabel(mixed $value): string
    {
        $text = trim((string) $value);
        if ($text === '') {
            return '';
        }

        try {
            $date = Carbon::parse($text);
            $weekdays = ['日', '月', '火', '水', '木', '金', '土'];

            return $date->format('n/j') . '(' . $weekdays[$date->dayOfWeek] . ')';
        } catch (\Throwable) {
            return $text;
        }
    }

    private function formatNumericDisplay(mixed $value): string
    {
        $text = trim((string) $value);
        if ($text === '') {
            return '';
        }

        $normalized = str_replace(',', '', $text);
        if (!is_numeric($normalized)) {
            return $text;
        }

        $number = (float) $normalized;
        if (fmod($number, 1.0) === 0.0) {
            return (string) ((int) $number);
        }

        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }

    private function formatFullDateLabel(mixed $value): string
    {
        $text = trim((string) $value);
        if ($text === '') {
            return '';
        }

        try {
            $date = Carbon::parse($text);
            $weekdays = ['日', '月', '火', '水', '木', '金', '土'];

            return $date->format('Y/n/j') . '(' . $weekdays[$date->dayOfWeek] . ')';
        } catch (\Throwable) {
            return $text;
        }
    }

    private function formatDateTimeLabel(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return null;
        }

        try {
            return Carbon::parse($text)->format('Y/n/j G:i:s');
        } catch (\Throwable) {
            return $text;
        }
    }

    private function loadMonthlyApprovalStatus(string $staffId, int $year, int $month): array
    {
        $status = DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards')
            ->whereRaw('LTRIM(RTRIM(staff_name)) = ?', [$staffId])
            ->whereRaw('YEAR(work_date) = ?', [$year])
            ->whereRaw('MONTH(work_date) = ?', [$month])
            ->selectRaw("
                MAX(CONVERT(varchar(19), staff_request, 120)) as self_applied_at,
                MAX(CONVERT(varchar(19), manager_approval, 120)) as admin_approved_at
            ")
            ->first();

        $monthlyAppliedAt = $this->formatDateTimeLabel($status->self_applied_at ?? null);
        $adminApprovedAt = $this->formatDateTimeLabel($status->admin_approved_at ?? null);

        return [
            'monthlyAppliedAt' => $monthlyAppliedAt,
            'adminApprovedAt' => $adminApprovedAt,
            'canAdminApprove' => $monthlyAppliedAt !== null && $adminApprovedAt === null,
            'canAdminRemand' => $monthlyAppliedAt !== null && $adminApprovedAt === null,
        ];
    }
}


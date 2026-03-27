<?php

namespace App\Http\Controllers\Admin\V2;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\PaidLeave\PaidLeaveV2SummaryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaidLeaveV2Controller extends Controller
{
    public function index(Request $request, PaidLeaveV2SummaryService $summaryService): View
    {
        $selectedYear = (int) date('Y');
        $selectedStaffId = trim((string) $request->query('staff_id', ''));
        $page = max(1, (int) $request->query('page', 1));
        $yearStart = sprintf('%04d-01-01', $selectedYear);
        $yearEnd = sprintf('%04d-12-31', $selectedYear);

        $staffOptions = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs as ms')
            ->leftJoin('dbo.mx_stores as st', 'ms.section', '=', 'st.store_code')
            ->select([
                DB::raw('LTRIM(RTRIM(CAST(ms.staff_id as nvarchar(50)))) as staff_id'),
                'ms.staff_name',
                'ms.staff_division',
                DB::raw('st.store_name as store_name'),
                DB::raw('ms.nyu_date as join_date'),
                DB::raw('ms.tai_date as retire_date'),
            ])
            ->where(function ($q) use ($yearEnd): void {
                $q->whereNull('ms.nyu_date')
                    ->orWhere(DB::raw("LTRIM(RTRIM(CAST(ms.nyu_date as nvarchar(50))))"), '=', '')
                    ->orWhereDate('ms.nyu_date', '<=', $yearEnd);
            })
            ->where(function ($q) use ($yearStart): void {
                $q->whereNull('ms.tai_date')
                    ->orWhere(DB::raw("LTRIM(RTRIM(CAST(ms.tai_date as nvarchar(50))))"), '=', '')
                    ->orWhereDate('ms.tai_date', '>=', $yearStart);
            })
            ->whereNotNull('ms.staff_division')
            ->where(DB::raw("LTRIM(RTRIM(CAST(ms.staff_division as nvarchar(50))))"), '<>', '')
            ->where(DB::raw("LTRIM(RTRIM(CAST(ms.staff_division as nvarchar(50))))"), 'not like', '%業務委託%')
            ->where(DB::raw("LTRIM(RTRIM(CAST(ms.staff_division as nvarchar(50))))"), '<>', '役員')
            ->orderByRaw("
                CASE
                    WHEN ms.tai_date IS NULL OR LTRIM(RTRIM(CAST(ms.tai_date as nvarchar(50)))) = '' THEN 0
                    ELSE 1
                END
            ")
            ->orderBy('ms.section')
            ->orderBy('ms.staff_id')
            ->get()
            ->map(static function ($row): array {
                $staffId = trim((string) ($row->staff_id ?? ''));
                $staffName = trim((string) ($row->staff_name ?? ''));
                $storeName = trim((string) ($row->store_name ?? ''));
                $retireDate = trim((string) ($row->retire_date ?? ''));
                $isRetired = $retireDate !== '';

                $label = $staffName !== '' ? $staffName : $staffId;
                if ($staffId !== '') {
                    $label .= ' [' . $staffId . ']';
                }
                if ($storeName !== '') {
                    $label .= ' / ' . $storeName;
                }
                $label .= $isRetired ? '（退職）' : '（在職）';

                return [
                    'staff_id' => $staffId,
                    'label' => $label,
                    'is_retired' => $isRetired,
                ];
            })
            ->all();

        $payload = $summaryService->build($selectedStaffId, $selectedYear, $page, 20);

        return view('admin_v2.work.paid_leave.index', [
            'selectedYear' => $selectedYear,
            'selectedStaffId' => $selectedStaffId,
            'historyPage' => $page,
            'staffOptions' => $staffOptions,
            'summary' => $payload['summary'],
            'historyRows' => $payload['historyRows'],
            'historyPager' => $payload['historyPager'],
        ]);
    }

    public function update(Request $request, int $yukyuNo): RedirectResponse
    {
        $selectedStaffId = trim((string) $request->input('staff_id', ''));
        $page = max(1, (int) $request->input('page', 1));

        DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_yukyu')
            ->where('yukyu_no', $yukyuNo)
            ->update([
                'addition_day' => $this->normalizeDate($request->input('addition_day')),
                'remaining_day' => $this->normalizeNumber($request->input('remaining_day')),
                'lost_num' => $this->normalizeNumber($request->input('lost_num')),
                'date_use' => $this->normalizeDate($request->input('date_use')),
                'days_used' => $this->normalizeNumber($request->input('days_used')),
            ]);

        return redirect()->route('admin.paid-leave.index', [
            'staff_id' => $selectedStaffId,
            'page' => $page,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $selectedStaffId = trim((string) $request->input('staff_id', ''));

        if ($selectedStaffId !== '') {
            DB::connection('sqlsrv_payroll')
                ->table('dbo.mx_yukyu')
                ->insert([
                    'staff_id' => $selectedStaffId,
                    'addition_day' => $this->normalizeDate($request->input('addition_day')),
                    'remaining_day' => $this->normalizeNumber($request->input('remaining_day')),
                    'lost_num' => $this->normalizeNumber($request->input('lost_num')),
                    'date_use' => $this->normalizeDate($request->input('date_use')),
                    'days_used' => $this->normalizeNumber($request->input('days_used')),
                ]);
        }

        return redirect()->route('admin.paid-leave.index', [
            'staff_id' => $selectedStaffId,
        ]);
    }

    public function destroy(Request $request, int $yukyuNo): RedirectResponse
    {
        $selectedStaffId = trim((string) $request->input('staff_id', ''));
        $page = max(1, (int) $request->input('page', 1));

        DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_yukyu')
            ->where('yukyu_no', $yukyuNo)
            ->delete();

        return redirect()->route('admin.paid-leave.index', [
            'staff_id' => $selectedStaffId,
            'page' => $page,
        ]);
    }

    private function normalizeDate(mixed $value): ?string
    {
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        try {
            return Carbon::parse($text)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeNumber(mixed $value): ?float
    {
        $text = trim(str_replace(',', '', (string) $value));
        if ($text === '') {
            return null;
        }

        return is_numeric($text) ? (float) $text : null;
    }
}

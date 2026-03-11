<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class BonusController extends Controller
{
    public function index(Request $request): View
    {
        $selectedMonth = (string) $request->query('month', now('Asia/Tokyo')->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = now('Asia/Tokyo')->format('Y-m');
        }

        $selectedCompanyId = trim((string) $request->query('company_id', ''));
        $selectedStaffId = trim((string) $request->query('staff_id', ''));
        $selectedRowKey = trim((string) $request->query('row', ''));

        $companyOptions = DB::connection('sqlsrv')
            ->table('dbo.mx_stores')
            ->whereNotNull('company_name')
            ->whereRaw("LTRIM(RTRIM(company_name)) <> ''")
            ->distinct()
            ->orderBy('company_name')
            ->pluck('company_name')
            ->map(fn ($v) => [
                'company_id' => (string) $v,
                'company_name' => (string) $v,
            ])
            ->values()
            ->all();

        $staffQuery = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs as ms')
            ->selectRaw('LTRIM(RTRIM(ms.staff_id)) as staff_id, ms.staff_name, ISNULL(ms.staff_division, ms.employment) as division')
            ->whereNotNull('ms.staff_id')
            ->whereRaw("LTRIM(RTRIM(ms.staff_id)) <> ''");

        if ($selectedCompanyId !== ''
            && Schema::connection('sqlsrv')->hasColumn('mx_staffs', 'section')
            && Schema::connection('sqlsrv')->hasColumn('mx_stores', 'store_code')
            && Schema::connection('sqlsrv')->hasColumn('mx_stores', 'company_name')) {
            $staffQuery->leftJoin('dbo.mx_stores as st', 'st.store_code', '=', 'ms.section')
                ->where('st.company_name', $selectedCompanyId);
        }

        $staffOptions = $staffQuery
            ->orderBy('ms.staff_id')
            ->get()
            ->map(fn ($r) => [
                'staff_id' => trim((string) ($r->staff_id ?? '')),
                'staff_name' => (string) ($r->staff_name ?? ''),
                'division' => (string) ($r->division ?? ''),
            ])
            ->filter(fn ($r) => $r['staff_id'] !== '')
            ->values()
            ->all();

        $seedStaffOptions = $staffOptions;
        $staffMap = [];
        foreach ($staffOptions as $s) {
            $staffMap[$s['staff_id']] = $s;
        }

        [$year, $month] = array_map('intval', explode('-', $selectedMonth));

        $entryQuery = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 1)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month]);

        if ($selectedStaffId !== '') {
            $entryQuery->whereRaw('LTRIM(RTRIM([kyuyo_staff_id])) = ?', [$selectedStaffId]);
        }

        $entries = $entryQuery
            ->orderBy('kyuyo_sho_no', 'desc')
            ->get();

        $summaryRows = [];
        foreach ($entries as $e) {
            $sid = trim((string) ($e->kyuyo_staff_id ?? ''));
            if ($sid === '') {
                continue;
            }
            $rowKey = (string) ($e->kyuyo_sho_no ?? '');
            $staff = $staffMap[$sid] ?? ['staff_name' => '', 'division' => ''];
            $summaryRows[] = [
                'key' => $rowKey,
                'entry_id' => (int) ($e->kyuyo_sho_no ?? 0),
                'staff_id' => $sid,
                'staff_name' => (string) ($staff['staff_name'] ?? ''),
                'division' => (string) ($staff['division'] ?? ''),
                'supply_month' => $this->fmtDate($e->supply_month ?? null),
                'is_locked' => (int) ($e->edit_lock ?? 0) === 1,
                'bonus_amo' => $this->num($e->bonus_amo ?? 0),
                'adjustment_cost' => $this->num($e->adjustment_cost ?? 0),
                'adjustment_year_end' => $this->num($e->adjustment_year_end ?? 0),
                'kenpo' => $this->num($e->kenpo ?? 0),
                'kaigo' => $this->num($e->kaigo ?? 0),
                'kounen' => $this->num($e->kounen ?? 0),
                'koyou' => $this->num($e->koyou ?? 0),
                'income_tax' => $this->num($e->income_tax ?? 0),
                'resident_tax' => $this->num($e->resident_tax ?? 0),
            ];
        }

        if ($selectedRowKey === '' && !empty($summaryRows)) {
            $selectedRowKey = (string) $summaryRows[0]['key'];
        }

        $selectedSummary = null;
        foreach ($summaryRows as $r) {
            if ((string) $r['key'] === $selectedRowKey) {
                $selectedSummary = $r;
                break;
            }
        }
        if ($selectedSummary === null && !empty($summaryRows)) {
            $selectedSummary = $summaryRows[0];
        }

        $detailRows = [
            'earnings' => [
                ['label' => 'Bonus Amount', 'key' => 'bonus_amo', 'value' => (float) ($selectedSummary['bonus_amo'] ?? 0)],
            ],
            'deductions' => [
                ['label' => 'Health Insurance', 'key' => 'kenpo', 'value' => (float) ($selectedSummary['kenpo'] ?? 0)],
                ['label' => 'Care Insurance', 'key' => 'kaigo', 'value' => (float) ($selectedSummary['kaigo'] ?? 0)],
                ['label' => 'Pension', 'key' => 'kounen', 'value' => (float) ($selectedSummary['kounen'] ?? 0)],
                ['label' => 'Employment Insurance', 'key' => 'koyou', 'value' => (float) ($selectedSummary['koyou'] ?? 0)],
                ['label' => 'Income Tax', 'key' => 'income_tax', 'value' => (float) ($selectedSummary['income_tax'] ?? 0)],
                ['label' => 'Resident Tax', 'key' => 'resident_tax', 'value' => (float) ($selectedSummary['resident_tax'] ?? 0)],
            ],
            'others' => [
                ['label' => 'Year-end Adjustment', 'key' => 'adjustment_year_end', 'value' => (float) ($selectedSummary['adjustment_year_end'] ?? 0)],
                ['label' => 'Reimbursement', 'key' => 'adjustment_cost', 'value' => (float) ($selectedSummary['adjustment_cost'] ?? 0)],
            ],
            'totals' => [
                'pay_total' => (float) ($selectedSummary['bonus_amo'] ?? 0),
                'deduction_total' => (float) (($selectedSummary['kenpo'] ?? 0) + ($selectedSummary['kaigo'] ?? 0) + ($selectedSummary['kounen'] ?? 0) + ($selectedSummary['koyou'] ?? 0) + ($selectedSummary['income_tax'] ?? 0) + ($selectedSummary['resident_tax'] ?? 0)),
                'others_total' => (float) (($selectedSummary['adjustment_year_end'] ?? 0) + ($selectedSummary['adjustment_cost'] ?? 0)),
                'net_pay' => (float) (($selectedSummary['bonus_amo'] ?? 0) - (($selectedSummary['kenpo'] ?? 0) + ($selectedSummary['kaigo'] ?? 0) + ($selectedSummary['kounen'] ?? 0) + ($selectedSummary['koyou'] ?? 0) + ($selectedSummary['income_tax'] ?? 0) + ($selectedSummary['resident_tax'] ?? 0)) + (($selectedSummary['adjustment_year_end'] ?? 0) + ($selectedSummary['adjustment_cost'] ?? 0))),
            ],
        ];

        return view('admin.bonus.index', [
            'selectedMonth' => $selectedMonth,
            'selectedCompanyId' => $selectedCompanyId,
            'selectedStaffId' => $selectedStaffId,
            'selectedRowKey' => $selectedRowKey,
            'companyOptions' => $companyOptions,
            'staffOptions' => $staffOptions,
            'seedStaffOptions' => $seedStaffOptions,
            'summaryRows' => $summaryRows,
            'selectedSummary' => $selectedSummary,
            'detailRows' => $detailRows,
        ]);
    }

    public function seedBulkCreate(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'company_id' => ['nullable', 'string', 'max:200'],
            'target_staff_ids' => ['required', 'array', 'min:1'],
            'target_staff_ids.*' => ['required', 'string', 'max:50'],
        ]);

        $month = (string) $v['month'];
        $monthDate = Carbon::createFromFormat('Y-m-d', $month . '-20')->toDateString();
        $created = 0;

        foreach ((array) $v['target_staff_ids'] as $sid) {
            $sid = trim((string) $sid);
            if ($sid === '') {
                continue;
            }
            $exists = DB::connection('sqlsrv_payroll')->table('dbo.mx_kyuyo_shou')
                ->where('bonus', 1)
                ->whereDate('supply_month', $monthDate)
                ->whereRaw('LTRIM(RTRIM([kyuyo_staff_id])) = ?', [$sid])
                ->exists();
            if ($exists) {
                continue;
            }

            $insert = [
                'kyuyo_staff_id' => $sid,
                'supply_month' => $monthDate,
                'bonus' => 1,
                'edit_lock' => 0,
                'bonus_amo' => 0,
                'kenpo' => 0,
                'kaigo' => 0,
                'kounen' => 0,
                'koyou' => 0,
                'income_tax' => 0,
                'resident_tax' => 0,
                'adjustment_year_end' => 0,
                'adjustment_cost' => 0,
            ];
            DB::connection('sqlsrv_payroll')->table('dbo.mx_kyuyo_shou')->insert($insert);
            $created++;
        }

        return redirect()->route('admin.bonus.index', ['month' => $month, 'company_id' => (string) ($v['company_id'] ?? '')])
            ->with('status', '賞与データを作成しました（' . $created . '件）。');
    }

    public function seedBulkDelete(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'company_id' => ['nullable', 'string', 'max:200'],
            'target_staff_ids' => ['nullable', 'array'],
            'target_staff_ids.*' => ['nullable', 'string', 'max:50'],
        ]);

        $month = (string) $v['month'];
        $monthDate = Carbon::createFromFormat('Y-m-d', $month . '-20')->toDateString();

        $query = DB::connection('sqlsrv_payroll')->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 1)
            ->whereDate('supply_month', $monthDate)
            ->where('edit_lock', 0);

        $targets = collect((array) ($v['target_staff_ids'] ?? []))->map(fn ($x) => trim((string) $x))->filter()->values()->all();
        if (!empty($targets)) {
            $query->whereIn('kyuyo_staff_id', $targets);
        }

        $deleted = $query->delete();

        return redirect()->route('admin.bonus.index', ['month' => $month, 'company_id' => (string) ($v['company_id'] ?? '')])
            ->with('status', '賞与データを削除しました（' . $deleted . '件）。');
    }

    public function save(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'entry_id' => ['required', 'integer', 'min:1'],
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'company_id' => ['nullable', 'string', 'max:200'],
            'staff_id' => ['nullable', 'string', 'max:50'],
            'row' => ['nullable', 'string', 'max:50'],
            'fields' => ['nullable', 'array'],
        ]);

        $entryId = (int) $v['entry_id'];
        $fields = (array) ($v['fields'] ?? []);
        $allow = ['bonus_amo', 'kenpo', 'kaigo', 'kounen', 'koyou', 'income_tax', 'resident_tax', 'adjustment_year_end', 'adjustment_cost'];
        $update = [];
        foreach ($allow as $k) {
            if (array_key_exists($k, $fields)) {
                $update[$k] = $this->num($fields[$k]);
            }
        }

        if (!empty($update)) {
            DB::connection('sqlsrv_payroll')->table('dbo.mx_kyuyo_shou')
                ->where('kyuyo_sho_no', $entryId)
                ->update($update);
        }

        return redirect()->route('admin.bonus.index', [
            'month' => (string) $v['month'],
            'company_id' => (string) ($v['company_id'] ?? ''),
            'staff_id' => (string) ($v['staff_id'] ?? ''),
            'row' => (string) ($v['row'] ?? ''),
        ])->with('status', '保存しました。');
    }

    public function lock(Request $request): RedirectResponse
    {
        return $this->toggleLock($request, 1, '確定しました。');
    }

    public function unlock(Request $request): RedirectResponse
    {
        return $this->toggleLock($request, 0, '未確定に戻しました。');
    }

    private function toggleLock(Request $request, int $value, string $status): RedirectResponse
    {
        $v = $request->validate([
            'entry_id' => ['required', 'integer', 'min:1'],
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'company_id' => ['nullable', 'string', 'max:200'],
            'staff_id' => ['nullable', 'string', 'max:50'],
            'row' => ['nullable', 'string', 'max:50'],
        ]);

        DB::connection('sqlsrv_payroll')->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_sho_no', (int) $v['entry_id'])
            ->update(['edit_lock' => $value]);

        return redirect()->route('admin.bonus.index', [
            'month' => (string) $v['month'],
            'company_id' => (string) ($v['company_id'] ?? ''),
            'staff_id' => (string) ($v['staff_id'] ?? ''),
            'row' => (string) ($v['row'] ?? ''),
        ])->with('status', $status);
    }

    private function fmtDate(mixed $v): string
    {
        if ($v === null || $v === '') {
            return '';
        }
        try {
            return Carbon::parse((string) $v)->format('Y/m/d');
        } catch (\Throwable) {
            return (string) $v;
        }
    }

    private function num(mixed $v): float
    {
        if ($v === null || $v === '') {
            return 0.0;
        }
        $s = str_replace(',', '', trim((string) $v));
        if ($s === '' || !is_numeric($s)) {
            return 0.0;
        }
        return (float) $s;
    }
}

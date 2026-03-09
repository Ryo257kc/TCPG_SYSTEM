<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class MasterController extends Controller
{
    public function allowance(Request $request): RedirectResponse|View
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $selectedOfficeName = trim((string) $request->query('office_name', ''));

        $companyOptions = DB::connection('sqlsrv')
            ->table('dbo.m_companies')
            ->select('company_code', 'company_name')
            ->whereNotNull('company_code')
            ->whereRaw("LTRIM(RTRIM(company_code)) <> ''")
            ->orderBy('company_code')
            ->get()
            ->map(fn ($r) => [
                'company_code' => (string) ($r->company_code ?? ''),
                'company_name' => (string) ($r->company_name ?? ''),
            ])
            ->values()
            ->all();

        $hasSlotNo = $this->hasPayrollColumn('t_allowance', 'slot_no');
        $hasAmountColumnKey = $this->hasPayrollColumn('t_allowance', 'amount_column_key');
        $hasDisplayOrder = $this->hasPayrollColumn('t_allowance', 'display_order');

        $rowsQuery = DB::connection('sqlsrv_payroll')
            ->table('dbo.t_allowance')
            ->orderBy('office_name');

        if ($hasDisplayOrder) {
            $rowsQuery->orderByRaw('ISNULL(display_order, 9999) asc');
        }
        if ($hasSlotNo) {
            $rowsQuery->orderByRaw('ISNULL(slot_no, 9999) asc');
        }
        $rowsQuery->orderBy('allowance_no');

        if ($selectedOfficeName !== '') {
            $rowsQuery->where('office_name', $selectedOfficeName);
        }

        $rows = $rowsQuery->get()
            ->map(fn ($r) => [
                'allowance_no' => (int) ($r->allowance_no ?? 0),
                'allowance_name' => (string) ($r->allowance_name ?? ''),
                'rou_target' => (int) ($r->rou_target ?? 0),
                'syaho_target' => (int) ($r->syaho_target ?? 0),
                'tax_target' => (int) ($r->tax_target ?? 0),
                'kotei_wage' => (int) ($r->kotei_wage ?? 0),
                'office_name' => trim((string) ($r->office_name ?? '')),
                'warimasi_kiso' => (int) ($r->warimasi_kiso ?? 0),
                'koujyo_kiso' => (int) ($r->koujyo_kiso ?? 0),
                'slot_no' => $hasSlotNo ? (int) ($r->slot_no ?? 0) : 0,
                'amount_column_key' => $hasAmountColumnKey ? (string) ($r->amount_column_key ?? '') : '',
                'display_order' => $hasDisplayOrder ? (int) ($r->display_order ?? 0) : 0,
            ])
            ->values();

        $counterByOffice = [];
        $rows = $rows->map(function (array $row) use (&$counterByOffice) {
            $office = $row['office_name'] !== '' ? $row['office_name'] : '_blank';
            $counterByOffice[$office] = ($counterByOffice[$office] ?? 0) + 1;
            $row['display_no'] = $counterByOffice[$office];
            if (($row['display_order'] ?? 0) <= 0) {
                $row['display_order'] = $row['display_no'];
            }
            return $row;
        })->all();

        $duplicateAmountKeys = [];
        if ($hasAmountColumnKey) {
            $dupQuery = DB::connection('sqlsrv_payroll')
                ->table('dbo.t_allowance')
                ->selectRaw('LTRIM(RTRIM(office_name)) as office_name, LTRIM(RTRIM(amount_column_key)) as amount_column_key, COUNT(*) as cnt')
                ->whereRaw("LTRIM(RTRIM(ISNULL(amount_column_key, ''))) <> ''")
                ->groupByRaw('LTRIM(RTRIM(office_name)), LTRIM(RTRIM(amount_column_key))')
                ->havingRaw('COUNT(*) > 1');
            if ($selectedOfficeName !== '') {
                $dupQuery->whereRaw('LTRIM(RTRIM(office_name)) = ?', [$selectedOfficeName]);
            }
            $duplicateAmountKeys = $dupQuery
                ->orderBy('office_name')
                ->orderBy('amount_column_key')
                ->get()
                ->map(fn ($r) => [
                    'office_name' => (string) ($r->office_name ?? ''),
                    'amount_column_key' => (string) ($r->amount_column_key ?? ''),
                    'count' => (int) ($r->cnt ?? 0),
                ])
                ->values()
                ->all();
        }

        return view('admin.master.allowance.index', [
            'rows' => $rows,
            'companyOptions' => $companyOptions,
            'selectedOfficeName' => $selectedOfficeName,
            'rowCount' => count($rows),
            'source' => 't_allowance',
            'duplicateAmountKeys' => $duplicateAmountKeys,
        ]);
    }

    public function allowanceUpdate(Request $request): RedirectResponse
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $data = $request->validate([
            'allowance_no' => ['required', 'integer'],
            'allowance_name' => ['nullable', 'string', 'max:100'],
            'display_order' => ['nullable', 'integer', 'min:1', 'max:999'],
            'amount_column_key' => ['nullable', 'string', 'max:50'],
            'office_name_filter' => ['nullable', 'string', 'max:20'],
        ]);

        $allowanceNo = (int) $data['allowance_no'];
        $targetDisplayOrder = (int) ($data['display_order'] ?? 0);

        $allowanceRow = DB::connection('sqlsrv_payroll')
            ->table('dbo.t_allowance')
            ->where('allowance_no', $allowanceNo)
            ->first(['office_name']);

        if ($allowanceRow === null) {
            return redirect()
                ->route('admin.master.allowance', [
                    'office_name' => trim((string) ($data['office_name_filter'] ?? '')),
                ])
                ->with('status', '対象データが見つかりません。');
        }

        $officeName = trim((string) ($allowanceRow->office_name ?? ''));

        $update = [
            'allowance_name' => trim((string) ($data['allowance_name'] ?? '')),
            'rou_target' => $request->boolean('rou_target') ? 1 : 0,
            'syaho_target' => $request->boolean('syaho_target') ? 1 : 0,
            'tax_target' => $request->boolean('tax_target') ? 1 : 0,
            'kotei_wage' => $request->boolean('kotei_wage') ? 1 : 0,
            'warimasi_kiso' => $request->boolean('warimasi_kiso') ? 1 : 0,
            'koujyo_kiso' => $request->boolean('koujyo_kiso') ? 1 : 0,
        ];

        if ($this->hasPayrollColumn('t_allowance', 'amount_column_key')) {
            $amountKey = trim((string) ($data['amount_column_key'] ?? ''));
            if ($amountKey === '') {
                return redirect()
                    ->route('admin.master.allowance', [
                        'office_name' => trim((string) ($data['office_name_filter'] ?? '')),
                    ])
                    ->with('status', 'AmountKey は必須です。');
            }
            $isDuplicate = DB::connection('sqlsrv_payroll')
                ->table('dbo.t_allowance')
                ->whereRaw('LTRIM(RTRIM(office_name)) = ?', [$officeName])
                ->whereRaw('LTRIM(RTRIM(amount_column_key)) = ?', [$amountKey])
                ->where('allowance_no', '<>', $allowanceNo)
                ->exists();
            if ($isDuplicate) {
                return redirect()
                    ->route('admin.master.allowance', [
                        'office_name' => trim((string) ($data['office_name_filter'] ?? '')),
                    ])
                    ->with('status', 'AmountKey が重複しています。会社内で一意になるように設定してください。');
            }
            $update['amount_column_key'] = $amountKey;
        }

        if ($this->hasPayrollColumn('t_allowance', 'display_order')) {
            $update['display_order'] = $targetDisplayOrder > 0 ? $targetDisplayOrder : 9999;
        }

        DB::connection('sqlsrv_payroll')->transaction(function () use (
            $allowanceNo,
            $officeName,
            $targetDisplayOrder,
            $update
        ): void {
            DB::connection('sqlsrv_payroll')
                ->table('dbo.t_allowance')
                ->where('allowance_no', $allowanceNo)
                ->update($update);

            if (!$this->hasPayrollColumn('t_allowance', 'display_order')) {
                return;
            }

            $rows = DB::connection('sqlsrv_payroll')
                ->table('dbo.t_allowance')
                ->whereRaw('LTRIM(RTRIM(office_name)) = ?', [$officeName])
                ->orderByRaw('ISNULL(display_order, 9999) asc')
                ->orderByRaw('ISNULL(slot_no, 9999) asc')
                ->orderBy('allowance_no')
                ->get(['allowance_no'])
                ->map(fn ($r) => (int) ($r->allowance_no ?? 0))
                ->filter(fn ($v) => $v > 0)
                ->values()
                ->all();

            if ($rows === []) {
                return;
            }

            $currentIndex = array_search($allowanceNo, $rows, true);
            if ($currentIndex !== false) {
                array_splice($rows, (int) $currentIndex, 1);
            }

            $insertIndex = $targetDisplayOrder > 0 ? min(max($targetDisplayOrder - 1, 0), count($rows)) : count($rows);
            array_splice($rows, $insertIndex, 0, [$allowanceNo]);

            $order = 1;
            foreach ($rows as $rowAllowanceNo) {
                DB::connection('sqlsrv_payroll')
                    ->table('dbo.t_allowance')
                    ->where('allowance_no', $rowAllowanceNo)
                    ->update(['display_order' => $order]);
                $order++;
            }
        });

        return redirect()
            ->route('admin.master.allowance', [
                'office_name' => trim((string) ($data['office_name_filter'] ?? '')),
            ])
            ->with('status', '更新しました（No.' . $allowanceNo . '）。');
    }
    public function allowanceEnsureSlots(Request $request): RedirectResponse
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $data = $request->validate([
            'office_name_filter' => ['required', 'string', 'max:20'],
        ]);

        $officeName = trim((string) ($data['office_name_filter'] ?? ''));
        if ($officeName === '') {
            return redirect()
                ->route('admin.master.allowance')
                ->with('status', '会社を選択してから実行してください。');
        }

        $hasSlotNo = $this->hasPayrollColumn('t_allowance', 'slot_no');
        $hasAmountColumnKey = $this->hasPayrollColumn('t_allowance', 'amount_column_key');
        $hasDisplayOrder = $this->hasPayrollColumn('t_allowance', 'display_order');

        $existingAmountKeys = [];
        if ($hasAmountColumnKey) {
            $existingAmountKeys = DB::connection('sqlsrv_payroll')
                ->table('dbo.t_allowance')
                ->whereRaw('LTRIM(RTRIM(office_name)) = ?', [$officeName])
                ->whereRaw("LTRIM(RTRIM(ISNULL(amount_column_key, ''))) <> ''")
                ->pluck('amount_column_key')
                ->map(fn ($v) => trim((string) $v))
                ->filter(fn ($v) => $v !== '')
                ->unique()
                ->values()
                ->all();
        }

        $existingSlots = [];
        if ($hasSlotNo) {
            $existingSlots = DB::connection('sqlsrv_payroll')
                ->table('dbo.t_allowance')
                ->whereRaw('LTRIM(RTRIM(office_name)) = ?', [$officeName])
                ->whereNotNull('slot_no')
                ->pluck('slot_no')
                ->map(fn ($v) => (int) $v)
                ->filter(fn ($v) => $v > 0)
                ->unique()
                ->values()
                ->all();
        }

        $requiredEarnings = [
            ['key' => 'allowance_amo_1', 'name' => '月給／時給', 'slot' => 1],
            ['key' => 'allowance_amo_2', 'name' => '役員報酬', 'slot' => 2],
            ['key' => 'allowance_amo_3', 'name' => '管理手当', 'slot' => 3],
            ['key' => 'allowance_amo_4', 'name' => '歩合手当', 'slot' => 4],
            ['key' => 'allowance_amo_5', 'name' => '調整手当', 'slot' => 5],
            ['key' => 'allowance_amo_6', 'name' => '非課税通勤費', 'slot' => 6],
            ['key' => 'allowance_amo_7', 'name' => '休日出勤手当', 'slot' => 7],
            ['key' => 'allowance_amo_8', 'name' => '残業手当', 'slot' => 8],
            ['key' => 'allowance_amo_9', 'name' => '深夜残業手当', 'slot' => 9],
            ['key' => 'allowance_amo_10', 'name' => '課税通勤費', 'slot' => 10],
            ['key' => 'allowance_amo_11', 'name' => '資格手当', 'slot' => 11],
            ['key' => 'allowance_amo_12', 'name' => '請求手当', 'slot' => 12],
            ['key' => 'allowance_amo_13', 'name' => '職務手当', 'slot' => 13],
            ['key' => 'allowance_amo_14', 'name' => '家賃補助', 'slot' => 14],
            ['key' => 'allowance_amo_15', 'name' => '休業手当', 'slot' => 15],
            ['key' => 'allowance_amo_16', 'name' => '役職手当', 'slot' => 16],
            ['key' => 'allowance_amo_17', 'name' => '固定残業手当', 'slot' => 17],
            ['key' => 'basic_salary', 'name' => '基本給', 'slot' => 0],
            ['key' => 'leave_allowance', 'name' => '休業控除', 'slot' => 0],
            ['key' => 'traffic_addition', 'name' => '非課税通勤費加算', 'slot' => 0],
            ['key' => 'late_deduction', 'name' => '遅早控除', 'slot' => 0],
            ['key' => 'absence_deduction', 'name' => '欠勤控除', 'slot' => 0],
        ];

        $maxDisplayOrder = (int) (DB::connection('sqlsrv_payroll')
            ->table('dbo.t_allowance')
            ->whereRaw('LTRIM(RTRIM(office_name)) = ?', [$officeName])
            ->max('display_order') ?? 0);
        if ($maxDisplayOrder < 0) {
            $maxDisplayOrder = 0;
        }
        $nextDisplayOrder = $maxDisplayOrder + 1;

        $insertTargets = [];
        foreach ($requiredEarnings as $item) {
            $key = (string) $item['key'];
            if ($hasAmountColumnKey && in_array($key, $existingAmountKeys, true)) {
                continue;
            }
            if (!$hasAmountColumnKey && $hasSlotNo) {
                $slot = (int) $item['slot'];
                if ($slot > 0 && in_array($slot, $existingSlots, true)) {
                    continue;
                }
            }
            $insertTargets[] = $item;
        }

        $inserted = 0;
        if ($insertTargets !== []) {
            DB::connection('sqlsrv_payroll')->transaction(function () use (
                $officeName,
                $insertTargets,
                $hasSlotNo,
                $hasAmountColumnKey,
                $hasDisplayOrder,
                &$nextDisplayOrder,
                &$inserted
            ): void {
                foreach ($insertTargets as $item) {
                    $slot = (int) ($item['slot'] ?? 0);
                    $row = [
                        'allowance_name' => (string) ($item['name'] ?? ''),
                        'rou_target' => 0,
                        'syaho_target' => 0,
                        'tax_target' => 0,
                        'kotei_wage' => 0,
                        'office_name' => $officeName,
                        'warimasi_kiso' => 0,
                        'koujyo_kiso' => 0,
                    ];
                    if ($hasSlotNo) {
                        $row['slot_no'] = $slot;
                    }
                    if ($hasAmountColumnKey) {
                        $row['amount_column_key'] = (string) ($item['key'] ?? '');
                    }
                    if ($hasDisplayOrder) {
                        $row['display_order'] = $nextDisplayOrder;
                        $nextDisplayOrder++;
                    }

                    DB::connection('sqlsrv_payroll')
                        ->table('dbo.t_allowance')
                        ->insert($row);
                    $inserted++;
                }
            });
        }

        return redirect()
            ->route('admin.master.allowance', ['office_name' => $officeName])
            ->with('status', '支給項目補完を実行しました（追加: ' . $inserted . '件）');
    }

    public function company(Request $request): RedirectResponse|View
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $keyword = trim((string) $request->query('q', ''));

        $hasLegacyStoreNo = $this->hasColumn('m_companies', 'legacy_store_no');
        $hasCompanyCode = $this->hasColumn('m_companies', 'company_code');
        $hasCompanyNameKana = $this->hasColumn('m_companies', 'company_name_kana');
        $hasCompanyAddress = $this->hasColumn('m_companies', 'company_address');
        $hasOfficeNumber = $this->hasColumn('m_companies', 'office_number');
        $hasPhone = $this->hasColumn('m_companies', 'phone');
        $hasFax = $this->hasColumn('m_companies', 'fax');

        $selects = ['company_id', 'company_name'];
        if ($hasLegacyStoreNo) $selects[] = 'legacy_store_no';
        if ($hasCompanyCode) $selects[] = 'company_code';
        if ($hasCompanyNameKana) $selects[] = 'company_name_kana';
        if ($hasCompanyAddress) $selects[] = 'company_address';
        if ($hasOfficeNumber) $selects[] = 'office_number';
        if ($hasPhone) $selects[] = 'phone';
        if ($hasFax) $selects[] = 'fax';

        $query = DB::connection('sqlsrv')
            ->table('dbo.m_companies')
            ->select($selects)
            ->orderBy('company_id');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('company_name', 'like', '%' . $keyword . '%')
                    ->orWhere('company_code', 'like', '%' . $keyword . '%')
                    ->orWhere('office_number', 'like', '%' . $keyword . '%');
            });
        }

        $rows = $query->get()
            ->map(fn ($r) => [
                'company_id' => (string) ($r->company_id ?? ''),
                'legacy_store_no' => $hasLegacyStoreNo ? (string) ($r->legacy_store_no ?? '') : '',
                'company_code' => $hasCompanyCode ? (string) ($r->company_code ?? '') : '',
                'company_name' => (string) ($r->company_name ?? ''),
                'company_name_kana' => $hasCompanyNameKana ? (string) ($r->company_name_kana ?? '') : '',
                'company_address' => $hasCompanyAddress ? (string) ($r->company_address ?? '') : '',
                'office_number' => $hasOfficeNumber ? (string) ($r->office_number ?? '') : '',
                'phone' => $hasPhone ? (string) ($r->phone ?? '') : '',
                'fax' => $hasFax ? (string) ($r->fax ?? '') : '',
            ])
            ->all();

        return view('admin.master.company.index', [
            'keyword' => $keyword,
            'rows' => $rows,
            'rowCount' => count($rows),
            'source' => 'm_companies',
        ]);
    }

    public function staff(Request $request): RedirectResponse|View
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $keyword = trim((string) $request->query('q', ''));

        $query = DB::connection('sqlsrv')
            ->table('dbo.m_staffs as ms')
            ->leftJoin('dbo.m_stores as st', 'ms.store_code', '=', 'st.store_code')
            ->select([
                DB::raw('ms.staff_code as staff_id'),
                'ms.staff_name',
                'ms.staff_name_kana',
                'ms.store_code',
                DB::raw('st.store_name as store_name'),
                'ms.employment_status',
                'ms.is_store_manager',
                'ms.is_daily_report_user',
                'ms.retire_date',
            ])
            ->orderBy('ms.staff_code');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('ms.staff_code', 'like', '%' . $keyword . '%')
                    ->orWhere('ms.staff_name', 'like', '%' . $keyword . '%')
                    ->orWhere('ms.store_code', 'like', '%' . $keyword . '%');
            });
        }

        $rows = $query->get()
            ->map(fn ($r) => [
                'staff_id' => (string) ($r->staff_id ?? ''),
                'staff_name' => (string) ($r->staff_name ?? ''),
                'staff_name_kana' => (string) ($r->staff_name_kana ?? ''),
                'store_code' => (string) ($r->store_code ?? ''),
                'store_name' => (string) ($r->store_name ?? ''),
                'employment_status' => (string) ($r->employment_status ?? ''),
                'is_store_manager' => (int) ($r->is_store_manager ?? 0),
                'is_daily_report_user' => (int) ($r->is_daily_report_user ?? 0),
                'retire_date' => (string) ($r->retire_date ?? ''),
            ])
            ->all();

        return view('admin.master.staff.index', [
            'keyword' => $keyword,
            'rows' => $rows,
            'rowCount' => count($rows),
            'source' => 'm_staffs',
        ]);
    }

    public function store(Request $request): RedirectResponse|View
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $keyword = trim((string) $request->query('q', ''));

        $query = DB::connection('sqlsrv')
            ->table('dbo.m_stores')
            ->select([
                'store_code',
                'store_name',
                'company_name',
                'store_short_name',
                'phone',
                'is_closed',
                'legacy_no',
            ])
            ->orderBy('store_code');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('store_code', 'like', '%' . $keyword . '%')
                    ->orWhere('store_name', 'like', '%' . $keyword . '%')
                    ->orWhere('company_name', 'like', '%' . $keyword . '%');
            });
        }

        $rows = $query->get()
            ->map(fn ($r) => [
                'store_code' => (string) ($r->store_code ?? ''),
                'store_name' => (string) ($r->store_name ?? ''),
                'company_name' => (string) ($r->company_name ?? ''),
                'store_short_name' => (string) ($r->store_short_name ?? ''),
                'phone' => (string) ($r->phone ?? ''),
                'is_closed' => (int) ($r->is_closed ?? 0),
                'legacy_no' => (string) ($r->legacy_no ?? ''),
            ])
            ->all();

        return view('admin.master.store.index', [
            'keyword' => $keyword,
            'rows' => $rows,
            'rowCount' => count($rows),
            'source' => 'm_stores',
        ]);
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            return Schema::connection('sqlsrv')->hasColumn($table, $column)
                || Schema::connection('sqlsrv')->hasColumn('dbo.' . $table, $column);
        } catch (\Throwable) {
            return false;
        }
    }

    private function hasPayrollColumn(string $table, string $column): bool
    {
        try {
            return Schema::connection('sqlsrv_payroll')->hasColumn($table, $column)
                || Schema::connection('sqlsrv_payroll')->hasColumn('dbo.' . $table, $column);
        } catch (\Throwable) {
            return false;
        }
    }
}


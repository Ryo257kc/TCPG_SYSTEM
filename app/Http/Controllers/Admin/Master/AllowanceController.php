<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AllowanceController extends Controller
{
    public function index(Request $request): RedirectResponse|View
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $selectedOfficeName = trim((string) $request->query('office_name', ''));
        $allowanceTable = $this->resolveAllowanceTable();
        $source = $allowanceTable;

        $companyOptions = DB::connection('sqlsrv')
            ->table('dbo.mx_companies')
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

        $hasSlotNo = $this->hasPayrollColumn($allowanceTable, 'slot_no');
        $hasAmountColumnKey = $this->hasPayrollColumn($allowanceTable, 'amount_column_key');
        $hasDisplayOrder = $this->hasPayrollColumn($allowanceTable, 'display_order');

        $rowsQuery = DB::connection('sqlsrv_payroll')
            ->table('dbo.' . $allowanceTable)
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
                ->table('dbo.' . $allowanceTable)
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
            'source' => $source,
            'duplicateAmountKeys' => $duplicateAmountKeys,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $allowanceTable = $this->resolveAllowanceTable();
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
            ->table('dbo.' . $allowanceTable)
            ->where('allowance_no', $allowanceNo)
            ->first(['office_name']);

        if ($allowanceRow === null) {
            return redirect()
                ->route('admin.master.allowance', ['office_name' => trim((string) ($data['office_name_filter'] ?? ''))])
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

        if ($this->hasPayrollColumn($allowanceTable, 'amount_column_key')) {
            $amountKey = trim((string) ($data['amount_column_key'] ?? ''));
            if ($amountKey === '') {
                return redirect()
                    ->route('admin.master.allowance', ['office_name' => trim((string) ($data['office_name_filter'] ?? ''))])
                    ->with('status', 'AmountKey は必須です。');
            }

            $isDuplicate = DB::connection('sqlsrv_payroll')
                ->table('dbo.' . $allowanceTable)
                ->whereRaw('LTRIM(RTRIM(office_name)) = ?', [$officeName])
                ->whereRaw('LTRIM(RTRIM(amount_column_key)) = ?', [$amountKey])
                ->where('allowance_no', '<>', $allowanceNo)
                ->exists();
            if ($isDuplicate) {
                return redirect()
                    ->route('admin.master.allowance', ['office_name' => trim((string) ($data['office_name_filter'] ?? ''))])
                    ->with('status', 'AmountKey が重複しています。');
            }
            $update['amount_column_key'] = $amountKey;
        }

        if ($this->hasPayrollColumn($allowanceTable, 'display_order')) {
            $update['display_order'] = $targetDisplayOrder > 0 ? $targetDisplayOrder : 9999;
        }

        DB::connection('sqlsrv_payroll')->transaction(function () use ($allowanceTable, $allowanceNo, $officeName, $targetDisplayOrder, $update): void {
            DB::connection('sqlsrv_payroll')
                ->table('dbo.' . $allowanceTable)
                ->where('allowance_no', $allowanceNo)
                ->update($update);

            if (!$this->hasPayrollColumn($allowanceTable, 'display_order')) {
                return;
            }

            $rows = DB::connection('sqlsrv_payroll')
                ->table('dbo.' . $allowanceTable)
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
                    ->table('dbo.' . $allowanceTable)
                    ->where('allowance_no', $rowAllowanceNo)
                    ->update(['display_order' => $order]);
                $order++;
            }
        });

        return redirect()
            ->route('admin.master.allowance', ['office_name' => trim((string) ($data['office_name_filter'] ?? ''))])
            ->with('status', '更新しました。');
    }

    public function ensureSlots(Request $request): RedirectResponse
    {
        if (!session('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $allowanceTable = $this->resolveAllowanceTable();
        $data = $request->validate([
            'office_name_filter' => ['required', 'string', 'max:20'],
        ]);

        $officeName = trim((string) ($data['office_name_filter'] ?? ''));
        if ($officeName === '') {
            return redirect()->route('admin.master.allowance')->with('status', '会社を選択してください。');
        }

        $hasSlotNo = $this->hasPayrollColumn($allowanceTable, 'slot_no');
        $hasAmountColumnKey = $this->hasPayrollColumn($allowanceTable, 'amount_column_key');
        $hasDisplayOrder = $this->hasPayrollColumn($allowanceTable, 'display_order');

        $existingAmountKeys = [];
        if ($hasAmountColumnKey) {
            $existingAmountKeys = DB::connection('sqlsrv_payroll')
                ->table('dbo.' . $allowanceTable)
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
                ->table('dbo.' . $allowanceTable)
                ->whereRaw('LTRIM(RTRIM(office_name)) = ?', [$officeName])
                ->whereNotNull('slot_no')
                ->pluck('slot_no')
                ->map(fn ($v) => (int) $v)
                ->filter(fn ($v) => $v > 0)
                ->unique()
                ->values()
                ->all();
        }

        $required = [
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
        ];

        $maxDisplayOrder = (int) (DB::connection('sqlsrv_payroll')
            ->table('dbo.' . $allowanceTable)
            ->whereRaw('LTRIM(RTRIM(office_name)) = ?', [$officeName])
            ->max('display_order') ?? 0);

        $inserted = 0;
        foreach ($required as $item) {
            $key = (string) $item['key'];
            $slot = (int) $item['slot'];
            $missingByKey = !$hasAmountColumnKey || !in_array($key, $existingAmountKeys, true);
            $missingBySlot = !$hasSlotNo || ($slot <= 0 || !in_array($slot, $existingSlots, true));
            if (!$missingByKey && !$missingBySlot) {
                continue;
            }

            $payload = [
                'office_name' => $officeName,
                'allowance_name' => (string) $item['name'],
                'rou_target' => 0,
                'syaho_target' => 0,
                'tax_target' => 0,
                'kotei_wage' => 0,
                'warimasi_kiso' => 0,
                'koujyo_kiso' => 0,
            ];
            if ($hasAmountColumnKey) {
                $payload['amount_column_key'] = $key;
            }
            if ($hasSlotNo) {
                $payload['slot_no'] = $slot;
            }
            if ($hasDisplayOrder) {
                $maxDisplayOrder++;
                $payload['display_order'] = $maxDisplayOrder;
            }

            DB::connection('sqlsrv_payroll')->table('dbo.' . $allowanceTable)->insert($payload);
            $inserted++;
        }

        return redirect()
            ->route('admin.master.allowance', ['office_name' => $officeName])
            ->with('status', $inserted > 0 ? "不足スロットを {$inserted} 件追加しました。" : '不足スロットはありません。');
    }

    private function resolveAllowanceTable(): string
    {
        return 'mx_allowance';
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

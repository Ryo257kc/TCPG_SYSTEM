<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class PayrollV2Controller extends Controller
{
    private const SECTION_KEYS = [
        'BaseInfo' => [
            'fuyo_sum',
            'work_kiso_num',
            'transfer_balance',
            'tax_amount',
            'working_time',
            'year_working_time',
            'traffic_day',
            'yukyu',
            'syaho',
            'koyou',
            'memo',
        ],
        'Attendance' => [
            'work_in_num', 'absence_num', 'work_time', 'work_time_num',
            'work_horiday_num', 'overtime', 'night_over_time', 'late_time',
            'horiday_true', 'horiday_true_num',
            'days_closed', 'time_closed',
        ],
        'Earnings' => [
            'basic_salary', 'officer_com', 'allowance_amo_1', 'allowance_amo_2', 'allowance_amo_3',
            'allowance_amo_4', 'allowance_amo_5', 'allowance_amo_6', 'allowance_amo_7',
            'allowance_amo_8', 'allowance_amo_9', 'allowance_amo_10', 'allowance_amo_11',
            'allowance_amo_12', 'allowance_amo_13', 'allowance_amo_14', 'allowance_amo_15',
            'allowance_amo_16', 'allowance_amo_17', 'leave_allowance', 'traffic_addition',
            'late_deduction', 'absence_deduction',
            'taxation_sum', 'not_taxation_sum', 'supply_sum',
        ],
        'Deductions' => [
            'kenpo', 'kaigo', 'kounen', 'koyou', 'income_tax', 'resident_tax',
            'rent_cost', 'adjustment_cost', 'syaho_sum', 'deduction_sum',
            'syaho_deduction_sum',
            'koyou_office', 'jidou_office', 'rousai_office',
        ],
        'Sales' => [
            'peple_num', 'km',
            'unpaid_amo', 'kitazaike', 'higashi_kakogawa', 'tsubasa_harima', 'own_cost',
            'sakura_hari', 'orita_hari', 'miyamoto_hari', 'yokoi_hari',
        ],
        'Others' => [
            'adjustment_year_end', 'koujyo_1',
            'cost_liquidation',
        ],
        'ReferenceTotals' => [
            'kotei_sum', 'yakuin_sum',
            'not_syaho', 'not_rouho', 'not_supply',
            'rouho_target_sum', 'syaho_target_sum',
        ],
    ];

    private const EDITABLE_KEYS = [
        'work_in_num', 'absence_num', 'work_time', 'work_horiday_num', 'overtime', 'night_over_time',
        'late_time', 'horiday_true', 'horiday_true_num', 'days_closed', 'time_closed',

        'basic_salary', 'officer_com', 'leave_allowance', 'traffic_addition',
        'allowance_amo_1', 'allowance_amo_2', 'allowance_amo_3', 'allowance_amo_4', 'allowance_amo_5',
        'allowance_amo_6', 'allowance_amo_7', 'allowance_amo_8', 'allowance_amo_9', 'allowance_amo_10',
        'allowance_amo_11', 'allowance_amo_12', 'allowance_amo_13', 'allowance_amo_14',
        'allowance_amo_15', 'allowance_amo_16', 'allowance_amo_17',

        'kenpo', 'kaigo', 'kounen', 'koyou', 'income_tax', 'resident_tax',
        'rent_cost', 'adjustment_cost',

        'adjustment_year_end', 'koujyo_1', 'unpaid_amo', 'cost_liquidation',
        'kitazaike', 'higashi_kakogawa', 'tsubasa_harima', 'own_cost',
        'sakura_hari', 'orita_hari', 'miyamoto_hari', 'yokoi_hari',
    ];

    public function index(Request $request): View
    {
        $bonusColumn = $this->payrollColumn('is_bonus', 'bonus');
        $staffCodeColumn = $this->payrollColumn('staff_code', 'kyuyo_staff_id');
        $entryIdColumn = $this->payrollColumn('payroll_entry_id', 'kyuyo_sho_no');
        $payloadColumn = $this->payrollColumn('raw_payload', null);

        $availableMonths = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->selectRaw('YEAR([supply_month]) as y, MONTH([supply_month]) as m')
            ->where($bonusColumn, 0)
            ->whereNotNull('supply_month')
            ->groupByRaw('YEAR([supply_month]), MONTH([supply_month])')
            ->orderByRaw('YEAR([supply_month]) desc, MONTH([supply_month]) desc')
            ->get()
            ->map(fn ($row) => sprintf('%04d-%02d', (int) $row->y, (int) $row->m))
            ->values()
            ->all();

        $selectedMonth = (string) $request->query('month', $availableMonths[0] ?? now('Asia/Tokyo')->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = $availableMonths[0] ?? now('Asia/Tokyo')->format('Y-m');
        }
        [$year, $month] = [(int) substr($selectedMonth, 0, 4), (int) substr($selectedMonth, 5, 2)];

        $selectedCompanyId = trim((string) $request->query('company_id', ''));
        $selectedStaffId = trim((string) $request->query('staff_id', ''));

        $companyOptions = DB::connection('sqlsrv')
            ->table('dbo.mx_stores')
            ->select('company_name')
            ->whereNotNull('company_name')
            ->whereRaw('LTRIM(RTRIM(company_name)) <> ?', [''])
            ->distinct()
            ->orderBy('company_name')
            ->get()
            ->map(fn ($row) => [
                'company_id' => (string) $row->company_name,
                'company_name' => (string) $row->company_name,
            ])
            ->values()
            ->all();

        $staffIdColumn = $this->staffColumn('staff_code', 'staff_id');
        $staffStoreColumn = $this->staffColumn('store_code', 'section');
        $staffDivisionExpr = $this->staffDivisionSelectExpr();

        $staffQuery = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs as ms')
            ->leftJoin('dbo.mx_stores as st', 'ms.' . $staffStoreColumn, '=', 'st.store_code')
            ->whereNotNull('ms.' . $staffIdColumn)
            ->whereRaw('LTRIM(RTRIM(ms.' . $staffIdColumn . ')) <> ?', [''])
            ->selectRaw('LTRIM(RTRIM(ms.' . $staffIdColumn . ')) as staff_id, ms.staff_name, ' . $staffDivisionExpr . ' as staff_division, st.company_name, st.store_short_name');

        if ($selectedCompanyId !== '') {
            $staffQuery->where('st.company_name', $selectedCompanyId);
        }

        $staffOptions = $staffQuery
            ->orderBy('ms.' . $staffIdColumn)
            ->get()
            ->map(fn ($row) => [
                'staff_id' => trim((string) ($row->staff_id ?? '')),
                'staff_name' => trim((string) ($row->staff_name ?? '')),
                'staff_division' => trim((string) ($row->staff_division ?? '')),
                'company_name' => trim((string) ($row->company_name ?? '')),
                'store_short_name' => trim((string) ($row->store_short_name ?? '')),
            ])
            ->filter(fn ($row) => $row['staff_id'] !== '')
            ->values()
            ->all();

        $staffNameMap = [];
        $staffMetaMap = [];
        foreach ($staffOptions as $staff) {
            $id = (string) $staff['staff_id'];
            $staffNameMap[$id] = (string) $staff['staff_name'];
            $staffMetaMap[$id] = $staff;
        }

        $rowsQuery = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where($bonusColumn, 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month]);

        if ($selectedStaffId !== '') {
            $rowsQuery->whereRaw('LTRIM(RTRIM(' . $staffCodeColumn . ')) = ?', [$selectedStaffId]);
        }

        if ($selectedCompanyId !== '') {
            $companyStaffIds = array_values(array_unique(array_map(
                fn ($row) => (string) ($row['staff_id'] ?? ''),
                array_filter($staffOptions, fn ($row) => (string) ($row['company_name'] ?? '') === $selectedCompanyId)
            )));
            if ($companyStaffIds === []) {
                $rowsQuery->whereRaw('1 = 0');
            } else {
                $rowsQuery->whereIn(DB::raw('LTRIM(RTRIM(' . $staffCodeColumn . '))'), $companyStaffIds);
            }
        }

        $entryRows = $rowsQuery
            ->orderByRaw('LTRIM(RTRIM(' . $staffCodeColumn . ')) ASC')
            ->orderBy($entryIdColumn, 'desc')
            ->get();

        $summaryRows = [];
        $rowByStaff = [];

        foreach ($entryRows as $row) {
            $staffId = trim((string) ($row->{$staffCodeColumn} ?? ''));
            if ($staffId === '' || isset($rowByStaff[$staffId])) {
                continue;
            }

            $payload = $payloadColumn !== null ? $this->decodePayload($row->{$payloadColumn} ?? null) : [];

            $payTotal = $payload['supply_sum'] ?? null;
            $deductTotal = $payload['deduction_sum'] ?? null;
            $netPay = $payload['supply_deduction_sum'] ?? null;
            $otherTotal = $payload['other_total'] ?? null;
            if ($otherTotal === null || $otherTotal === '') {
                $otherTotal = (float) ($payload['adjustment_year_end'] ?? 0)
                    + (float) ($payload['cost_liquidation'] ?? 0)
                    + (float) ($payload['adjustment_cost'] ?? 0);
            }

            $item = [
                'payroll_entry_id' => (int) ($row->{$entryIdColumn} ?? 0),
                'staff_id' => $staffId,
                'staff_name' => (string) ($staffNameMap[$staffId] ?? ''),
                'staff_division' => (string) (($staffMetaMap[$staffId]['staff_division'] ?? '') ?: ''),
                'company_name' => (string) (($staffMetaMap[$staffId]['company_name'] ?? '') ?: ''),
                'store_short_name' => (string) (($staffMetaMap[$staffId]['store_short_name'] ?? '') ?: ''),
                'supply_month' => (string) ($row->supply_month ?? ''),
                'pay_total' => $this->fmtYen($payTotal),
                'deduction_total' => $this->fmtYen($deductTotal),
                'other_total' => $this->fmtYen($otherTotal),
                'net_pay' => $this->fmtYen($netPay),
                'is_edit_locked' => ((int) ($row->is_edit_locked ?? 0)) === 1,
                'attendance_checked' => ((int) ($payload['attendance_checked'] ?? 0)) === 1,
            ];
            $rowByStaff[$staffId] = ['summary' => $item, 'payload' => $payload];
            $summaryRows[] = $item;
        }

        if ($selectedStaffId === '' && $summaryRows !== []) {
            $selectedStaffId = (string) ($summaryRows[0]['staff_id'] ?? '');
        }

        $selectedSummary = null;
        $selectedPayloadSections = [];
        $displayLabelMap = $this->defaultPayloadLabels();
        $selectedMasterInfo = ['kihon' => [], 'syaho' => [], 'resident' => []];
        if ($selectedStaffId !== '' && isset($rowByStaff[$selectedStaffId])) {
            $selectedSummary = $rowByStaff[$selectedStaffId]['summary'];
            $allowanceMeta = $this->loadAllowanceMetaForStaff((string) ($selectedSummary['staff_id'] ?? ''));
            $displayLabelMap = array_merge($displayLabelMap, (array) ($allowanceMeta['labels'] ?? []));
            $selectedPayloadSections = $this->buildDisplayPayloadSections(
                $rowByStaff[$selectedStaffId]['payload'],
                $allowanceMeta['orders']
            );
            $staffBaseInfo = $this->loadStaffBaseInfoForDisplay((string) ($selectedSummary['staff_id'] ?? ''));
            if ($staffBaseInfo !== []) {
                $selectedPayloadSections['BaseInfo'] = array_merge(
                    (array) ($selectedPayloadSections['BaseInfo'] ?? []),
                    $staffBaseInfo
                );
            }
            try {
                /** @var \App\Services\Admin\Payroll\PayrollCalculationService $calc */
                $calc = app(\App\Services\Admin\Payroll\PayrollCalculationService::class);
                $selectedMasterInfo = $calc->resolveMasterInfoForDisplay(
                    (string) ($selectedSummary['staff_id'] ?? ''),
                    sprintf('%04d-%02d-01', $year, $month)
                );
            } catch (\Throwable) {
                $selectedMasterInfo = ['kihon' => [], 'syaho' => [], 'resident' => []];
            }
        }

        return view('admin.payroll_v2.index', [
            'availableMonths' => $availableMonths,
            'selectedMonth' => $selectedMonth,
            'companyOptions' => $companyOptions,
            'selectedCompanyId' => $selectedCompanyId,
            'staffOptions' => $staffOptions,
            'selectedStaffId' => $selectedStaffId,
            'summaryRows' => $summaryRows,
            'selectedSummary' => $selectedSummary,
            'selectedPayloadSections' => $selectedPayloadSections,
            'selectedMasterInfo' => $selectedMasterInfo,
            'displayLabelMap' => $displayLabelMap,
            'editablePayloadKeys' => array_fill_keys(self::EDITABLE_KEYS, true),
            'bulkStaffRows' => $staffOptions,
        ]);
    }

    public function seedBulkCreate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'staff_id' => ['nullable', 'string', 'max:50'],
            'company_id' => ['nullable', 'string', 'max:200'],
            'target_staff_ids' => ['nullable', 'array'],
            'target_staff_ids.*' => ['string', 'max:50'],
        ]);

        [$year, $month] = [(int) substr((string) $validated['month'], 0, 4), (int) substr((string) $validated['month'], 5, 2)];
        $targets = collect((array) ($validated['target_staff_ids'] ?? []))
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->unique()
            ->values()
            ->all();

        $selectedStaffId = trim((string) ($validated['staff_id'] ?? ''));
        if ($targets === [] && $selectedStaffId !== '') {
            $targets = [$selectedStaffId];
        }
        if ($targets === []) {
            return $this->redirectV2($request)->with('error', 'No target staff selected.');
        }

        $bonusCol = $this->payrollColumn('is_bonus', 'bonus');
        $staffCol = $this->payrollColumn('staff_code', 'kyuyo_staff_id');
        $lockCol = $this->payrollColumn('is_edit_locked', 'edit_lock');

        $existingIds = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where($bonusCol, 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month])
            ->whereIn(DB::raw('LTRIM(RTRIM(' . $staffCol . '))'), $targets)
            ->selectRaw('DISTINCT LTRIM(RTRIM(' . $staffCol . ')) as staff_id')
            ->pluck('staff_id')
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->values()
            ->all();
        $existingMap = array_fill_keys($existingIds, true);

        $created = 0;
        $skipped = 0;
        $supplyDate = sprintf('%04d-%02d-20', $year, $month);

        foreach ($targets as $sid) {
            if (isset($existingMap[$sid])) {
                $skipped++;
                continue;
            }

            $insert = [
                $staffCol => $sid,
                'supply_month' => $supplyDate,
                $bonusCol => 0,
                $lockCol => 0,
            ];

            if (Schema::connection('sqlsrv_payroll')->hasColumn('mx_kyuyo_shou', 'raw_payload')) {
                $insert['raw_payload'] = '{}';
            }
            if (Schema::connection('sqlsrv_payroll')->hasColumn('mx_kyuyo_shou', 'payment_total')) {
                $insert['payment_total'] = 0;
            }
            if (Schema::connection('sqlsrv_payroll')->hasColumn('mx_kyuyo_shou', 'salary_total')) {
                $insert['salary_total'] = 0;
            }
            if (Schema::connection('sqlsrv_payroll')->hasColumn('mx_kyuyo_shou', 'transfer_amount')) {
                $insert['transfer_amount'] = 0;
            }
            if (Schema::connection('sqlsrv_payroll')->hasColumn('mx_kyuyo_shou', 'bonus_amount')) {
                $insert['bonus_amount'] = 0;
            }
            if (Schema::connection('sqlsrv_payroll')->hasColumn('mx_kyuyo_shou', 'created_at')) {
                $insert['created_at'] = now('Asia/Tokyo');
            }
            if (Schema::connection('sqlsrv_payroll')->hasColumn('mx_kyuyo_shou', 'updated_at')) {
                $insert['updated_at'] = now('Asia/Tokyo');
            }

            DB::connection('sqlsrv_payroll')->table('dbo.mx_kyuyo_shou')->insert($insert);
            $created++;
        }

        return $this->redirectV2($request)->with('status', 'Payroll rows created: ' . $created . ' / skipped existing: ' . $skipped);
    }

    public function seedBulkDelete(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'staff_id' => ['nullable', 'string', 'max:50'],
            'company_id' => ['nullable', 'string', 'max:200'],
            'target_staff_ids' => ['nullable', 'array'],
            'target_staff_ids.*' => ['string', 'max:50'],
        ]);

        [$year, $month] = [(int) substr((string) $validated['month'], 0, 4), (int) substr((string) $validated['month'], 5, 2)];
        $targets = collect((array) ($validated['target_staff_ids'] ?? []))
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->unique()
            ->values()
            ->all();

        $selectedStaffId = trim((string) ($validated['staff_id'] ?? ''));
        if ($targets === [] && $selectedStaffId !== '') {
            $targets = [$selectedStaffId];
        }
        if ($targets === []) {
            return $this->redirectV2($request)->with('error', 'No target staff selected.');
        }

        $bonusCol = $this->payrollColumn('is_bonus', 'bonus');
        $staffCol = $this->payrollColumn('staff_code', 'kyuyo_staff_id');
        $entryIdCol = $this->payrollColumn('payroll_entry_id', 'kyuyo_sho_no');
        $lockCol = $this->payrollColumn('is_edit_locked', 'edit_lock');

        $entries = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where($bonusCol, 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month])
            ->whereIn(DB::raw('LTRIM(RTRIM(' . $staffCol . '))'), $targets)
            ->get([$entryIdCol . ' as entry_id', $lockCol . ' as lock_flag']);

        if ($entries->isEmpty()) {
            return $this->redirectV2($request)->with('status', 'No payroll rows to delete.');
        }

        $deleted = 0;
        $skippedLocked = 0;
        foreach ($entries as $entry) {
            if ((int) ($entry->lock_flag ?? 0) === 1) {
                $skippedLocked++;
                continue;
            }
            $deleted += DB::connection('sqlsrv_payroll')
                ->table('dbo.mx_kyuyo_shou')
                ->where($entryIdCol, (int) $entry->entry_id)
                ->delete();
        }

        return $this->redirectV2($request)->with('status', 'Deleted: ' . $deleted . ' / skipped locked: ' . $skippedLocked);
    }

    public function syncAttendance(Request $request): RedirectResponse
    {
        try {
            /** @var \App\Services\Admin\Payroll\PayrollEntryActionService $service */
            $service = app(\App\Services\Admin\Payroll\PayrollEntryActionService::class);
            $result = $service->syncAttendance($request, $this->attendanceAggregateService());
        } catch (\Throwable $e) {
            return $this->redirectV2($request)->with('error', $e->getMessage());
        }

        return $this->redirectFromActionResult($result, $request);
    }

    public function syncAttendanceBulk(Request $request): RedirectResponse
    {
        try {
            /** @var \App\Services\Admin\Payroll\PayrollEntryActionService $service */
            $service = app(\App\Services\Admin\Payroll\PayrollEntryActionService::class);
            $result = $service->syncAttendanceBulk($request, $this->attendanceAggregateService());
        } catch (\Throwable $e) {
            return $this->redirectV2($request)->with('error', $e->getMessage());
        }

        return $this->redirectFromActionResult($result, $request);
    }

    public function recalcDefaults(Request $request): RedirectResponse
    {
        return $this->runCalculationAction($request, __FUNCTION__);
    }

    public function recalcKoyou(Request $request): RedirectResponse
    {
        return $this->runCalculationAction($request, __FUNCTION__);
    }

    public function recalcPremiumDeduction(Request $request): RedirectResponse
    {
        return $this->runCalculationAction($request, __FUNCTION__);
    }

    public function recalcIncomeTax(Request $request): RedirectResponse
    {
        return $this->runCalculationAction($request, __FUNCTION__);
    }

    public function save(Request $request): RedirectResponse
    {
        try {
            /** @var \App\Services\Admin\Payroll\PayrollEntryActionService $service */
            $service = app(\App\Services\Admin\Payroll\PayrollEntryActionService::class);
            $result = $service->save($request);
        } catch (\Throwable $e) {
            return $this->redirectV2($request)->with('error', $e->getMessage());
        }

        return $this->redirectFromActionResult($result, $request);
    }

    public function lock(Request $request): RedirectResponse
    {
        try {
            /** @var \App\Services\Admin\Payroll\PayrollEntryActionService $service */
            $service = app(\App\Services\Admin\Payroll\PayrollEntryActionService::class);
            $result = $service->toggleLock($request, true);
        } catch (\Throwable $e) {
            return $this->redirectV2($request)->with('error', $e->getMessage());
        }

        return $this->redirectFromActionResult($result, $request);
    }

    public function unlock(Request $request): RedirectResponse
    {
        try {
            /** @var \App\Services\Admin\Payroll\PayrollEntryActionService $service */
            $service = app(\App\Services\Admin\Payroll\PayrollEntryActionService::class);
            $result = $service->toggleLock($request, false);
        } catch (\Throwable $e) {
            return $this->redirectV2($request)->with('error', $e->getMessage());
        }

        return $this->redirectFromActionResult($result, $request);
    }
    private function redirectFromActionResult(array $result, Request $request): RedirectResponse
    {
        $query = is_array($result['query'] ?? null) ? $result['query'] : array_filter([
            'month' => (string) $request->input('month', (string) $request->query('month', '')),
            'company_id' => (string) $request->input('company_id', (string) $request->query('company_id', '')),
            'staff_id' => (string) $request->input('staff_id', (string) $request->query('staff_id', '')),
            'row' => (string) $request->input('row', (string) $request->query('row', '')),
        ], static fn ($v) => $v !== '');

        $redirect = redirect()->route('admin.payroll-v2.index', $query);
        if (is_string($result['error'] ?? null) && $result['error'] !== '') {
            return $redirect->with('error', $result['error']);
        }
        if (is_string($result['status'] ?? null) && $result['status'] !== '') {
            return $redirect->with('status', $result['status']);
        }

        return $redirect;
    }

    private function attendanceAggregateService(): \App\Services\Admin\Payroll\AttendanceAggregateService
    {
        /** @var \App\Services\Admin\Payroll\AttendanceAggregateService $service */
        $service = app(\App\Services\Admin\Payroll\AttendanceAggregateService::class);

        return $service;
    }

    private function runCalculationAction(Request $request, string $action): RedirectResponse
    {
        try {
            /** @var \App\Services\Admin\Payroll\PayrollCalculationService $calc */
            $calc = app(\App\Services\Admin\Payroll\PayrollCalculationService::class);
            $result = $calc->execute($request, $action);
            return $this->redirectFromActionResult($result, $request);
        } catch (\Throwable $e) {
            return $this->redirectV2($request)->with('error', $e->getMessage());
        }
    }
    private function redirectV2(Request $request): RedirectResponse
    {
        $query = array_filter([
            'month' => (string) $request->input('month', (string) $request->query('month', '')),
            'company_id' => (string) $request->input('company_id', (string) $request->query('company_id', '')),
            'staff_id' => (string) $request->input('staff_id', (string) $request->query('staff_id', '')),
        ], static fn ($v) => $v !== '');

        return redirect()->route('admin.payroll-v2.index', $query);
    }

    private function decodePayload(mixed $rawPayload): array
    {
        if (!is_string($rawPayload) || trim($rawPayload) === '') {
            return [];
        }

        $decoded = json_decode($rawPayload, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function pickFirst(array $values): mixed
    {
        foreach ($values as $v) {
            if ($v !== null && $v !== '') {
                return $v;
            }
        }

        return null;
    }

    private function fmtYen(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (is_numeric($value)) {
            return number_format((float) $value, 0);
        }

        return (string) $value;
    }

    private function buildDisplayPayloadSections(array $payload, array $allowanceOrderMap = []): array
    {
        $out = [];
        foreach (self::SECTION_KEYS as $section => $keys) {
            if ($section === 'Earnings') {
                $keys = $this->sortEarningKeys($keys, $allowanceOrderMap);
            }
            $bucket = [];
            foreach ($keys as $key) {
                $bucket[$key] = array_key_exists($key, $payload) ? $payload[$key] : 0;
            }
            $out[$section] = $bucket;
        }

        $covered = [];
        foreach ($out as $rows) {
            foreach (array_keys($rows) as $k) {
                $covered[$k] = true;
            }
        }

        $extras = [];
        foreach ($payload as $k => $v) {
            if (!is_string($k)) {
                continue;
            }
            if (!isset($covered[$k])) {
                $extras[$k] = $v;
            }
        }

        return $out;
    }

    private function sortEarningKeys(array $keys, array $allowanceOrderMap): array
    {
        $prefix = ['basic_salary', 'officer_com'];
        $suffix = array_values(array_filter($keys, static function (string $key): bool {
            return !str_starts_with($key, 'allowance_amo_')
                && $key !== 'basic_salary'
                && $key !== 'officer_com';
        }));

        $allowanceKeys = array_values(array_filter($keys, static fn (string $key): bool => str_starts_with($key, 'allowance_amo_')));
        usort($allowanceKeys, static function (string $a, string $b) use ($allowanceOrderMap): int {
            $oa = (int) ($allowanceOrderMap[$a] ?? 9999);
            $ob = (int) ($allowanceOrderMap[$b] ?? 9999);
            if ($oa === $ob) {
                return strcmp($a, $b);
            }
            return $oa <=> $ob;
        });

        return array_values(array_merge($prefix, $allowanceKeys, $suffix));
    }

    private function loadAllowanceMeta(string $companyName): array
    {
        $table = $this->firstExistingTable('sqlsrv_payroll', ['dbo.mx_allowance', 'dbo.mx_allowance']);
        if ($table === null) {
            return ['labels' => [], 'orders' => []];
        }

        $cols = $this->tableColumns('sqlsrv_payroll', $table);
        $amountCol = $this->pickColumn($cols, ['amount_column_key', 'allowance_amo_no', 'amount_key', 'allowance_key', 'slot_no']);
        $nameCol = $this->pickColumn($cols, ['allowance_name', 'name', 'allowance', 'label', 'title']);
        $orderCol = $this->pickColumn($cols, ['display_order', 'sort_order', 'No']);
        $officeCol = $this->pickColumn($cols, ['office_name', 'company_id', 'office_code']);
        if ($amountCol === null || $nameCol === null) {
            return ['labels' => [], 'orders' => []];
        }

        $baseQuery = DB::connection('sqlsrv_payroll')->table($table)
            ->select([$amountCol, $nameCol, $orderCol ? $orderCol : DB::raw('NULL as sort_order')])
            ->whereNotNull($amountCol)
            ->whereRaw('LTRIM(RTRIM(CAST(' . $this->wrap($amountCol) . " AS nvarchar(255)))) <> ''");

        $officeCandidates = $this->companyOfficeCandidates($companyName);
        $rows = collect();
        if ($officeCol !== null && $officeCandidates !== []) {
            $rows = (clone $baseQuery)
                ->whereIn(DB::raw('LTRIM(RTRIM(CAST(' . $this->wrap($officeCol) . ' AS nvarchar(255))))'), $officeCandidates)
                ->get();
        }
        if ($rows->count() === 0) {
            // Fallback: keep display usable even when company linkage is missing.
            $rows = (clone $baseQuery)->get();
        }
        $labels = [];
        $orders = [];
        foreach ($rows as $row) {
            $rawKey = trim((string) ($row->{$amountCol} ?? ''));
            $key = $this->normalizeAllowanceAmountKey($rawKey);
            if ($key === '') {
                continue;
            }
            $label = trim((string) ($row->{$nameCol} ?? $key));
            if ($label === '') {
                $label = $key;
            }
            $labels[$key] = $label;
            if ($rawKey !== '' && $rawKey !== $key) {
                $labels[$rawKey] = $label;
            }
            if ($orderCol !== null) {
                $ord = (int) ($row->{$orderCol} ?? 9999);
                $orders[$key] = $ord;
                if ($rawKey !== '' && $rawKey !== $key) {
                    $orders[$rawKey] = $ord;
                }
            }
        }

        return ['labels' => $labels, 'orders' => $orders];
    }

    private function loadAllowanceMetaForStaff(string $staffId): array
    {
        $staffId = trim($staffId);
        if ($staffId === '') {
            return ['labels' => [], 'orders' => []];
        }

        $staffTable = $this->firstExistingTable('sqlsrv', ['dbo.mx_staffs', 'dbo.mx_staffs']);
        $storeTable = $this->firstExistingTable('sqlsrv', ['dbo.mx_stores', 'dbo.mx_stores']);
        if ($staffTable === null || $storeTable === null) {
            return ['labels' => [], 'orders' => []];
        }

        $staffCols = $this->tableColumns('sqlsrv', $staffTable);
        $staffIdCol = $this->pickColumn($staffCols, ['staff_id', 'staff_code']);
        $staffStoreCol = $this->pickColumn($staffCols, ['section', 'store_code']);
        if ($staffIdCol === null || $staffStoreCol === null) {
            return ['labels' => [], 'orders' => []];
        }

        $row = DB::connection('sqlsrv')
            ->table($staffTable . ' as ms')
            ->leftJoin($storeTable . ' as st', 'ms.' . $staffStoreCol, '=', 'st.store_code')
            ->whereRaw('LTRIM(RTRIM(CAST(ms.' . $this->wrap($staffIdCol) . ' AS nvarchar(255)))) = ?', [$staffId])
            ->first(['st.company_name']);

        $companyName = trim((string) ($row->company_name ?? ''));
        return $this->loadAllowanceMeta($companyName);
    }

    private function normalizeAllowanceAmountKey(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        if (str_starts_with($raw, 'allowance_amo_')) {
            return $raw;
        }
        if (preg_match('/^\d+$/', $raw)) {
            return 'allowance_amo_' . ((int) $raw);
        }
        if (preg_match('/allowance_amo_(\d+)/i', $raw, $m)) {
            return 'allowance_amo_' . ((int) $m[1]);
        }
        return $raw;
    }

    private function companyOfficeCandidates(string $companyName): array
    {
        $out = [];
        $companyName = trim($companyName);
        if ($companyName !== '') {
            $out[] = $companyName;
        }

        $companyTable = $this->firstExistingTable('sqlsrv', ['dbo.mx_companies', 'dbo.mx_companies']);
        if ($companyTable !== null) {
            $cols = $this->tableColumns('sqlsrv', $companyTable);
            $nameCol = $this->pickColumn($cols, ['company_name', 'name']);
            $idCol = $this->pickColumn($cols, ['company_id', 'company_code', 'office_name']);
            if ($nameCol !== null && $idCol !== null && $companyName !== '') {
                $rows = DB::connection('sqlsrv')->table($companyTable)
                    ->select([$idCol])
                    ->where($nameCol, $companyName)
                    ->get();
                foreach ($rows as $r) {
                    $v = trim((string) ($r->{$idCol} ?? ''));
                    if ($v !== '') {
                        $out[] = $v;
                    }
                }
            }
        }

        return array_values(array_unique($out));
    }

    private function defaultPayloadLabels(): array
    {
        return [
            'kyuyo_sho_no' => '給与No',
            'kyuyo_staff_id' => 'スタッフID',
            'staff_name' => '氏名',
            'supply_month' => '支給日',
            'section' => '勤務店舗',
            'attendance_checked' => '勤怠確認',
            'attendance_checked_at' => '勤怠確認日時',
            'attendance_checked_by' => '勤怠確認者',
            'bonus' => '賞与',
            'bonus_amo' => '賞与額',
            'edit_lock' => '編集ロック',

            'peple_num' => '人数',
            'km' => '距離',
            'work_in_num' => '出勤日数',
            'absence_num' => '欠勤日数',
            'work_time' => '出勤時間',
            'work_time_num' => '休日出勤時間',
            'work_horiday_num' => '休日出勤日数',
            'work_kiso_num' => '勤務基礎日数',
            'overtime' => '残業時間',
            'night_over_time' => '深夜残業時間',
            'late_time' => '遅早時間',
            'horiday_true' => '有休使用日数',
            'horiday_true_num' => '有休残日数',
            'days_closed' => '休業日数',
            'time_closed' => '休業時間',

            'basic_salary' => '基本給',
            'officer_com' => '役員報酬',
            'leave_allowance' => '休業手当',
            'traffic_addition' => '非課税通勤費加算',
            'late_deduction' => '遅早控除',
            'absence_deduction' => '欠勤控除',
            'fuyo_sum' => '扶養人数',
            'taxation_sum' => '課税支給合計',
            'not_taxation_sum' => '非課税支給合計',
            'supply_sum' => '支給合計',

            'kenpo' => '健康保険料',
            'kaigo' => '介護保険料',
            'kounen' => '厚生年金保険料',
            'koyou' => '雇用保険料',
            'income_tax' => '所得税',
            'resident_tax' => '住民税',
            'rent_cost' => '社宅家賃',
            'adjustment_cost' => '調整控除',
            'syaho_sum' => '社会保険計',
            'deduction_sum' => '控除合計',
            'syaho_deduction_sum' => '社保控除後計',
            'supply_deduction_sum' => '差引支給額',
            'rouho_target_sum' => '労保対象額',
            'syaho_target_sum' => '社保対象額',

            'unpaid_amo' => '未収額',
            'kitazaike' => '北在家',
            'higashi_kakogawa' => '東加古川',
            'tsubasa_harima' => 'つばさ播磨',
            'own_cost' => '自費',
            'sakura_hari' => 'さくら鍼灸',
            'orita_hari' => '織田鍼灸',
            'miyamoto_hari' => '宮本鍼灸',
            'yokoi_hari' => '横井鍼灸',

            'kotei_sum' => '固定計',
            'yakuin_sum' => '役員計',
            'adjustment_year_end' => '年調過不足',
            'koujyo_1' => '控除1',
            'cost_liquidation' => '立替清算',
            'transfer_balance' => '振込残額',
            'company_advance_cost' => '会社立替費用',
            'not_syaho' => '社保対象外',
            'not_rouho' => '労保対象外',
            'not_supply' => '支給対象外',
            'koyou_office' => '雇用保険(会社)',
            'jidou_office' => '児童手当拠出金',
            'rousai_office' => '労災保険(会社)',
            'other_total' => 'その他合計',
        ];
    }

    private function loadStaffBaseInfoForDisplay(string $staffId): array
    {
        $staffId = trim($staffId);
        if ($staffId === '') {
            return [];
        }

        $table = $this->firstExistingTable('sqlsrv', ['dbo.mx_staffs', 'dbo.mx_staffs']);
        if ($table === null) {
            return [];
        }

        $cols = $this->tableColumns('sqlsrv', $table);
        $idCol = $this->pickColumn($cols, ['staff_id', 'staff_code']);
        if ($idCol === null) {
            return [];
        }

        $targets = ['tax_amount', 'working_time', 'year_working_time', 'traffic_day', 'yukyu', 'syaho', 'koyou', 'memo'];
        $selectCols = [];
        foreach ($targets as $key) {
            $col = $this->pickColumn($cols, [$key]);
            if ($col !== null) {
                $selectCols[$key] = $col;
            }
        }
        if ($selectCols === []) {
            return [];
        }

        $selectRawParts = [];
        foreach ($selectCols as $key => $col) {
            $selectRawParts[] = $this->wrap($col) . ' as ' . $this->wrap($key);
        }

        $row = DB::connection('sqlsrv')
            ->table($table)
            ->whereRaw('LTRIM(RTRIM(CAST(' . $this->wrap($idCol) . ' AS nvarchar(255)))) = ?', [$staffId])
            ->selectRaw(implode(', ', $selectRawParts))
            ->first();

        if ($row === null) {
            return [];
        }

        $out = [];
        foreach (array_keys($selectCols) as $key) {
            $out[$key] = $row->{$key} ?? '';
        }

        return $out;
    }

    private function firstExistingTable(string $conn, array $candidates): ?string
    {
        foreach ($candidates as $table) {
            $plain = str_replace('dbo.', '', $table);
            if (Schema::connection($conn)->hasTable($plain)) {
                return $table;
            }
        }
        return null;
    }

    private function tableColumns(string $conn, string $qualifiedTable): array
    {
        $parts = explode('.', $qualifiedTable, 2);
        $table = count($parts) === 2 ? $parts[1] : $parts[0];
        try {
            return Schema::connection($conn)->getColumnListing($table);
        } catch (\Throwable) {
            return [];
        }
    }

    private function pickColumn(array $columns, array $candidates): ?string
    {
        $set = [];
        foreach ($columns as $col) {
            $set[strtolower((string) $col)] = (string) $col;
        }
        foreach ($candidates as $cand) {
            $k = strtolower($cand);
            if (isset($set[$k])) {
                return $set[$k];
            }
        }
        return null;
    }

    private function wrap(string $column): string
    {
        return '[' . str_replace(']', ']]', $column) . ']';
    }

    private function payrollColumn(string $preferred, ?string $fallback): string
    {
        if (Schema::connection('sqlsrv_payroll')->hasColumn('mx_kyuyo_shou', $preferred)) {
            return $preferred;
        }
        if ($fallback !== null && Schema::connection('sqlsrv_payroll')->hasColumn('mx_kyuyo_shou', $fallback)) {
            return $fallback;
        }

        return $preferred;
    }

    private function staffColumn(string $preferred, string $fallback): string
    {
        if (Schema::connection('sqlsrv')->hasColumn('mx_staffs', $preferred)) {
            return $preferred;
        }
        if (Schema::connection('sqlsrv')->hasColumn('mx_staffs', $fallback)) {
            return $fallback;
        }

        return $preferred;
    }

    private function staffDivisionSelectExpr(): string
    {
        $hasDivision = Schema::connection('sqlsrv')->hasColumn('mx_staffs', 'staff_division');
        $hasEmploymentStatus = Schema::connection('sqlsrv')->hasColumn('mx_staffs', 'employment_status');

        if ($hasDivision && $hasEmploymentStatus) {
            return 'ISNULL(ms.staff_division, ms.employment_status)';
        }
        if ($hasDivision) {
            return 'ms.staff_division';
        }
        if ($hasEmploymentStatus) {
            return 'ms.employment_status';
        }

        return "''";
    }
}

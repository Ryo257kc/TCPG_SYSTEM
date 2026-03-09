<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function index(Request $request): View
    {
        $availableMonths = DB::connection('sqlsrv_payroll')
            ->table('dbo.m_payroll_entries')
            ->selectRaw('YEAR([supply_month]) as y, MONTH([supply_month]) as m')
            ->where('is_bonus', 0)
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

        $companyOptions = DB::connection('sqlsrv')
            ->table('dbo.m_stores')
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

        $targetStaffIdsFromPayroll = DB::connection('sqlsrv_payroll')
            ->table('dbo.m_payroll_entries')
            ->where('is_bonus', 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month])
            ->whereNotNull('staff_code')
            ->selectRaw('DISTINCT LTRIM(RTRIM(staff_code)) as staff_id')
            ->pluck('staff_id')
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->values()
            ->all();

        $selectedCompanyId = trim((string) $request->query('company_id', ''));

        $staffQuery = DB::connection('sqlsrv')
            ->table('dbo.m_staffs as ms')
            ->leftJoin('dbo.m_stores as st', 'ms.store_code', '=', 'st.store_code')
            ->selectRaw('LTRIM(RTRIM(ms.staff_code)) as staff_id, ms.staff_name');
        if ($targetStaffIdsFromPayroll === []) {
            $staffQuery->whereRaw('1 = 0');
        } else {
            $staffQuery->whereIn(DB::raw('LTRIM(RTRIM(ms.staff_code))'), $targetStaffIdsFromPayroll);
        }

        if ($selectedCompanyId !== '') {
            $staffQuery->where('st.company_name', $selectedCompanyId);
        }

        $staffOptions = $staffQuery
            ->whereNotNull('ms.staff_code')
            ->orderBy('ms.staff_code')
            ->get()
            ->map(fn ($row) => [
                'staff_id' => (string) $row->staff_id,
                'staff_name' => (string) ($row->staff_name ?? ''),
            ])
            ->values()
            ->all();

        $staffIdsInOptions = array_map(static fn ($row) => (string) ($row['staff_id'] ?? ''), $staffOptions);
        $missingStaffIds = array_values(array_diff($targetStaffIdsFromPayroll, $staffIdsInOptions));
        foreach ($missingStaffIds as $sid) {
            $staffOptions[] = [
                'staff_id' => (string) $sid,
                'staff_name' => '',
            ];
        }
        usort($staffOptions, static fn ($a, $b) => strcmp((string)($a['staff_id'] ?? ''), (string)($b['staff_id'] ?? '')));

        $staffNameMap = DB::connection('sqlsrv')
            ->table('dbo.m_staffs')
            ->selectRaw('LTRIM(RTRIM(staff_code)) as staff_id, staff_name')
            ->whereNotNull('staff_code')
            ->get()
            ->mapWithKeys(fn ($row) => [(string) $row->staff_id => (string) ($row->staff_name ?? '')])
            ->all();

        $hasStaffDivision = $this->staffHasColumn('staff_division');
        $staffDivisionMap = DB::connection('sqlsrv')
            ->table('dbo.m_staffs')
            ->selectRaw(
                'LTRIM(RTRIM(staff_code)) as staff_id, '
                . ($hasStaffDivision ? 'staff_division, ' : '')
                . 'employment_status'
            )
            ->whereNotNull('staff_code')
            ->get()
            ->mapWithKeys(fn ($row) => [
                (string) $row->staff_id => $hasStaffDivision && trim((string) ($row->staff_division ?? '')) !== ''
                    ? (string) $row->staff_division
                    : (string) ($row->employment_status ?? ''),
            ])
            ->all();

        $staffOrgMap = DB::connection('sqlsrv')
            ->table('dbo.m_staffs as ms')
            ->leftJoin('dbo.m_stores as st', 'ms.store_code', '=', 'st.store_code')
            ->selectRaw('LTRIM(RTRIM(ms.staff_code)) as staff_id, st.company_name, st.store_name')
            ->whereNotNull('ms.staff_code')
            ->get()
            ->mapWithKeys(fn ($row) => [
                (string) $row->staff_id => [
                    'company_name' => (string) ($row->company_name ?? ''),
                    'store_name' => (string) ($row->store_name ?? ''),
                ],
            ])
            ->all();

        $staffIdsByCompany = array_map(static fn ($row) => $row['staff_id'], $staffOptions);

        $selectedStaffId = trim((string) $request->query('staff_id', ''));
        if ($selectedCompanyId !== '' && $selectedStaffId !== '' && !in_array($selectedStaffId, $staffIdsByCompany, true)) {
            $selectedStaffId = '';
        }

        $rawRows = DB::connection('sqlsrv_payroll')
            ->table('dbo.m_payroll_entries')
            ->where('is_bonus', 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month])
            ->when($selectedStaffId !== '', fn ($q) => $q->where('staff_code', $selectedStaffId))
            ->when($selectedStaffId === '' && $selectedCompanyId !== '', function ($q) use ($staffIdsByCompany) {
                if ($staffIdsByCompany === []) {
                    $q->whereRaw('1 = 0');
                } else {
                    $q->whereIn('staff_code', $staffIdsByCompany);
                }
            })
            ->orderBy('supply_month', 'desc')
            ->orderBy('payroll_entry_id', 'desc')
            ->limit(200)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->values()
            ->all();

        $rawRowByStaff = [];
        foreach ($rawRows as $row) {
            $staffId = trim((string) ($row['staff_code'] ?? ''));
            if ($staffId === '' || isset($rawRowByStaff[$staffId])) {
                continue;
            }
            $rawRowByStaff[$staffId] = $row;
        }

        $summaryRows = [];
        $detailMap = [];

        $targetStaffRows = $selectedStaffId !== ''
            ? array_values(array_filter($staffOptions, fn ($s) => (string) ($s['staff_id'] ?? '') === $selectedStaffId))
            : $staffOptions;

        $selectedMonthDate = $selectedMonth . '-01';

        foreach ($targetStaffRows as $index => $staff) {
            $staffId = trim((string) ($staff['staff_id'] ?? ''));
            if ($staffId === '') {
                continue;
            }

            $staffName = (string) ($staff['staff_name'] ?? ($staffNameMap[$staffId] ?? ''));
            $division = $staffDivisionMap[$staffId] ?? '';
            $org = $staffOrgMap[$staffId] ?? ['company_name' => '', 'store_name' => ''];
            $row = $rawRowByStaff[$staffId] ?? null;
            $payload = $this->decodePayload($row['raw_payload'] ?? null);
            $key = sha1(json_encode([$staffId, $selectedMonthDate, $index], JSON_UNESCAPED_UNICODE));

            if ($row === null) {
                $summaryRows[] = [
                    'key' => $key,
                    'staff_id' => $staffId,
                    'staff_name' => $staffName,
                    'supply_month' => $this->formatDate($selectedMonthDate),
                    'division' => $division,
                    'company_name' => (string) ($org['company_name'] ?? ''),
                    'store_name' => (string) ($org['store_name'] ?? ''),
                    'pay_total' => '',
                    'deduction_total' => '',
                    'net_pay' => '',
                    'edit_lock' => '',
                ];

                $detailMap[$key] = [
                    ['name' => 'staff_id', 'value' => $staffId],
                    ['name' => 'staff_name', 'value' => $staffName],
                    ['name' => 'target_month', 'value' => $this->formatDate($selectedMonthDate)],
                    ['name' => 'employment_type', 'value' => '給与データなし'],
                ];
                continue;
            }

            $payTotal = $this->firstNonNull([
                $row['payment_total'] ?? null,
                $payload['payment_total'] ?? null,
                $payload['sikyu_total'] ?? null,
                $payload['pay_total'] ?? null,
                $payload['supply_sum'] ?? null,
            ]);
            $deductTotal = $this->firstNonNull([
                $payload['deduction_total'] ?? null,
                $payload['koujo_total'] ?? null,
                $payload['deduction_sum'] ?? null,
                $row['salary_total'] ?? null,
            ]);
            $netPay = $this->firstNonNull([
                $row['transfer_amount'] ?? null,
                $payload['transfer_amo'] ?? null,
                $payload['supply_deduction_sum'] ?? null,
            ]);

            $summaryRows[] = [
                'key' => $key,
                'staff_id' => $staffId,
                'staff_name' => $staffName,
                'supply_month' => $this->formatDate((string) ($row['supply_month'] ?? $selectedMonthDate)),
                'division' => $division,
                'company_name' => (string) ($org['company_name'] ?? ''),
                'store_name' => (string) ($org['store_name'] ?? ''),
                'pay_total' => $this->formatAmount($payTotal),
                'deduction_total' => $this->formatAmount($deductTotal),
                'net_pay' => $this->formatAmount($netPay),
                'edit_lock' => ((int) ($row['is_edit_locked'] ?? 0)) === 1 ? '1' : '0',
                'attendance_checked' => ((int) ($payload['attendance_checked'] ?? 0)) === 1 ? '1' : '0',
                'attendance_synced_at' => (string) ($payload['attendance_synced_at'] ?? ''),
                'attendance_synced_by' => (string) ($payload['attendance_synced_by'] ?? ''),
            ];

            $details = [];
            foreach ($row as $col => $val) {
                if ($col === 'raw_payload') {
                    continue;
                }
                $details[] = ['name' => (string) $col, 'value' => $this->formatCell((string) $col, $val)];
            }
            foreach ($payload as $col => $val) {
                $details[] = ['name' => 'payload.' . (string) $col, 'value' => $this->formatCell((string) $col, $val)];
            }
            $details[] = ['name' => 'staff_name', 'value' => $staffName];
            $detailMap[$key] = $details;
        }

        $selectedRowKey = (string) $request->query('row', ($summaryRows[0]['key'] ?? ''));
        if (!isset($detailMap[$selectedRowKey]) && $summaryRows !== []) {
            $selectedRowKey = $summaryRows[0]['key'];
        }
        $selectedSummary = null;
        foreach ($summaryRows as $row) {
            if (($row['key'] ?? '') === $selectedRowKey) {
                $selectedSummary = $row;
                break;
            }
        }

        $attendanceCheckedCount = count(array_filter($summaryRows, fn ($r) => (string) ($r['attendance_checked'] ?? '0') === '1'));
        $attendanceUncheckedCount = max(count($summaryRows) - $attendanceCheckedCount, 0);
        $allowanceDefinitions = [];
        $selectedStaffMeta = [];
        $selectedMasterInfo = [];
        $attendancePreview = [];
        $previousPayrollPreview = [];
        if (is_array($selectedSummary) && !empty($selectedSummary['staff_id'])) {
            $selectedStaffIdForMeta = (string) $selectedSummary['staff_id'];
            $companyCode = $this->resolveStaffCompanyCode($selectedStaffIdForMeta);
            $allowanceDefinitions = $this->resolveAllowanceDefinitionsBySlot($companyCode);
            $selectedStaffMeta = $this->resolveLegacyStaffMeta($selectedStaffIdForMeta);
            $selectedMasterInfo = $this->resolvePayrollMasterInfo(
                $selectedStaffIdForMeta,
                $selectedMonthDate,
                $companyCode
            );
            $attendancePreview = $this->buildAttendanceAggregatePreview(
                $selectedStaffIdForMeta,
                $selectedMonthDate
            );
            $previousPayrollPreview = $this->buildPreviousPayrollPreview(
                $selectedStaffIdForMeta,
                $selectedMonthDate
            );
        }

        return view('admin.payroll.index', [
            'availableMonths' => $availableMonths,
            'selectedMonth' => $selectedMonth,
            'companyOptions' => $companyOptions,
            'selectedCompanyId' => $selectedCompanyId,
            'staffOptions' => $staffOptions,
            'selectedStaffId' => $selectedStaffId,
            'summaryRows' => $summaryRows,
            'selectedRowKey' => $selectedRowKey,
            'attendanceCheckedCount' => $attendanceCheckedCount,
            'attendanceUncheckedCount' => $attendanceUncheckedCount,
            'selectedSummary' => $selectedSummary,
            'selectedDetails' => $detailMap[$selectedRowKey] ?? [],
            'allowanceDefinitions' => $allowanceDefinitions,
            'selectedStaffMeta' => $selectedStaffMeta,
            'selectedMasterInfo' => $selectedMasterInfo,
            'attendancePreview' => $attendancePreview,
            'previousPayrollPreview' => $previousPayrollPreview,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $monthParam = trim((string) $request->query('month', now('Asia/Tokyo')->format('Y-m')));
        if (!preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
            $monthParam = now('Asia/Tokyo')->format('Y-m');
        }
        [$year, $month] = [(int) substr($monthParam, 0, 4), (int) substr($monthParam, 5, 2)];

        $selectedStaffId = trim((string) $request->query('staff_id', ''));
        $selectedCompanyId = trim((string) $request->query('company_id', ''));

        $entryQuery = DB::connection('sqlsrv_payroll')
            ->table('dbo.m_payroll_entries')
            ->where('is_bonus', 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month]);

        if ($selectedStaffId !== '') {
            $entryQuery->whereRaw('LTRIM(RTRIM(staff_code)) = ?', [$selectedStaffId]);
        } elseif ($selectedCompanyId !== '') {
            $staffIds = DB::connection('sqlsrv')
                ->table('dbo.m_staffs as ms')
                ->leftJoin('dbo.m_stores as st', 'ms.store_code', '=', 'st.store_code')
                ->where('st.company_name', $selectedCompanyId)
                ->whereNotNull('ms.staff_code')
                ->selectRaw('DISTINCT LTRIM(RTRIM(ms.staff_code)) as staff_id')
                ->pluck('staff_id')
                ->map(fn ($v) => trim((string) $v))
                ->filter(fn ($v) => $v !== '')
                ->values()
                ->all();

            if ($staffIds === []) {
                $entryQuery->whereRaw('1 = 0');
            } else {
                $entryQuery->whereIn(DB::raw('LTRIM(RTRIM(staff_code))'), $staffIds);
            }
        }

        $rows = $entryQuery
            ->orderByRaw('LTRIM(RTRIM(staff_code)) ASC')
            ->orderBy('supply_month', 'desc')
            ->get([
                'staff_code',
                'supply_month',
                'is_edit_locked',
                'payment_total',
                'salary_total',
                'transfer_amount',
                'raw_payload',
            ]);

        $staffNameMap = DB::connection('sqlsrv')
            ->table('dbo.m_staffs')
            ->whereNotNull('staff_code')
            ->selectRaw('LTRIM(RTRIM(staff_code)) as staff_id, staff_name')
            ->get()
            ->mapWithKeys(fn ($row) => [(string) $row->staff_id => (string) ($row->staff_name ?? '')])
            ->all();

        $filename = 'payroll_' . $monthParam . '_' . now('Asia/Tokyo')->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows, $staffNameMap) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'month',
                'staff_id',
                'staff_name',
                'supply_month',
                'pay_total',
                'deduction_total',
                'net_pay',
                'edit_lock',
                'attendance_checked',
            ]);

            foreach ($rows as $row) {
                $staffId = trim((string) ($row->staff_code ?? ''));
                $payload = $this->decodePayload($row->raw_payload ?? null);

                $payTotal = $this->firstNonNull([
                    $row->payment_total ?? null,
                    $payload['payment_total'] ?? null,
                    $payload['sikyu_total'] ?? null,
                    $payload['pay_total'] ?? null,
                    $payload['supply_sum'] ?? null,
                ]);
                $deductionTotal = $this->firstNonNull([
                    $payload['deduction_total'] ?? null,
                    $payload['koujo_total'] ?? null,
                    $payload['deduction_sum'] ?? null,
                    $row->salary_total ?? null,
                ]);
                $netPay = $this->firstNonNull([
                    $row->transfer_amount ?? null,
                    $payload['transfer_amo'] ?? null,
                    $payload['supply_deduction_sum'] ?? null,
                ]);

                fputcsv($out, [
                    $row->supply_month ? date('Y-m', strtotime((string) $row->supply_month)) : '',
                    $staffId,
                    (string) ($staffNameMap[$staffId] ?? ''),
                    $row->supply_month ? date('Y-m-d', strtotime((string) $row->supply_month)) : '',
                    $this->formatAmount($payTotal),
                    $this->formatAmount($deductionTotal),
                    $this->formatAmount($netPay),
                    ((int) ($row->is_edit_locked ?? 0)) === 1 ? '1' : '0',
                    ((int) ($payload['attendance_checked'] ?? 0)) === 1 ? '1' : '0',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function buildPreviousPayrollPreview(string $staffId, string $selectedMonthDate): array
    {
        $staffId = trim($staffId);
        if ($staffId === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedMonthDate)) {
            return [];
        }

        $prevMonthDate = date('Y-m-01', strtotime($selectedMonthDate . ' -1 month'));
        $year = (int) date('Y', strtotime($prevMonthDate));
        $month = (int) date('n', strtotime($prevMonthDate));

        $entry = DB::connection('sqlsrv_payroll')
            ->table('dbo.m_payroll_entries')
            ->where('is_bonus', 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month])
            ->whereRaw('LTRIM(RTRIM(staff_code)) = ?', [$staffId])
            ->orderBy('supply_month', 'desc')
            ->orderBy('payroll_entry_id', 'desc')
            ->first(['raw_payload']);

        if ($entry === null || !is_string($entry->raw_payload ?? null)) {
            return [];
        }

        $decoded = json_decode((string) $entry->raw_payload, true);
        if (!is_array($decoded)) {
            return [];
        }

        $out = [];
        foreach ($decoded as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            if (is_numeric($value)) {
                $out[$key] = (float) $value;
                continue;
            }
            if (is_string($value)) {
                $num = str_replace(',', '', trim($value));
                if ($num !== '' && is_numeric($num)) {
                    $out[$key] = (float) $num;
                }
            }
        }

        return $out;
    }

    public function lock(Request $request): RedirectResponse
    {
        return $this->toggleEditLock($request, true);
    }

    public function unlock(Request $request): RedirectResponse
    {
        return $this->toggleEditLock($request, false);
    }

    public function toggleLock(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_lock' => ['required', 'in:0,1'],
        ]);

        $nextLock = ((string) $validated['current_lock']) !== '1';

        return $this->toggleEditLock($request, $nextLock);
    }

    public function save(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'target_staff_id' => ['required', 'string', 'max:50'],
            'staff_id' => ['nullable', 'string', 'max:50'],
            'company_id' => ['nullable', 'string', 'max:200'],
            'row' => ['nullable', 'string', 'max:100'],
            'fields' => ['nullable', 'array'],
        ]);

        [$year, $month] = [(int) substr((string) $validated['month'], 0, 4), (int) substr((string) $validated['month'], 5, 2)];
        $staffId = trim((string) $validated['target_staff_id']);

        $entry = DB::connection('sqlsrv_payroll')
            ->table('dbo.m_payroll_entries')
            ->where('is_bonus', 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month])
            ->whereRaw('LTRIM(RTRIM(staff_code)) = ?', [$staffId])
            ->orderBy('supply_month', 'desc')
            ->orderBy('payroll_entry_id', 'desc')
            ->first();

        if ($entry === null) {
            return $this->redirectPayrollWithQuery($validated, '対象データが見つかりません。');
        }
        if (((int) ($entry->is_edit_locked ?? 0)) === 1) {
            return $this->redirectPayrollWithQuery($validated, '給与が確定済みのため、先に未確定へ戻してください。');
        }

        $payload = [];
        if (is_string($entry->raw_payload ?? null) && trim((string) $entry->raw_payload) !== '') {
            $decoded = json_decode((string) $entry->raw_payload, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        if (((int) ($payload['attendance_checked'] ?? 0)) !== 1) {
            return $this->redirectPayrollWithQuery($validated, '勤怠未確定のため保存できません。');
        }

        $fields = is_array($validated['fields'] ?? null) ? $validated['fields'] : [];
        if ($fields === []) {
            return $this->redirectPayrollWithQuery($validated, '保存対象の項目がありません。');
        }

        foreach ($fields as $k => $v) {
            if (!is_string($k)) {
                continue;
            }
            $payload[$k] = $this->normalizeNumericInput($v);
        }

        $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if (!is_string($encodedPayload)) {
            return $this->redirectPayrollWithQuery($validated, '保存に失敗しました。データ形式を確認してください。');
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.m_payroll_entries')
            ->where('payroll_entry_id', (int) $entry->payroll_entry_id)
            ->update([
                'raw_payload' => $encodedPayload,
                'updated_at' => now('Asia/Tokyo'),
            ]);

        return $this->redirectPayrollWithQuery($validated, '給与項目を保存しました。');
    }

    public function syncAttendance(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'staff_id' => ['required', 'string', 'max:50'],
            'company_id' => ['nullable', 'string', 'max:200'],
        ]);

        [$year, $month] = [(int) substr((string) $validated['month'], 0, 4), (int) substr((string) $validated['month'], 5, 2)];
        $staffId = trim((string) $validated['staff_id']);

        $entry = DB::connection('sqlsrv_payroll')
            ->table('dbo.m_payroll_entries')
            ->where('is_bonus', 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month])
            ->whereRaw('LTRIM(RTRIM(staff_code)) = ?', [$staffId])
            ->first();

        if ($entry === null) {
            return $this->redirectPayrollWithQuery($validated, '対象データが見つかりません。');
        }
        if ((int) ($entry->is_edit_locked ?? 0) === 1) {
            return $this->redirectPayrollWithQuery($validated, '給与が確定済みのため、先に未確定へ戻してください。');
        }

        $preview = $this->buildAttendanceAggregatePreview($staffId, sprintf('%04d-%02d-01', $year, $month));
        if ($preview === []) {
            return $this->redirectPayrollWithQuery($validated, '反映対象の勤怠データがありません。');
        }
        $payload = $this->decodePayload($entry->raw_payload ?? null);

        $updatedKeys = [];
        foreach ($preview as $key => $value) {
            $payload[$key] = $value;
            $updatedKeys[] = $key;
        }

        $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if (!is_string($encodedPayload)) {
            return $this->redirectPayrollWithQuery($validated, '勤怠反映に失敗しました。データ形式を確認してください。');
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.m_payroll_entries')
            ->where('payroll_entry_id', (int) $entry->payroll_entry_id)
            ->update([
                'raw_payload' => $encodedPayload,
                'updated_at' => now('Asia/Tokyo'),
            ]);

        return $this->redirectPayrollWithQuery($validated, '勤怠を反映しました（更新項目: ' . count($updatedKeys) . '件）。');
    }

    public function syncAttendanceBulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'staff_id' => ['nullable', 'string', 'max:50'],
            'company_id' => ['nullable', 'string', 'max:200'],
        ]);

        [$year, $month] = [(int) substr((string) $validated['month'], 0, 4), (int) substr((string) $validated['month'], 5, 2)];
        $selectedStaffId = trim((string) ($validated['staff_id'] ?? ''));
        $selectedCompanyId = trim((string) ($validated['company_id'] ?? ''));
        $payrollMonthDate = sprintf('%04d-%02d-01', $year, $month);

        $entryQuery = DB::connection('sqlsrv_payroll')
            ->table('dbo.m_payroll_entries')
            ->where('is_bonus', 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month]);

        if ($selectedStaffId !== '') {
            $entryQuery->whereRaw('LTRIM(RTRIM(staff_code)) = ?', [$selectedStaffId]);
        } elseif ($selectedCompanyId !== '') {
            $staffIds = DB::connection('sqlsrv')
                ->table('dbo.m_staffs as ms')
                ->leftJoin('dbo.m_stores as st', 'ms.store_code', '=', 'st.store_code')
                ->where('st.company_name', $selectedCompanyId)
                ->whereNotNull('ms.staff_code')
                ->selectRaw('DISTINCT LTRIM(RTRIM(ms.staff_code)) as staff_id')
                ->pluck('staff_id')
                ->map(fn ($v) => trim((string) $v))
                ->filter(fn ($v) => $v !== '')
                ->values()
                ->all();

            if ($staffIds === []) {
                return $this->redirectPayrollWithQuery($validated, '選択した会社に対象スタッフがいません。');
            }

            $entryQuery->whereIn(DB::raw('LTRIM(RTRIM(staff_code))'), $staffIds);
        }

        $entries = $entryQuery
            ->orderByRaw('LTRIM(RTRIM(staff_code)) ASC')
            ->get(['payroll_entry_id', 'staff_code', 'raw_payload', 'is_edit_locked']);

        if ($entries->isEmpty()) {
            return $this->redirectPayrollWithQuery($validated, '選択月の給与データが見つかりません。');
        }

        foreach ($entries as $entry) {
            $payload = $this->decodePayload($entry->raw_payload ?? null);
            if (false && ((int) ($payload['attendance_checked'] ?? 0)) !== 1) {
                return $this->redirectPayrollWithQuery($validated, '勤怠未確定のスタッフがいます。先に勤怠を全員確定してください。');
            }
        }

        $updatedStaff = 0;
        $updatedFields = 0;
        $skippedLocked = 0;

        foreach ($entries as $entry) {
            if ((int) ($entry->is_edit_locked ?? 0) === 1) {
                $skippedLocked++;
                continue;
            }

            $staffId = trim((string) ($entry->staff_code ?? ''));
            if ($staffId === '') {
                continue;
            }

            $preview = $this->buildAttendanceAggregatePreview($staffId, $payrollMonthDate);
            if ($preview === []) {
                continue;
            }
            $payload = $this->decodePayload($entry->raw_payload ?? null);
            if (((int) ($payload['attendance_checked'] ?? 0)) !== 1) {
                continue;
            }

            foreach ($preview as $key => $value) {
                $payload[$key] = $value;
                $updatedFields++;
            }

            $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            if (!is_string($encodedPayload)) {
                continue;
            }

            DB::connection('sqlsrv_payroll')
                ->table('dbo.m_payroll_entries')
                ->where('payroll_entry_id', (int) $entry->payroll_entry_id)
                ->update([
                    'raw_payload' => $encodedPayload,
                    'updated_at' => now('Asia/Tokyo'),
                ]);

            $updatedStaff++;
        }

        return $this->redirectPayrollWithQuery(
            $validated,
            '勤怠を一括反映しました（対象: ' . $updatedStaff . '名 / 更新項目: ' . $updatedFields . '件）'
            . ($skippedLocked > 0 ? ' ※確定済みスキップ: ' . $skippedLocked . '名' : '')
        );
    }
    private function toggleEditLock(Request $request, bool $lock): RedirectResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'target_staff_id' => ['nullable', 'string', 'max:50'],
            'staff_id' => ['nullable', 'string', 'max:50'],
            'company_id' => ['nullable', 'string', 'max:200'],
            'row' => ['nullable', 'string', 'max:100'],
        ]);

        [$year, $month] = [(int) substr((string) $validated['month'], 0, 4), (int) substr((string) $validated['month'], 5, 2)];
        $targetStaffId = trim((string) ($validated['target_staff_id'] ?? ''));
        if ($targetStaffId === '') {
            $targetStaffId = trim((string) ($validated['staff_id'] ?? ''));
        }

        if ($targetStaffId !== '') {
            DB::connection('sqlsrv_payroll')
                ->table('dbo.m_payroll_entries')
                ->where('is_bonus', 0)
                ->whereRaw('YEAR([supply_month]) = ?', [$year])
                ->whereRaw('MONTH([supply_month]) = ?', [$month])
                ->whereRaw('LTRIM(RTRIM(staff_code)) = ?', [$targetStaffId])
                ->update(['is_edit_locked' => $lock ? 1 : 0]);
        }

        $query = [
            'month' => (string) $validated['month'],
        ];
        $staffFilter = trim((string) ($validated['staff_id'] ?? ''));
        if ($staffFilter !== '') {
            $query['staff_id'] = $staffFilter;
        }
        if (!empty($validated['company_id'])) {
            $query['company_id'] = (string) $validated['company_id'];
        }
        if (!empty($validated['row'])) {
            $query['row'] = (string) $validated['row'];
        }

        return redirect()
            ->route('admin.payroll.index', $query)
            ->with('status', $lock ? '給与を確定しました。' : '給与を未確定に戻しました。');
    }

    private function decodePayload(mixed $payload): array
    {
        if (!is_string($payload) || trim($payload) === '') {
            return [];
        }
        $decoded = json_decode($payload, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function firstNonNull(array $values): mixed
    {
        foreach ($values as $value) {
            if ($value !== null && $value !== '') {
                return $value;
            }
        }
        return null;
    }

    private function formatDate(string $value): string
    {
        if ($value === '') {
            return '';
        }
        $ts = strtotime($value);
        return $ts !== false ? date('Y/m/d', $ts) : $value;
    }

    private function formatAmount(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $str = str_replace(',', '', (string) $value);
        if (!preg_match('/^-?\d+(\.\d+)?$/', $str)) {
            return (string) $value;
        }
        return number_format((float) $str);
    }

    private function formatCell(string $column, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $text = (string) $value;
        $lc = mb_strtolower($column);
        if (
            str_contains($lc, 'date') ||
            str_contains($lc, 'month') ||
            (str_contains($lc, 'day') && !str_contains($lc, 'horiday')) ||
            preg_match('/^\d{4}-\d{2}-\d{2}/', $text)
        ) {
            return $this->formatDate($text);
        }
        if (
            preg_match('/^-?\d+(\.\d+)?$/', str_replace(',', '', $text)) &&
            (str_contains($lc, 'amo') || str_contains($lc, 'amount') || str_contains($lc, 'total') || str_contains($lc, 'tax') || str_contains($lc, 'pay') || str_contains($lc, 'deduct') || str_contains($lc, 'bonus') || str_contains($lc, 'salary'))
        ) {
            return $this->formatAmount($text);
        }
        return $text;
    }

    private function buildAttendanceAggregatePreview(string $staffId, string $selectedMonthDate): array
    {
        if ($staffId === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedMonthDate)) {
            return [];
        }

        $attendanceMonthDate = date('Y-m-01', strtotime($selectedMonthDate . ' -1 month'));
        $fromDate = $attendanceMonthDate;
        $toDate = date('Y-m-t', strtotime($fromDate));

        $timeCardTable = $this->resolveTimeCardTable();
        if ($timeCardTable === null) {
            return [];
        }

        $columns = $this->tableColumns('sqlsrv', $timeCardTable);
        $staffCol = $this->pickColumn($columns, ['staff_id', 'staff_code', 'staff_name']);
        $dateCol = $this->pickColumn($columns, ['work_date', 'date']);
        if ($staffCol === null || $dateCol === null) {
            return [];
        }

        $actualStartCol = $this->pickColumn($columns, ['actual_start']);
        $shiftStartCol = $this->pickColumn($columns, ['shift_start']);
        $changeStartCol = $this->pickColumn($columns, ['change_start']);

        $absenceCol = $this->pickColumn($columns, ['absence_num', 'absence_days']);
        $holidayDaysCol = $this->pickColumn($columns, ['work_horiday_num', 'work_holiday_num']);
        $workTimeCol = $this->pickColumn($columns, ['work_time']);
        $overtimeCol = $this->pickColumn($columns, ['overtime']);
        $nightOvertimeCol = $this->pickColumn($columns, ['night_over_time']);
        $paidLeaveUsedCol = $this->pickColumn($columns, ['paid_leave_used', 'horiday_true']);
        $paidLeaveRemainCol = $this->pickColumn($columns, ['paid_leave_balance', 'horiday_true_num']);
        $closedDaysCol = $this->pickColumn($columns, ['days_closed']);
        $closedTimeCol = $this->pickColumn($columns, ['time_closed']);

        $selectCols = [$staffCol, $dateCol];
        foreach ([
            $actualStartCol,
            $shiftStartCol,
            $changeStartCol,
            $absenceCol,
            $holidayDaysCol,
            $workTimeCol,
            $overtimeCol,
            $nightOvertimeCol,
            $paidLeaveUsedCol,
            $paidLeaveRemainCol,
            $closedDaysCol,
            $closedTimeCol,
        ] as $c) {
            if ($c !== null && !in_array($c, $selectCols, true)) {
                $selectCols[] = $c;
            }
        }

        $rows = DB::connection('sqlsrv')
            ->table($timeCardTable)
            ->whereRaw('LTRIM(RTRIM(' . $this->wrap($staffCol) . ')) = ?', [$staffId])
            ->whereRaw('CONVERT(date, ' . $this->wrap($dateCol) . ') between ? and ?', [$fromDate, $toDate])
            ->get($selectCols);

        if ($rows->isEmpty()) {
            return [];
        }

        $attendanceDays = 0.0;
        $absenceDays = 0.0;
        $holidayWorkDays = 0.0;
        $workTime = 0.0;
        $overtime = 0.0;
        $nightOvertime = 0.0;
        $paidLeaveUsed = 0.0;
        $paidLeaveRemain = null;
        $closedDays = 0.0;
        $closedTime = 0.0;

        foreach ($rows as $row) {
            $rowArray = (array) $row;

            $hasAttendance = false;
            foreach ([$actualStartCol, $shiftStartCol, $changeStartCol] as $attCol) {
                if ($attCol !== null && !empty($rowArray[$attCol])) {
                    $hasAttendance = true;
                    break;
                }
            }
            if ($hasAttendance) {
                $attendanceDays += 1;
            }

            $absenceDays += (float) ($absenceCol !== null ? ($rowArray[$absenceCol] ?? 0) : 0);
            $holidayWorkDays += (float) ($holidayDaysCol !== null ? ($rowArray[$holidayDaysCol] ?? 0) : 0);
            $workTime += (float) ($workTimeCol !== null ? ($rowArray[$workTimeCol] ?? 0) : 0);
            $overtime += (float) ($overtimeCol !== null ? ($rowArray[$overtimeCol] ?? 0) : 0);
            $nightOvertime += (float) ($nightOvertimeCol !== null ? ($rowArray[$nightOvertimeCol] ?? 0) : 0);
            $paidLeaveUsed += (float) ($paidLeaveUsedCol !== null ? ($rowArray[$paidLeaveUsedCol] ?? 0) : 0);
            $closedDays += (float) ($closedDaysCol !== null ? ($rowArray[$closedDaysCol] ?? 0) : 0);
            $closedTime += (float) ($closedTimeCol !== null ? ($rowArray[$closedTimeCol] ?? 0) : 0);

            if ($paidLeaveRemainCol !== null && isset($rowArray[$paidLeaveRemainCol]) && $rowArray[$paidLeaveRemainCol] !== null && $rowArray[$paidLeaveRemainCol] !== '') {
                $paidLeaveRemain = (float) $rowArray[$paidLeaveRemainCol];
            }
        }

        return [
            'work_in_num' => $attendanceDays,
            'absence_num' => $absenceDays,
            'work_horiday_num' => $holidayWorkDays,
            'work_time' => $workTime,
            'overtime' => $overtime,
            'night_over_time' => $nightOvertime,
            'horiday_true' => $paidLeaveUsed,
            'days_closed' => $closedDays,
            'time_closed' => $closedTime,
            'attendance_checked_month' => $fromDate,
        ] + ($paidLeaveRemain !== null ? ['horiday_true_num' => $paidLeaveRemain] : []);
    }

    private function resolveAllowanceDefinitionsOrdered(?string $companyCode): array
    {
        $companyCode = trim((string) ($companyCode ?? ''));
        if ($companyCode === '') {
            return [];
        }

        try {
            $columns = $this->tableColumns('sqlsrv_payroll', 'dbo.t_allowance');
            if ($columns === []) {
                return [];
            }

            $hasSlotNo = in_array('slot_no', $columns, true);
            $hasAmountKey = in_array('amount_column_key', $columns, true);
            $hasDisplayOrder = in_array('display_order', $columns, true);

            $rows = DB::connection('sqlsrv_payroll')
                ->table('dbo.t_allowance')
                ->whereRaw('LTRIM(RTRIM(office_name)) = ?', [$companyCode])
                ->whereNotNull('allowance_name')
                ->whereRaw("LTRIM(RTRIM(allowance_name)) <> ''")
                ->orderByRaw($hasDisplayOrder ? 'display_order asc, allowance_no asc' : 'allowance_no asc')
                ->get([
                    'allowance_no',
                    'allowance_name',
                    DB::raw($hasSlotNo ? 'slot_no' : 'NULL as slot_no'),
                    DB::raw($hasAmountKey ? 'amount_column_key' : 'NULL as amount_column_key'),
                    DB::raw($hasDisplayOrder ? 'display_order' : 'NULL as display_order'),
                ]);

            $defs = [];
            foreach ($rows as $row) {
                $slotNo = (int) ($row->slot_no ?? 0);
                $amountKey = trim((string) ($row->amount_column_key ?? ''));
                if ($slotNo <= 0 && $amountKey === '') {
                    continue;
                }

                $defs[] = [
                    'allowance_no' => (int) ($row->allowance_no ?? 0),
                    'slot_no' => $slotNo,
                    'allowance_name' => trim((string) ($row->allowance_name ?? '')),
                    'amount_column_key' => $amountKey,
                    'display_order' => (int) ($row->display_order ?? 0),
                ];
            }

            return $defs;
        } catch (\Throwable) {
            return [];
        }
    }

    private function resolveAllowanceDefinitionsBySlot(?string $companyCode): array
    {
        $rows = $this->resolveAllowanceDefinitionsOrdered($companyCode);
        if ($rows === []) {
            return [];
        }

        usort($rows, static function (array $a, array $b): int {
            $aOrder = (int) ($a['display_order'] ?? 0);
            $bOrder = (int) ($b['display_order'] ?? 0);
            if ($aOrder !== 0 || $bOrder !== 0) {
                if ($aOrder === 0) {
                    $aOrder = 9999;
                }
                if ($bOrder === 0) {
                    $bOrder = 9999;
                }
                if ($aOrder !== $bOrder) {
                    return $aOrder <=> $bOrder;
                }
            }

            $aSlot = (int) ($a['slot_no'] ?? 0);
            $bSlot = (int) ($b['slot_no'] ?? 0);
            if ($aSlot !== $bSlot) {
                return $aSlot <=> $bSlot;
            }

            return ((int) ($a['allowance_no'] ?? 0)) <=> ((int) ($b['allowance_no'] ?? 0));
        });

        return $rows;
    }

    private function resolveStaffCompanyCode(string $staffId): string
    {
        $staffId = trim($staffId);
        if ($staffId === '') {
            return '';
        }

        try {
            $row = DB::connection('sqlsrv')
                ->table('dbo.m_staffs as ms')
                ->leftJoin('dbo.m_stores as st', 'ms.store_code', '=', 'st.store_code')
                ->leftJoin('dbo.m_companies as mc', 'st.company_name', '=', 'mc.company_name')
                ->whereRaw('LTRIM(RTRIM(ms.staff_code)) = ?', [$staffId])
                ->first([
                    DB::raw('mc.company_code as mc_company_code'),
                    DB::raw('mc.company_id as mc_company_id'),
                    DB::raw('st.company_name as st_company_name'),
                ]);

            if ($row === null) {
                return '';
            }

            foreach ([
                'mc_company_code',
                'mc_company_id',
                'st_company_name',
            ] as $col) {
                $value = trim((string) ($row->{$col} ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        } catch (\Throwable) {
            return '';
        }

        return '';
    }

    private function resolveLegacyStaffMeta(string $staffId): array
    {
        $staffId = trim($staffId);
        if ($staffId === '') {
            return [];
        }

        try {
            $row = DB::connection('sqlsrv')
                ->table('dbo.staff')
                ->whereRaw('LTRIM(RTRIM(staff_id)) = ?', [$staffId])
                ->first([
                    'tax_amount',
                    'traffic_day',
                    'working_time',
                    'year_working_time',
                ]);

            if ($row === null) {
                return [];
            }

            return [
                'tax_amount' => $this->formatCell('tax_amount', $row->tax_amount ?? null),
                // traffic_day is "daily commute amount", not a date.
                'traffic_day' => $this->formatAmountOrRaw($row->traffic_day ?? null),
                'working_time' => $this->formatAmountOrRaw($row->working_time ?? null),
                'year_working_time' => $this->formatFixed2OrRaw($row->year_working_time ?? null),
            ];
        } catch (\Throwable) {
            return [];
        }
    }

    private function resolvePayrollMasterInfo(string $staffId, string $selectedMonthDate, string $companyCode): array
    {
        $monthNo = (int) date('n', strtotime($selectedMonthDate));
        // Resident tax slot starts in June:
        // June=1 ... Dec=7, Jan=8 ... May=12
        $residentSlot = $monthNo >= 6 ? ($monthNo - 5) : ($monthNo + 7);
        $residentColumn = 'resident_tax' . $residentSlot;
        // Rule: records decided/revised in month M are reflected from month M+1 payroll.
        // So for selected payroll month, use records with effective date < first day of selected month.
        $cutoffDate = $selectedMonthDate;

        $result = [
            'kihon' => [],
            'syaho' => [],
            'resident' => [],
        ];

        try {
            $kihon = DB::connection('sqlsrv_payroll')
                ->table('dbo.t_kihon')
                ->whereRaw('LTRIM(RTRIM(staff_id)) = ?', [$staffId])
                ->whereDate('decision_date', '<', $cutoffDate)
                ->orderBy('decision_date', 'desc')
                ->orderBy('kihon_no', 'desc')
                ->first();

            if ($kihon !== null) {
                $result['kihon'] = [
                    'decision_date' => $this->formatDate((string) ($kihon->decision_date ?? '')),
                    'monthly_salary' => $this->formatAmountOrRaw($kihon->monthly_salary ?? null),
                    'executive_remu' => $this->formatAmountOrRaw($kihon->executive_remu ?? null),
                    'hourly_salary' => $this->formatAmountOrRaw($kihon->hourly_salary ?? null),
                    'hourly_pay' => $this->formatAmountOrRaw($kihon->hourly_pay ?? null),
                    'position_allow' => $this->formatAmountOrRaw($kihon->position_allow ?? null),
                    'duties_allow' => $this->formatAmountOrRaw($kihon->duties_allow ?? null),
                    'qualification_allow' => $this->formatAmountOrRaw($kihon->qualification_allow ?? null),
                    'claim_allow' => $this->formatAmountOrRaw($kihon->claim_allow ?? null),
                    'traffic_pay' => $this->formatAmountOrRaw($kihon->traffic_pay ?? null),
                    'adjustment_add' => $this->formatAmountOrRaw($kihon->adjustment_add ?? null),
                    'rent_subsidies' => $this->formatAmountOrRaw($kihon->rent_subsidies ?? null),
                    'rent_pay' => $this->formatAmountOrRaw($kihon->rent_pay ?? null),
                    'adjustment_pay' => $this->formatAmountOrRaw($kihon->adjustment_pay ?? null),
                    'fixed_overtime' => $this->formatAmountOrRaw($kihon->fixed_overtime ?? null),
                ];
            }
        } catch (\Throwable) {
            $result['kihon'] = [];
        }

        try {
            $resident = DB::connection('sqlsrv_payroll')
                ->table('dbo.t_resident')
                ->whereRaw('LTRIM(RTRIM(staff_id)) = ?', [$staffId])
                ->whereDate('target_month', '<=', $selectedMonthDate)
                ->orderBy('target_month', 'desc')
                ->orderBy('resident_no', 'desc')
                ->first();

            if ($resident !== null) {
                $residentTax = property_exists($resident, $residentColumn) ? ($resident->{$residentColumn} ?? null) : null;
                $result['resident'] = [
                    'target_month' => $this->formatDate((string) ($resident->target_month ?? '')),
                    'resident_tax_month' => $this->formatAmountOrRaw($residentTax),
                ];
            }
        } catch (\Throwable) {
            $result['resident'] = [];
        }

        try {
            $staffShou = DB::connection('sqlsrv_payroll')
                ->table('dbo.t_staff_shou')
                ->whereRaw('LTRIM(RTRIM(staff_id)) = ?', [$staffId])
                ->whereDate('raise_year', '<', $cutoffDate)
                ->orderBy('raise_year', 'desc')
                ->orderBy('staff_shou_no', 'desc')
                ->first();

            if ($staffShou !== null) {
                $result['syaho'] = [
                    'raise_year' => $this->formatYearMonth((string) ($staffShou->raise_year ?? '')),
                    'kenpo_amo' => $this->formatAmountOrRaw($staffShou->kenpo_amo ?? null),
                    'kaigo_amo' => $this->formatAmountOrRaw($staffShou->kaigo_amo ?? null),
                    'kounen_amo' => $this->formatAmountOrRaw($staffShou->kounen_amo ?? null),
                    'kenpo_monthly_amo' => $this->formatAmountOrRaw($staffShou->kenpo_monthly_amo ?? null),
                    'kounen_monthly_amo' => $this->formatAmountOrRaw($staffShou->kounen_monthly_amo ?? null),
                    'kenpo_toukyu' => $this->formatAmountOrRaw($staffShou->kenpo_toukyu ?? null),
                    'kounen_toukyu' => $this->formatAmountOrRaw($staffShou->kounen_toukyu ?? null),
                ];
            }
        } catch (\Throwable) {
            $result['syaho'] = [];
        }

        return $result;
    }

    private function formatAmountOrRaw(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $text = trim((string) $value);
        $numeric = str_replace(',', '', $text);
        if (preg_match('/^-?\d+(\.\d+)?$/', $numeric)) {
            return $this->formatAmount($numeric);
        }

        return $text;
    }

    private function formatFixed2OrRaw(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $text = trim((string) $value);
        $numeric = str_replace(',', '', $text);
        if (preg_match('/^-?\d+(\.\d+)?$/', $numeric)) {
            return number_format((float) $numeric, 2, '.', ',');
        }

        return $text;
    }

    private function formatYearMonth(string $value): string
    {
        if ($value === '') {
            return '';
        }
        $ts = strtotime($value);
        if ($ts === false) {
            return $value;
        }
        return date('Y/n', $ts);
    }

    private function normalizeNumericInput(mixed $value): mixed
    {
        if ($value === null) {
            return 0;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return 0;
        }

        $normalized = str_replace([',', ' ', '　'], '', $text);
        if (!preg_match('/^-?\d+(\.\d+)?$/', $normalized)) {
            return $text;
        }

        return str_contains($normalized, '.') ? (float) $normalized : (int) $normalized;
    }

    private function redirectPayrollWithQuery(array $validated, string $message): RedirectResponse
    {
        $query = [
            'month' => (string) ($validated['month'] ?? ''),
            'row' => (string) ($validated['row'] ?? ''),
        ];
        $staffFilter = trim((string) ($validated['staff_id'] ?? ''));
        if ($staffFilter !== '') {
            $query['staff_id'] = $staffFilter;
        }
        $companyFilter = trim((string) ($validated['company_id'] ?? ''));
        if ($companyFilter !== '') {
            $query['company_id'] = $companyFilter;
        }

        return redirect()->route('admin.payroll.index', $query)->with('status', $message);
    }

    private function resolveTimeCardTable(): ?string
    {
        $candidates = ['dbo.m_time_cards', 'm_time_cards', 'dbo.t_time_card', 't_time_card'];
        foreach ($candidates as $table) {
            try {
                if (Schema::connection('sqlsrv')->hasTable($table)) {
                    return $table;
                }
            } catch (\Throwable) {
                // no-op
            }
        }
        return null;
    }

    private function tableColumns(string $connection, string $table): array
    {
        try {
            return Schema::connection($connection)->getColumnListing($table);
        } catch (\Throwable) {
            return [];
        }
    }

    private function pickColumn(array $columns, array $candidates): ?string
    {
        $map = [];
        foreach ($columns as $col) {
            $map[mb_strtolower((string) $col)] = (string) $col;
        }
        foreach ($candidates as $name) {
            $k = mb_strtolower((string) $name);
            if (isset($map[$k])) {
                return $map[$k];
            }
        }
        return null;
    }

    private function wrap(string $column): string
    {
        return '[' . str_replace(']', ']]', $column) . ']';
    }

    private function payrollAllowanceHasColumn(string $column): bool
    {
        static $cache = [];

        if (array_key_exists($column, $cache)) {
            return $cache[$column];
        }

        try {
            $cache[$column] = Schema::connection('sqlsrv_payroll')->hasColumn('t_allowance', $column)
                || Schema::connection('sqlsrv_payroll')->hasColumn('dbo.t_allowance', $column);
        } catch (\Throwable) {
            $cache[$column] = false;
        }

        return $cache[$column];
    }

    private function staffHasColumn(string $column): bool
    {
        static $cache = [];

        if (array_key_exists($column, $cache)) {
            return $cache[$column];
        }

        try {
            $cache[$column] = Schema::connection('sqlsrv')->hasColumn('m_staffs', $column)
                || Schema::connection('sqlsrv')->hasColumn('dbo.m_staffs', $column);
        } catch (\Throwable) {
            $cache[$column] = false;
        }

        return $cache[$column];
    }
}



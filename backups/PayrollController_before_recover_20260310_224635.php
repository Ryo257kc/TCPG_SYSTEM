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
                    ['name' => 'employment_type', 'value' => '-'],
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
            'recalc_koyou' => ['nullable', 'in:0,1'],
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
            return $this->redirectPayrollWithQuery($validated, '給与が確定済みのため編集できません。');
        }

        $payload = [];
        if (is_string($entry->raw_payload ?? null) && trim((string) $entry->raw_payload) !== '') {
            $decoded = json_decode((string) $entry->raw_payload, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        if (((int) ($payload['attendance_checked'] ?? 0)) !== 1) {
            return $this->redirectPayrollWithQuery($validated, '勤怠未確定のため編集できません。');
        }

        $fields = is_array($validated['fields'] ?? null) ? $validated['fields'] : [];
        if ($fields === []) {
            return $this->redirectPayrollWithQuery($validated, '更新項目がありません。');
        }

        foreach ($fields as $k => $v) {
            if (!is_string($k)) {
                continue;
            }
            if (is_string($v) && trim($v) === '' && array_key_exists($k, $payload)) {
                // Keep existing value when blank is posted from hidden/non-edited inputs.
                continue;
            }
            $payload[$k] = $this->normalizeNumericInput($v);
        }

        $payrollDate = sprintf('%04d-%02d-01', $year, $month);
        $this->applyComputedTotals($payload, $staffId, $payrollDate);

        if (((string)($validated['recalc_koyou'] ?? '0')) === '1') {
            // Recalculate koyou using freshly computed rouho_target_sum,
            // then recalc totals again so syaho_sum/deduction/net stay consistent.
            $this->applyEmploymentInsurance($payload, $staffId, $payrollDate);
            $this->applyComputedTotals($payload, $staffId, $payrollDate);
        }

        $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if (!is_string($encodedPayload)) {
            return $this->redirectPayrollWithQuery($validated, '更新データの保存に失敗しました。');
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.m_payroll_entries')
            ->where('payroll_entry_id', (int) $entry->payroll_entry_id)
            ->update([
                'raw_payload' => $encodedPayload,
                'updated_at' => now('Asia/Tokyo'),
            ]);

        return $this->redirectPayrollWithQuery($validated, '給与情報を更新しました。');
    }

    public function recalcKoyou(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'target_staff_id' => ['required', 'string', 'max:50'],
            'staff_id' => ['nullable', 'string', 'max:50'],
            'company_id' => ['nullable', 'string', 'max:200'],
            'row' => ['nullable', 'string', 'max:100'],
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
            return $this->redirectPayrollWithQuery($validated, '給与が確定済みのため編集できません。');
        }

        $payload = $this->decodePayload($entry->raw_payload ?? null);
        $payrollDate = sprintf('%04d-%02d-01', $year, $month);
        $this->applyComputedTotals($payload, $staffId, $payrollDate);
        $this->applyEmploymentInsurance($payload, $staffId, $payrollDate);
        $this->applyComputedTotals($payload, $staffId, $payrollDate);

        $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if (!is_string($encodedPayload)) {
            return $this->redirectPayrollWithQuery($validated, '更新データの保存に失敗しました。');
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.m_payroll_entries')
            ->where('payroll_entry_id', (int) $entry->payroll_entry_id)
            ->update([
                'raw_payload' => $encodedPayload,
                'updated_at' => now('Asia/Tokyo'),
            ]);

        return $this->redirectPayrollWithQuery($validated, '雇用保険を再計算しました。');
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
            return $this->redirectPayrollWithQuery($validated, '給与が確定済みのため反映できません。');
        }

        $preview = $this->buildAttendanceAggregatePreview($staffId, sprintf('%04d-%02d-01', $year, $month));
        if ($preview === []) {
            return $this->redirectPayrollWithQuery($validated, '勤怠データがありません。');
        }
        $payload = $this->decodePayload($entry->raw_payload ?? null);

        $updatedKeys = [];
        foreach ($preview as $key => $value) {
            $payload[$key] = $value;
            $updatedKeys[] = $key;
        }

        $companyCode = $this->resolveStaffCompanyCode($staffId);
        $masterInfo = $this->resolvePayrollMasterInfo(
            $staffId,
            sprintf('%04d-%02d-01', $year, $month),
            $companyCode
        );
        $masterKeys = $this->applyPayrollMastersToPayload($payload, $masterInfo, $staffId);
        foreach ($masterKeys as $mk) {
            $updatedKeys[] = $mk;
        }

        $this->applyComputedTotals($payload, $staffId, sprintf('%04d-%02d-01', $year, $month));

        $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if (!is_string($encodedPayload)) {
            return $this->redirectPayrollWithQuery($validated, '勤怠反映データの保存に失敗しました。');
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.m_payroll_entries')
            ->where('payroll_entry_id', (int) $entry->payroll_entry_id)
            ->update([
                'raw_payload' => $encodedPayload,
                'updated_at' => now('Asia/Tokyo'),
            ]);

        return $this->redirectPayrollWithQuery($validated, '勤怠一括反映を実行しました。更新件数: ' . count($updatedKeys) . '件');
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
                return $this->redirectPayrollWithQuery($validated, '選択対象スタッフが見つかりません。');
            }

            $entryQuery->whereIn(DB::raw('LTRIM(RTRIM(staff_code))'), $staffIds);
        }

        $entries = $entryQuery
            ->orderByRaw('LTRIM(RTRIM(staff_code)) ASC')
            ->get(['payroll_entry_id', 'staff_code', 'raw_payload', 'is_edit_locked']);

        if ($entries->isEmpty()) {
            return $this->redirectPayrollWithQuery($validated, '対象月の給与データが見つかりません。');
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

            $companyCode = $this->resolveStaffCompanyCode($staffId);
            $masterInfo = $this->resolvePayrollMasterInfo($staffId, $payrollMonthDate, $companyCode);
            $masterKeys = $this->applyPayrollMastersToPayload($payload, $masterInfo, $staffId);
            $updatedFields += count($masterKeys);

            $this->applyComputedTotals($payload, $staffId, $payrollMonthDate);

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
            '蜍､諤荳諡ｬ蜿肴丐繧貞ｮ溯｡後＠縺ｾ縺励◆縲ょｯｾ雎｡: ' . $updatedStaff . '莠ｺ / 譖ｴ譁ｰ鬆・岼: ' . $updatedFields . '莉ｶ'
            . ($skippedLocked > 0 ? ' / 遒ｺ螳壽ｸ医∩繧ｹ繧ｭ繝・・: ' . $skippedLocked . '莠ｺ' : '')
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
        if ($staffId === '' || !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $selectedMonthDate)) {
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

        $changeStartCol = $this->pickColumn($columns, ['change_start', 'edit_start']);
        $changeLeaveCol = $this->pickColumn($columns, ['change_leave', 'edit_leave']);
        $changeBreakCol = $this->pickColumn($columns, ['change_break_out', 'edit_break_out']);
        $changeEndCol = $this->pickColumn($columns, ['change_end', 'edit_end']);
        $shiftStartCol = $this->pickColumn($columns, ['shift_start', 'shift_in']);
        $shiftLeaveCol = $this->pickColumn($columns, ['shift_leave', 'shift_exit']);
        $shiftBreakCol = $this->pickColumn($columns, ['shift_break_out', 'shift_entry']);
        $shiftEndCol = $this->pickColumn($columns, ['shift_end', 'shift_out']);
        $changeWorkCol = $this->pickColumn($columns, ['change_work', 'work_time_change', 'edit_work']);
        $shiftWorkCol = $this->pickColumn($columns, ['shift_work', 'work_time_shift']);

        $categoryCol = $this->pickColumn($columns, ['attendance_category', 'work_category', 'category', 'kintai_category', 'kubun', 'work_type']);
        $categoryTimeCol = $this->pickColumn($columns, ['attendance_category_time', 'work_type_time', 'category_time']);
        $absenceCol = $this->pickColumn($columns, ['absence_num', 'absence_days']);
        $overtimeCols = array_values(array_filter([
            $this->pickColumn($columns, ['overtime']),
            $this->pickColumn($columns, ['overtime_hours']),
            $this->pickColumn($columns, ['over_time']),
            $this->pickColumn($columns, ['overtime_hours_2']),
        ], static fn ($v) => $v !== null));
        $nightOvertimeCols = array_values(array_filter([
            $this->pickColumn($columns, ['night_over_time']),
            $this->pickColumn($columns, ['night_overtime_hours']),
            $this->pickColumn($columns, ['night_overtime']),
        ], static fn ($v) => $v !== null));
        $paidLeaveUsedCol = $this->pickColumn($columns, ['paid_leave_used', 'horiday_true']);
        $paidLeaveRemainCol = $this->pickColumn($columns, ['paid_leave_balance', 'horiday_true_num']);
        $closedDaysCol = $this->pickColumn($columns, ['days_closed']);
        $closedTimeCol = $this->pickColumn($columns, ['time_closed']);

        $selectCols = [$staffCol, $dateCol];
        foreach ([
            $changeStartCol,
            $changeLeaveCol,
            $changeBreakCol,
            $changeEndCol,
            $shiftStartCol,
            $shiftLeaveCol,
            $shiftBreakCol,
            $shiftEndCol,
            $changeWorkCol,
            $shiftWorkCol,
            $categoryCol,
            $categoryTimeCol,
            $absenceCol,
            $paidLeaveUsedCol,
            $paidLeaveRemainCol,
            $closedDaysCol,
            $closedTimeCol,
        ] as $c) {
            if ($c !== null && !in_array($c, $selectCols, true)) {
                $selectCols[] = $c;
            }
        }
        foreach ($overtimeCols as $c) {
            if (!in_array($c, $selectCols, true)) {
                $selectCols[] = $c;
            }
        }
        foreach ($nightOvertimeCols as $c) {
            if (!in_array($c, $selectCols, true)) {
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
        $holidayWorkTime = 0.0;
        $workTime = 0.0;
        $overtime = 0.0;
        $nightOvertime = 0.0;
        $lateTime = 0.0;
        $paidLeaveUsed = 0.0;
        $paidLeaveRemain = null;
        $closedDays = 0.0;
        $closedTime = 0.0;

        foreach ($rows as $row) {
            $rowArray = (array) $row;

            $changeHours = $this->calcWorkHoursFromParts(
                $changeStartCol !== null ? ($rowArray[$changeStartCol] ?? null) : null,
                $changeLeaveCol !== null ? ($rowArray[$changeLeaveCol] ?? null) : null,
                $changeBreakCol !== null ? ($rowArray[$changeBreakCol] ?? null) : null,
                $changeEndCol !== null ? ($rowArray[$changeEndCol] ?? null) : null
            );
            if (($changeHours === null || $changeHours <= 0) && $changeWorkCol !== null) {
                $v = (float) ($rowArray[$changeWorkCol] ?? 0);
                if ($v > 0) {
                    $changeHours = $v;
                }
            }
            $shiftHours = $this->calcWorkHoursFromParts(
                $shiftStartCol !== null ? ($rowArray[$shiftStartCol] ?? null) : null,
                $shiftLeaveCol !== null ? ($rowArray[$shiftLeaveCol] ?? null) : null,
                $shiftBreakCol !== null ? ($rowArray[$shiftBreakCol] ?? null) : null,
                $shiftEndCol !== null ? ($rowArray[$shiftEndCol] ?? null) : null
            );
            if (($shiftHours === null || $shiftHours <= 0) && $shiftWorkCol !== null) {
                $v = (float) ($rowArray[$shiftWorkCol] ?? 0);
                if ($v > 0) {
                    $shiftHours = $v;
                }
            }
            // 実働は「変更実績 > シフト > 0」の順で採用（打刻は使わない）
            $dayHours = $changeHours ?? $shiftHours ?? 0.0;

            $category = trim((string) ($categoryCol !== null ? ($rowArray[$categoryCol] ?? '') : ''));
            $categoryTime = (float) ($categoryTimeCol !== null ? ($rowArray[$categoryTimeCol] ?? 0) : 0);

            $categoryLower = strtolower($category);
            $isHolidayWork = in_array($category, ['休出', '法出'], true)
                || str_contains($categoryLower, 'holiday_work')
                || str_contains($categoryLower, 'legal_holiday_work');
            $isAbsence = in_array($category, ['欠勤'], true)
                || str_contains($categoryLower, 'absence');
            $isLateEarly = in_array($category, ['遅刻', '早退', '遅早'], true)
                || str_contains($categoryLower, 'late')
                || str_contains($categoryLower, 'early');

            if ($isHolidayWork) {
                $holidayWorkDays += 1;
                $holidayWorkTime += $categoryTime;
            } elseif ($dayHours > 0) {
                $attendanceDays += 1;
                $workTime += $dayHours;
            }

            if ($absenceCol !== null) {
                $absenceDays += (float) ($rowArray[$absenceCol] ?? 0);
            } elseif ($isAbsence) {
                $absenceDays += 1;
            }

            if ($isLateEarly && $categoryTime > 0) {
                $lateTime += $categoryTime;
            }

            $ot = 0.0;
            foreach ($overtimeCols as $c) {
                $v = (float) ($rowArray[$c] ?? 0);
                if (abs($v) > 0.000001) {
                    $ot = $v;
                    break;
                }
            }
            $overtime += $ot;

            $night = 0.0;
            foreach ($nightOvertimeCols as $c) {
                $v = (float) ($rowArray[$c] ?? 0);
                if (abs($v) > 0.000001) {
                    $night = $v;
                    break;
                }
            }
            $nightOvertime += $night;
            $paidLeaveUsed += (float) ($paidLeaveUsedCol !== null ? ($rowArray[$paidLeaveUsedCol] ?? 0) : 0);
            $closedDays += (float) ($closedDaysCol !== null ? ($rowArray[$closedDaysCol] ?? 0) : 0);
            $closedTime += (float) ($closedTimeCol !== null ? ($rowArray[$closedTimeCol] ?? 0) : 0);

            if (
                $paidLeaveRemainCol !== null
                && array_key_exists($paidLeaveRemainCol, $rowArray)
                && $rowArray[$paidLeaveRemainCol] !== null
                && $rowArray[$paidLeaveRemainCol] !== ''
            ) {
                $paidLeaveRemain = (float) $rowArray[$paidLeaveRemainCol];
            }
        }

        return [
            'work_in_num' => $attendanceDays,
            'absence_num' => $absenceDays,
            'work_horiday_num' => $holidayWorkDays,
            'work_time_num' => $holidayWorkTime,
            'work_time' => $workTime,
            'overtime' => $overtime,
            'night_over_time' => $nightOvertime,
            'late_time' => $lateTime,
            'horiday_true' => $paidLeaveUsed,
            'days_closed' => $closedDays,
            'time_closed' => $closedTime,
            'attendance_checked_month' => $fromDate,
        ] + ($paidLeaveRemain !== null ? ['horiday_true_num' => $paidLeaveRemain] : []);
    }

    private function toMinuteOfDay(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);
        if ($s === '') {
            return null;
        }
        if (preg_match('/\b(\d{1,2}):(\d{2})\b/', $s, $m) !== 1) {
            return null;
        }
        $h = (int) $m[1];
        $i = (int) $m[2];
        if ($h < 0 || $h > 23 || $i < 0 || $i > 59) {
            return null;
        }
        return ($h * 60) + $i;
    }

    private function calcWorkHoursFromParts(mixed $start, mixed $leave, mixed $breakOut, mixed $end): ?float
    {
        $s = $this->toMinuteOfDay($start);
        $l = $this->toMinuteOfDay($leave);
        $b = $this->toMinuteOfDay($breakOut);
        $e = $this->toMinuteOfDay($end);

        $total = 0.0;
        $hasAny = false;

        if ($s !== null && $l !== null && $l >= $s) {
            $total += ($l - $s) / 60;
            $hasAny = true;
        }
        if ($b !== null && $e !== null && $e >= $b) {
            $total += ($e - $b) / 60;
            $hasAny = true;
        }

        if (!$hasAny) {
            return null;
        }
        return round($total, 2);
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
            $hasTaxTarget = in_array('tax_target', $columns, true);
            $hasSyahoTarget = in_array('syaho_target', $columns, true);
            $hasRouTarget = in_array('rou_target', $columns, true);

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
                    DB::raw($hasTaxTarget ? 'tax_target' : '0 as tax_target'),
                    DB::raw($hasSyahoTarget ? 'syaho_target' : '0 as syaho_target'),
                    DB::raw($hasRouTarget ? 'rou_target' : '0 as rou_target'),
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
                    'tax_target' => (int) ($row->tax_target ?? 0),
                    'syaho_target' => (int) ($row->syaho_target ?? 0),
                    'rou_target' => (int) ($row->rou_target ?? 0),
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
            $staffShouTable = $this->resolvePayrollMasterTable([
                'dbo.m_staff_social_insurances',
                'm_staff_social_insurances',
                'dbo.t_staff_shou',
                't_staff_shou',
            ]);

            $staffShou = null;
            if ($staffShouTable !== null) {
                $staffCodeCol = $this->payrollTableHasColumn($staffShouTable, 'staff_code') ? 'staff_code' : 'staff_id';
                $idOrderCol = $this->payrollTableHasColumn($staffShouTable, 'staff_social_insurance_id')
                    ? 'staff_social_insurance_id'
                    : ($this->payrollTableHasColumn($staffShouTable, 'staff_shou_no') ? 'staff_shou_no' : null);

                $query = DB::connection('sqlsrv_payroll')
                    ->table($staffShouTable)
                    ->whereRaw('LTRIM(RTRIM(' . $this->wrap($staffCodeCol) . ')) = ?', [$staffId])
                    ->whereDate('raise_year', '<', $cutoffDate)
                    ->orderBy('raise_year', 'desc');

                if ($idOrderCol !== null) {
                    $query->orderBy($idOrderCol, 'desc');
                }

                $staffShou = $query->first();
            }

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

    private function resolvePayrollMasterTable(array $candidates): ?string
    {
        foreach ($candidates as $table) {
            try {
                if (Schema::connection('sqlsrv_payroll')->hasTable($table)) {
                    return $table;
                }
            } catch (\Throwable) {
                // no-op
            }
        }
        return null;
    }

    private function payrollTableHasColumn(string $table, string $column): bool
    {
        try {
            return Schema::connection('sqlsrv_payroll')->hasColumn($table, $column);
        } catch (\Throwable) {
            return false;
        }
    }

    private function applyPayrollMastersToPayload(array &$payload, array $masterInfo, string $staffId): array
    {
        $updated = [];
        $set = function (string $payloadKey, mixed $value) use (&$payload, &$updated): void {
            if ($value === null) {
                return;
            }
            $text = trim((string) $value);
            if ($text === '') {
                return;
            }
            $normalized = $this->normalizeNumericInput($text);
            $payload[$payloadKey] = $normalized;
            $updated[] = $payloadKey;
        };

        $kihon = (array) ($masterInfo['kihon'] ?? []);
        $basePay = $this->resolveBasePayForEmployment($staffId, $kihon);
        $set('basic_salary', $basePay);
        // "隴幄ご・ｵ・ｦ繝ｻ荵怜・驍ｨ・ｦ" slot (隰・唱・ｽ繝ｻ) follows employment type rule.
        $set('allowance_amo_1', $basePay);
        $set('allowance_amo_2', $kihon['executive_remu'] ?? null);
        $set('allowance_amo_16', $kihon['position_allow'] ?? null);
        $set('allowance_amo_13', $kihon['duties_allow'] ?? null);
        $set('allowance_amo_11', $kihon['qualification_allow'] ?? null);
        $set('allowance_amo_12', $kihon['claim_allow'] ?? null);
        $set('allowance_amo_10', $kihon['traffic_pay'] ?? null);
        $set('allowance_amo_5', $kihon['adjustment_add'] ?? null);
        $set('allowance_amo_14', $kihon['rent_subsidies'] ?? null);
        $set('rent_cost', $kihon['rent_pay'] ?? null);
        $set('adjustment_cost', $kihon['adjustment_pay'] ?? null);
        $set('allowance_amo_15', $kihon['fixed_overtime'] ?? null);

        $syaho = (array) ($masterInfo['syaho'] ?? []);
        $set('kenpo', $syaho['kenpo_amo'] ?? null);
        $set('kaigo', $syaho['kaigo_amo'] ?? null);
        $set('kounen', $syaho['kounen_amo'] ?? null);

        $resident = (array) ($masterInfo['resident'] ?? []);
        $set('resident_tax', $resident['resident_tax_month'] ?? null);

        return array_values(array_unique($updated));
    }

    private function resolveBasePayForEmployment(string $staffId, array $kihon): mixed
    {
        $staffId = trim($staffId);
        $employment = '';

        if ($staffId !== '') {
            try {
                $row = DB::connection('sqlsrv')
                    ->table('dbo.m_staffs')
                    ->whereRaw('LTRIM(RTRIM(staff_code)) = ?', [$staffId])
                    ->first(['staff_division', 'employment_status']);

                $employment = trim((string) ($row->staff_division ?? ''));
                if ($employment === '') {
                    $employment = trim((string) ($row->employment_status ?? ''));
                }
            } catch (\Throwable) {
                $employment = '';
            }
        }

        // 雇用形態がパートの場合は時給、それ以外は月給
        if (mb_strpos($employment, 'パート') !== false) {
            return $kihon['hourly_pay'] ?? ($kihon['hourly_salary'] ?? null);
        }

        return $kihon['monthly_salary'] ?? null;
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

        $normalized = str_replace([',', ' ', '邵ｲﾂ'], '', $text);
        if (!preg_match('/^-?\d+(\.\d+)?$/', $normalized)) {
            return $text;
        }

        return str_contains($normalized, '.') ? (float) $normalized : (int) $normalized;
    }

    private function payloadNumber(array $payload, string $key): float
    {
        if (!array_key_exists($key, $payload)) {
            return 0.0;
        }
        $normalized = $this->normalizeNumericInput($payload[$key]);
        if (is_int($normalized) || is_float($normalized)) {
            return (float) $normalized;
        }
        if (is_string($normalized)) {
            $s = str_replace([',', ' ', '邵ｲﾂ'], '', trim($normalized));
            if ($s !== '' && is_numeric($s)) {
                return (float) $s;
            }
        }
        return 0.0;
    }

    private function applyComputedTotals(array &$payload, string $staffId = '', string $payrollMonthDate = ''): void
    {
        $syahoSum = $this->payloadNumber($payload, 'kenpo')
            + $this->payloadNumber($payload, 'kaigo')
            + $this->payloadNumber($payload, 'kounen')
            + $this->payloadNumber($payload, 'koyou');
        $payload['syaho_sum'] = $this->normalizeNumericInput($syahoSum);

        $supplySum = 0.0;
        $supplySum += $this->payloadNumber($payload, 'basic_salary');
        $supplySum += $this->payloadNumber($payload, 'officer_com');
        for ($i = 2; $i <= 17; $i++) {
            $supplySum += $this->payloadNumber($payload, 'allowance_amo_' . $i);
        }
        $supplySum += $this->payloadNumber($payload, 'traffic_addition');
        $supplySum += $this->payloadNumber($payload, 'leave_allowance');
        $supplySum += $this->payloadNumber($payload, 'late_deduction');
        $supplySum += $this->payloadNumber($payload, 'absence_deduction');

        $targets = $this->computeTargetsFromAllowanceSettings($payload, $staffId, $supplySum);
        $taxable = $targets['taxable'];
        $rouhoTarget = $targets['rouho_target'];
        $syahoTarget = $targets['syaho_target'];
        $nontaxable = max(0.0, $supplySum - $taxable);
        $payload['taxation_sum'] = $this->normalizeNumericInput($taxable);
        $payload['not_taxation_sum'] = $this->normalizeNumericInput($nontaxable);

        $payload['supply_sum'] = $this->normalizeNumericInput($supplySum);
        $payload['pay_total'] = $this->normalizeNumericInput($supplySum);

        $payload['rouho_target_sum'] = $this->normalizeNumericInput($rouhoTarget);
        $payload['syaho_target_sum'] = $this->normalizeNumericInput($syahoTarget);

        $deductionSum = $syahoSum
            + $this->payloadNumber($payload, 'income_tax')
            + $this->payloadNumber($payload, 'resident_tax')
            + $this->payloadNumber($payload, 'rent_cost')
            + $this->payloadNumber($payload, 'adjustment_cost');
        $payload['deduction_sum'] = $this->normalizeNumericInput($deductionSum);
        $payload['koujo_total'] = $this->normalizeNumericInput($deductionSum);

        $otherTotal = $this->payloadNumber($payload, 'adjustment_year_end')
            + $this->payloadNumber($payload, 'cost_liquidation')
            + $this->payloadNumber($payload, 'not_supply');
        $payload['other_total'] = $this->normalizeNumericInput($otherTotal);

        $afterSocial = $supplySum - $syahoSum;
        $payload['syaho_deduction_sum'] = $this->normalizeNumericInput($afterSocial);

        $netPay = $supplySum - $deductionSum + $otherTotal;
        $payload['supply_deduction_sum'] = $this->normalizeNumericInput($netPay);
        $payload['salary_total'] = $this->normalizeNumericInput($netPay);
        $payload['transfer_amo'] = $this->normalizeNumericInput($netPay);
    }
    private function applyEmploymentInsurance(array &$payload, string $staffId, string $payrollMonthDate): void
    {
        $staffId = trim($staffId);
        $monthDate = trim($payrollMonthDate);
        if ($staffId === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $monthDate)) {
            return;
        }

        $isBonus = $this->payloadNumber($payload, 'bonus') > 0
            || in_array(strtolower((string)($payload['bonus'] ?? '')), ['true', '1'], true);
        $base = $isBonus
            ? $this->payloadNumber($payload, 'bonus_amo')
            : $this->payloadNumber($payload, 'rouho_target_sum');

        $ratePerMille = $this->resolveLaborInsuranceRate($monthDate);
        if ($ratePerMille <= 0 || $base <= 0) {
            $payload['koyou'] = 0;
            return;
        }

        $staffDivision = $this->resolveStaffDivision($staffId);
        if ($staffId === '001' || $staffDivision === '管理責任者') {
            $payload['koyou'] = 0;
            return;
        }

        // Access互換: 四捨五入（0桁）
        $koyou = round($base * ($ratePerMille / 1000), 0, PHP_ROUND_HALF_UP);
        $payload['koyou'] = $this->normalizeNumericInput($koyou);
    }

    private function resolveLaborInsuranceRate(string $payrollMonthDate): float
    {
        static $cache = [];
        if (isset($cache[$payrollMonthDate])) {
            return $cache[$payrollMonthDate];
        }

        $conn = DB::connection('sqlsrv_payroll');
        $candidates = [
            ['dbo.m_labor_insurance_rates', 'apply_date', 'general_employee_rate'],
            ['m_labor_insurance_rates', 'apply_date', 'general_employee_rate'],
            ['dbo.t_rouho', 'rou_apply_date', 'general_st'],
            ['t_rouho', 'rou_apply_date', 'general_st'],
        ];

        foreach ($candidates as [$table, $dateCol, $rateCol]) {
            try {
                if (!Schema::connection('sqlsrv_payroll')->hasTable($table)) {
                    continue;
                }
                if (!Schema::connection('sqlsrv_payroll')->hasColumn($table, $dateCol) || !Schema::connection('sqlsrv_payroll')->hasColumn($table, $rateCol)) {
                    continue;
                }
                $row = $conn->table($table)
                    ->whereRaw('CONVERT(date, ' . $this->wrap($dateCol) . ') <= ?', [$payrollMonthDate])
                    ->orderBy($dateCol, 'desc')
                    ->first([$rateCol]);
                if ($row !== null) {
                    $rate = (float) ($row->{$rateCol} ?? 0);
                    if ($rate > 0) {
                        $cache[$payrollMonthDate] = $rate;
                        return $rate;
                    }
                }
            } catch (\Throwable) {
                // no-op
            }
        }

        $cache[$payrollMonthDate] = 0.0;
        return 0.0;
    }

    private function resolveStaffDivision(string $staffId): string
    {
        static $cache = [];
        $staffId = trim($staffId);
        if ($staffId === '') {
            return '';
        }
        if (array_key_exists($staffId, $cache)) {
            return $cache[$staffId];
        }
        try {
            $row = DB::connection('sqlsrv')
                ->table('dbo.m_staffs')
                ->whereRaw('LTRIM(RTRIM(staff_code)) = ?', [$staffId])
                ->first(['staff_division']);
            $cache[$staffId] = trim((string)($row->staff_division ?? ''));
            return $cache[$staffId];
        } catch (\Throwable) {
            $cache[$staffId] = '';
            return '';
        }
    }

    private function computeTargetsFromAllowanceSettings(array $payload, string $staffId, float $supplySum): array
    {
        $staffId = trim($staffId);
        if ($staffId === '') {
            return [
                'taxable' => max(0.0, $this->payloadNumber($payload, 'taxation_sum')),
                'rouho_target' => max(0.0, $this->payloadNumber($payload, 'rouho_target_sum')),
                'syaho_target' => max(0.0, $this->payloadNumber($payload, 'syaho_target_sum')),
            ];
        }

        $companyCode = $this->resolveStaffCompanyCode($staffId);
        $defs = $this->resolveAllowanceDefinitionsOrdered($companyCode);
        if ($defs === []) {
            // fallback: keep current payload values when mapping is unavailable
            return [
                'taxable' => max(0.0, $this->payloadNumber($payload, 'taxation_sum')),
                'rouho_target' => max(0.0, $this->payloadNumber($payload, 'rouho_target_sum')),
                'syaho_target' => max(0.0, $this->payloadNumber($payload, 'syaho_target_sum')),
            ];
        }

        $taxable = 0.0;
        $rouhoTarget = 0.0;
        $syahoTarget = 0.0;

        foreach ($defs as $def) {
            $key = trim((string)($def['amount_column_key'] ?? ''));
            if ($key === '') {
                $slotNo = (int)($def['slot_no'] ?? 0);
                if ($slotNo > 0) {
                    $key = 'allowance_amo_' . $slotNo;
                }
            }
            if ($key === '') {
                continue;
            }

            $amount = $this->payloadNumber($payload, $key);
            if ((int)($def['tax_target'] ?? 0) === 1) {
                $taxable += $amount;
            }
            if ((int)($def['rou_target'] ?? 0) === 1) {
                $rouhoTarget += $amount;
            }
            if ((int)($def['syaho_target'] ?? 0) === 1) {
                $syahoTarget += $amount;
            }
        }

        $taxable = max(0.0, min($taxable, $supplySum));
        $rouhoTarget = max(0.0, min($rouhoTarget, $supplySum));
        $syahoTarget = max(0.0, min($syahoTarget, $supplySum));

        return [
            'taxable' => $taxable,
            'rouho_target' => $rouhoTarget,
            'syaho_target' => $syahoTarget,
        ];
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
        $candidates = ['dbo.m_time_cards', 'm_time_cards'];
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

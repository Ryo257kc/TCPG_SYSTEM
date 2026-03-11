<?php

namespace App\Services\Admin\Payroll;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PayrollIndexQueryService
{
    public function resolveMonth(Request $request): array
    {
        $availableMonths = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
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

        return [
            'availableMonths' => $availableMonths,
            'selectedMonth' => $selectedMonth,
            'year' => (int) substr($selectedMonth, 0, 4),
            'month' => (int) substr($selectedMonth, 5, 2),
        ];
    }

    public function companyOptions(): array
    {
        return DB::connection('sqlsrv')
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
    }

    public function targetStaffIdsFromPayroll(int $year, int $month): array
    {
        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
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
    }

    public function staffContext(array $targetStaffIdsFromPayroll, string $selectedCompanyId): array
    {
        $staffQuery = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs as ms')
            ->leftJoin('dbo.mx_stores as st', 'ms.store_code', '=', 'st.store_code')
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
            ->table('dbo.mx_staffs')
            ->selectRaw('LTRIM(RTRIM(staff_code)) as staff_id, staff_name')
            ->whereNotNull('staff_code')
            ->get()
            ->mapWithKeys(fn ($row) => [(string) $row->staff_id => (string) ($row->staff_name ?? '')])
            ->all();

        $hasStaffDivision = $this->staffHasColumn('staff_division');
        $staffDivisionMap = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
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
            ->table('dbo.mx_staffs as ms')
            ->leftJoin('dbo.mx_stores as st', 'ms.store_code', '=', 'st.store_code')
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

        return [
            'staffOptions' => $staffOptions,
            'staffNameMap' => $staffNameMap,
            'staffDivisionMap' => $staffDivisionMap,
            'staffOrgMap' => $staffOrgMap,
            'staffIdsByCompany' => $staffIdsByCompany,
        ];
    }

    public function rawRowByStaff(int $year, int $month, string $selectedStaffId, string $selectedCompanyId, array $staffIdsByCompany): array
    {
        $rawRows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
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

        return $rawRowByStaff;
    }

    public function bulkStaffRows(string $selectedMonthDate, string $selectedCompanyId): array
    {
        $monthStartDate = $selectedMonthDate;
        $monthEndDate = date('Y-m-t', strtotime($monthStartDate));

        $activeStaffIds = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->whereNotNull('staff_code')
            ->whereRaw('LTRIM(RTRIM(staff_code)) <> ?', [''])
            ->where(function ($q) use ($monthEndDate) {
                $q->whereNull('hire_date')
                    ->orWhereRaw('CONVERT(date, hire_date) <= ?', [$monthEndDate]);
            })
            ->where(function ($q) use ($monthStartDate) {
                $q->whereNull('retire_date')
                    ->orWhereRaw('CONVERT(date, retire_date) >= ?', [$monthStartDate]);
            })
            ->selectRaw('DISTINCT LTRIM(RTRIM(staff_code)) as staff_id')
            ->pluck('staff_id')
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->values()
            ->all();

        $bulkStaffQuery = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs as ms')
            ->leftJoin('dbo.mx_stores as st', 'ms.store_code', '=', 'st.store_code')
            ->selectRaw('LTRIM(RTRIM(ms.staff_code)) as staff_id, ms.staff_name');

        if ($activeStaffIds === []) {
            $bulkStaffQuery->whereRaw('1 = 0');
        } else {
            $bulkStaffQuery->whereIn(DB::raw('LTRIM(RTRIM(ms.staff_code))'), $activeStaffIds);
        }

        if ($selectedCompanyId !== '') {
            $bulkStaffQuery->where('st.company_name', $selectedCompanyId);
        }

        return $bulkStaffQuery
            ->orderBy('ms.staff_code')
            ->get()
            ->map(fn ($row) => [
                'staff_id' => (string) ($row->staff_id ?? ''),
                'staff_name' => (string) ($row->staff_name ?? ''),
            ])
            ->filter(fn ($row) => $row['staff_id'] !== '')
            ->values()
            ->all();
    }

    private function staffHasColumn(string $column): bool
    {
        return Schema::connection('sqlsrv')->hasColumn('dbo.mx_staffs', $column)
            || Schema::connection('sqlsrv')->hasColumn('mx_staffs', $column);
    }
}

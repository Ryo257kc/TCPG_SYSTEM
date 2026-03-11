<?php

namespace App\Services\Admin\Payroll;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PayrollSeedService
{
    public function createMonthlyEntries(array $validated): array
    {
        [$year, $month] = [(int) substr((string) $validated['month'], 0, 4), (int) substr((string) $validated['month'], 5, 2)];
        $monthStartDate = sprintf('%04d-%02d-01', $year, $month);
        $monthEndDate = date('Y-m-t', strtotime($monthStartDate));
        $companyId = trim((string) ($validated['company_id'] ?? ''));
        $selectedStaffId = trim((string) ($validated['staff_id'] ?? ''));

        $targets = collect((array) ($validated['target_staff_ids'] ?? []))
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->unique()
            ->values()
            ->all();

        if ($targets === [] && $selectedStaffId !== '') {
            $targets = [$selectedStaffId];
        }
        if ($targets === []) {
            return ['message' => 'No target staff selected.'];
        }

        $staffCols = $this->tableColumns('sqlsrv', 'mx_staffs');
        $staffIdCol = $this->pickColumn($staffCols, ['staff_id', 'staff_code']);
        $staffStoreCol = $this->pickColumn($staffCols, ['section', 'store_code']);
        $hireDateCol = $this->pickColumn($staffCols, ['nyu_date', 'hire_date']);
        $retireDateCol = $this->pickColumn($staffCols, ['tai_date', 'retire_date']);

        if ($staffIdCol === null || $staffStoreCol === null || $hireDateCol === null || $retireDateCol === null) {
            return ['message' => 'mx_staffs required columns not found.'];
        }

        $staffQuery = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs as ms')
            ->leftJoin('dbo.mx_stores as st', 'ms.' . $staffStoreCol, '=', 'st.store_code')
            ->whereIn(DB::raw('LTRIM(RTRIM(ms.' . $staffIdCol . '))'), $targets)
            ->where(function ($q) use ($hireDateCol, $monthEndDate) {
                $q->whereNull('ms.' . $hireDateCol)
                    ->orWhereRaw('CONVERT(date, ms.' . $hireDateCol . ') <= ?', [$monthEndDate]);
            })
            ->where(function ($q) use ($retireDateCol, $monthStartDate) {
                $q->whereNull('ms.' . $retireDateCol)
                    ->orWhereRaw('CONVERT(date, ms.' . $retireDateCol . ') >= ?', [$monthStartDate]);
            });

        if ($companyId !== '') {
            $staffQuery->where('st.company_name', $companyId);
        }

        $validTargets = $staffQuery
            ->selectRaw('DISTINCT LTRIM(RTRIM(ms.' . $staffIdCol . ')) as staff_id')
            ->pluck('staff_id')
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->values()
            ->all();

        if ($validTargets === []) {
            return ['message' => 'No eligible target staff found.'];
        }

        $payrollCols = $this->tableColumns('sqlsrv_payroll', 'mx_kyuyo_shou');
        $payrollIdCol = $this->pickColumn($payrollCols, ['kyuyo_sho_no', 'payroll_entry_id']);
        $payrollStaffCol = $this->pickColumn($payrollCols, ['kyuyo_staff_id', 'staff_code']);
        $bonusCol = $this->pickColumn($payrollCols, ['bonus', 'is_bonus']) ?? 'bonus';
        $lockCol = $this->pickColumn($payrollCols, ['edit_lock', 'is_edit_locked']) ?? 'edit_lock';

        if ($payrollIdCol === null || $payrollStaffCol === null) {
            return ['message' => 'mx_kyuyo_shou required columns not found.'];
        }

        $existingIds = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where($bonusCol, 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month])
            ->whereIn(DB::raw('LTRIM(RTRIM(' . $payrollStaffCol . '))'), $validTargets)
            ->selectRaw('DISTINCT LTRIM(RTRIM(' . $payrollStaffCol . ')) as staff_id')
            ->pluck('staff_id')
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->values()
            ->all();

        $existingMap = array_fill_keys($existingIds, true);
        $colExists = array_fill_keys(array_map('mb_strtolower', $payrollCols), true);
        $hasCol = static fn (string $name) => isset($colExists[mb_strtolower($name)]);

        $created = 0;
        $skipped = 0;
        $supplyDate = sprintf('%04d-%02d-20', $year, $month);

        foreach ($validTargets as $staffId) {
            if (isset($existingMap[$staffId])) {
                $skipped++;
                continue;
            }

            $insert = [
                $payrollStaffCol => $staffId,
                'supply_month' => $supplyDate,
                $bonusCol => 0,
                $lockCol => 0,
            ];

            if ($hasCol('raw_payload')) {
                $insert['raw_payload'] = '{}';
            }
            if ($hasCol('payment_total')) {
                $insert['payment_total'] = 0;
            }
            if ($hasCol('salary_total')) {
                $insert['salary_total'] = 0;
            }
            if ($hasCol('pay_total')) {
                $insert['pay_total'] = 0;
            }
            if ($hasCol('deduction_total')) {
                $insert['deduction_total'] = 0;
            }
            if ($hasCol('net_pay')) {
                $insert['net_pay'] = 0;
            }
            if ($hasCol('transfer_amount')) {
                $insert['transfer_amount'] = 0;
            }
            if ($hasCol('bonus_amount')) {
                $insert['bonus_amount'] = 0;
            }
            if ($hasCol('created_at')) {
                $insert['created_at'] = now('Asia/Tokyo');
            }
            if ($hasCol('updated_at')) {
                $insert['updated_at'] = now('Asia/Tokyo');
            }

            DB::connection('sqlsrv_payroll')->table('dbo.mx_kyuyo_shou')->insert($insert);
            $created++;
        }

        return ['message' => 'Processed. created=' . $created . ' skipped=' . $skipped];
    }

    public function deleteMonthlyEntries(array $validated): array
    {
        [$year, $month] = [(int) substr((string) $validated['month'], 0, 4), (int) substr((string) $validated['month'], 5, 2)];
        $companyId = trim((string) ($validated['company_id'] ?? ''));
        $selectedStaffId = trim((string) ($validated['staff_id'] ?? ''));

        $targets = collect((array) ($validated['target_staff_ids'] ?? []))
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->unique()
            ->values()
            ->all();

        if ($targets === [] && $selectedStaffId !== '') {
            $targets = [$selectedStaffId];
        }
        if ($targets === []) {
            return ['message' => 'No target staff selected.'];
        }

        $payrollCols = $this->tableColumns('sqlsrv_payroll', 'mx_kyuyo_shou');
        $payrollIdCol = $this->pickColumn($payrollCols, ['kyuyo_sho_no', 'payroll_entry_id']);
        $payrollStaffCol = $this->pickColumn($payrollCols, ['kyuyo_staff_id', 'staff_code']);
        $bonusCol = $this->pickColumn($payrollCols, ['bonus', 'is_bonus']) ?? 'bonus';
        $lockCol = $this->pickColumn($payrollCols, ['edit_lock', 'is_edit_locked']) ?? 'edit_lock';

        if ($payrollIdCol === null || $payrollStaffCol === null) {
            return ['message' => 'mx_kyuyo_shou required columns not found.'];
        }

        $entryQuery = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where($bonusCol, 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month])
            ->whereIn(DB::raw('LTRIM(RTRIM(' . $payrollStaffCol . '))'), $targets);

        if ($companyId !== '') {
            $staffCols = $this->tableColumns('sqlsrv', 'mx_staffs');
            $staffIdCol = $this->pickColumn($staffCols, ['staff_id', 'staff_code']);
            $staffStoreCol = $this->pickColumn($staffCols, ['section', 'store_code']);
            if ($staffIdCol !== null && $staffStoreCol !== null) {
                $companyStaffIds = DB::connection('sqlsrv')
                    ->table('dbo.mx_staffs as ms')
                    ->leftJoin('dbo.mx_stores as st', 'ms.' . $staffStoreCol, '=', 'st.store_code')
                    ->where('st.company_name', $companyId)
                    ->whereNotNull('ms.' . $staffIdCol)
                    ->selectRaw('DISTINCT LTRIM(RTRIM(ms.' . $staffIdCol . ')) as staff_id')
                    ->pluck('staff_id')
                    ->map(fn ($v) => trim((string) $v))
                    ->filter(fn ($v) => $v !== '')
                    ->values()
                    ->all();

                if ($companyStaffIds === []) {
                    return ['message' => 'No rows found for company target.'];
                }
                $entryQuery->whereIn(DB::raw('LTRIM(RTRIM(' . $payrollStaffCol . '))'), $companyStaffIds);
            }
        }

        $entries = $entryQuery->get([$payrollIdCol . ' as entry_id', $lockCol . ' as lock_flag']);
        if ($entries->isEmpty()) {
            return ['message' => 'No payroll rows found.'];
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
                ->where($payrollIdCol, (int) $entry->entry_id)
                ->delete();
        }

        return ['message' => 'Processed. deleted=' . $deleted . ' skippedLocked=' . $skippedLocked];
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
}

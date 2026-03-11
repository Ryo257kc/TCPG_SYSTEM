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
            return ['message' => '蟇ｾ雎｡繧ｹ繧ｿ繝・ヵ繧帝∈謚槭＠縺ｦ縺上□縺輔＞縲・];
        }

        $staffQuery = DB::connection('sqlsrv')
            ->table('dbo.m_staffs as ms')
            ->leftJoin('dbo.m_stores as st', 'ms.store_code', '=', 'st.store_code')
            ->whereIn(DB::raw('LTRIM(RTRIM(ms.staff_code))'), $targets)
            ->where(function ($q) use ($monthEndDate) {
                $q->whereNull('ms.hire_date')
                    ->orWhereRaw('CONVERT(date, ms.hire_date) <= ?', [$monthEndDate]);
            })
            ->where(function ($q) use ($monthStartDate) {
                $q->whereNull('ms.retire_date')
                    ->orWhereRaw('CONVERT(date, ms.retire_date) >= ?', [$monthStartDate]);
            });
        if ($companyId !== '') {
            $staffQuery->where('st.company_name', $companyId);
        }

        $validTargets = $staffQuery
            ->selectRaw('DISTINCT LTRIM(RTRIM(ms.staff_code)) as staff_id')
            ->pluck('staff_id')
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->values()
            ->all();

        if ($validTargets === []) {
            return ['message' => '蟇ｾ雎｡譛医↓蝨ｨ邀阪☆繧句ｯｾ雎｡繧ｹ繧ｿ繝・ヵ縺瑚ｦ九▽縺九ｊ縺ｾ縺帙ｓ縲・];
        }

        $existingIds = DB::connection('sqlsrv_payroll')
            ->table('dbo.m_payroll_entries')
            ->where('is_bonus', 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month])
            ->whereIn(DB::raw('LTRIM(RTRIM(staff_code))'), $validTargets)
            ->selectRaw('DISTINCT LTRIM(RTRIM(staff_code)) as staff_id')
            ->pluck('staff_id')
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->values()
            ->all();
        $existingMap = array_fill_keys($existingIds, true);

        $tableColumns = $this->tableColumns('sqlsrv_payroll', 'dbo.m_payroll_entries');
        $colExists = array_fill_keys(array_map('mb_strtolower', $tableColumns), true);
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
                'staff_code' => $staffId,
                'supply_month' => $supplyDate,
                'is_bonus' => 0,
                'is_edit_locked' => 0,
            ];
            if ($hasCol('raw_payload')) {
                $insert['raw_payload'] = '{}';
            }
            if ($hasCol('deduction_total')) {
                $insert['deduction_total'] = 0;
            }
            if ($hasCol('pay_total')) {
                $insert['pay_total'] = 0;
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

            DB::connection('sqlsrv_payroll')->table('dbo.m_payroll_entries')->insert($insert);
            $created++;
        }

            return ['message' => 'Processed.'];
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
            return ['message' => '蟇ｾ雎｡繧ｹ繧ｿ繝・ヵ繧帝∈謚槭＠縺ｦ縺上□縺輔＞縲・];
        }

        $entryQuery = DB::connection('sqlsrv_payroll')
            ->table('dbo.m_payroll_entries')
            ->where('is_bonus', 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month])
            ->whereIn(DB::raw('LTRIM(RTRIM(staff_code))'), $targets);

        if ($companyId !== '') {
            $companyStaffIds = DB::connection('sqlsrv')
                ->table('dbo.m_staffs as ms')
                ->leftJoin('dbo.m_stores as st', 'ms.store_code', '=', 'st.store_code')
                ->where('st.company_name', $companyId)
                ->whereNotNull('ms.staff_code')
                ->selectRaw('DISTINCT LTRIM(RTRIM(ms.staff_code)) as staff_id')
                ->pluck('staff_id')
                ->map(fn ($v) => trim((string) $v))
                ->filter(fn ($v) => $v !== '')
                ->values()
                ->all();

            if ($companyStaffIds === []) {
                return ['message' => '謖・ｮ壻ｼ夂､ｾ縺ｮ蟇ｾ雎｡繝・・繧ｿ縺後≠繧翫∪縺帙ｓ縲・];
            }
            $entryQuery->whereIn(DB::raw('LTRIM(RTRIM(staff_code))'), $companyStaffIds);
        }

        $entries = $entryQuery->get(['payroll_entry_id', 'is_edit_locked']);
        if ($entries->isEmpty()) {
            return ['message' => '蜑企勁蟇ｾ雎｡繝・・繧ｿ縺後≠繧翫∪縺帙ｓ縲・];
        }

        $deleted = 0;
        $skippedLocked = 0;
        foreach ($entries as $entry) {
            if ((int) ($entry->is_edit_locked ?? 0) === 1) {
                $skippedLocked++;
                continue;
            }
            $deleted += DB::connection('sqlsrv_payroll')
                ->table('dbo.m_payroll_entries')
                ->where('payroll_entry_id', (int) $entry->payroll_entry_id)
                ->delete();
        }

            return ['message' => 'Processed.'];
    }

    private function tableColumns(string $connection, string $table): array
    {
        try {
            return Schema::connection($connection)->getColumnListing($table);
        } catch (\Throwable) {
            return [];
        }
    }
}

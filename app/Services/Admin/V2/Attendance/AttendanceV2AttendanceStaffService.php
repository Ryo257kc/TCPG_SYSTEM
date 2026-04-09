<?php

namespace App\Services\Admin\V2\Attendance;

use Illuminate\Support\Facades\DB;

class AttendanceV2AttendanceStaffService
{
    /**
     * @return list<array{staff_id:string,staff_name:string,division:string,store_name:string,company_name:string,time_card_keys:list<string>}>
     */
    public function staffs(string $fromDate, string $toDate, string $company): array
    {
        $timeCardKeys = DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards')
            ->selectRaw('LTRIM(RTRIM(staff_name)) as time_card_key')
            ->where('work_date', '>=', $fromDate . ' 00:00:00')
            ->where('work_date', '<', date('Y-m-d 00:00:00', strtotime($toDate . ' +1 day')))
            ->whereNotNull('staff_name')
            ->whereRaw('LTRIM(RTRIM(staff_name)) <> ?', [''])
            ->distinct()
            ->pluck('time_card_key')
            ->map(static fn ($value): string => trim((string) $value))
            ->filter(static fn (string $value): bool => $value !== '')
            ->values()
            ->all();

        if ($timeCardKeys === []) {
            return [];
        }

        $staffMasters = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs as s')
            ->leftJoin('dbo.mx_stores as st', 'st.store_code', '=', 's.section')
            ->leftJoin('dbo.mx_companies as c', 'c.company_id', '=', 'st.company_id')
            ->select([
                's.staff_id',
                's.staff_name',
                's.staff_division',
                'st.store_name',
                'c.company_name',
            ])
            ->whereNotNull('s.staff_id')
            ->whereRaw('LTRIM(RTRIM(s.staff_id)) <> ?', [''])
            ->get()
            ->map(static function ($row): array {
                return [
                    'staff_id' => trim((string) ($row->staff_id ?? '')),
                    'staff_name' => trim((string) ($row->staff_name ?? '')),
                    'division' => trim((string) ($row->staff_division ?? '')),
                    'store_name' => trim((string) ($row->store_name ?? '')),
                    'company_name' => trim((string) ($row->company_name ?? '')),
                ];
            })
            ->filter(static fn (array $row): bool => $row['staff_id'] !== '')
            ->values()
            ->all();

        $staffById = [];
        $staffByName = [];
        foreach ($staffMasters as $staffMaster) {
            $staffById[$staffMaster['staff_id']] ??= $staffMaster;

            if ($staffMaster['staff_name'] !== '') {
                $staffByName[$staffMaster['staff_name']] ??= $staffMaster;
            }
        }

        $grouped = [];
        foreach ($timeCardKeys as $timeCardKey) {
            $staffMaster = $staffById[$timeCardKey] ?? $staffByName[$timeCardKey] ?? null;
            $resolvedStaffId = $staffMaster['staff_id'] ?? $timeCardKey;

            if (!isset($grouped[$resolvedStaffId])) {
                $grouped[$resolvedStaffId] = [
                    'staff_id' => $resolvedStaffId,
                    'staff_name' => $staffMaster['staff_name'] ?? $timeCardKey,
                    'division' => $staffMaster['division'] ?? '',
                    'store_name' => $staffMaster['store_name'] ?? '',
                    'company_name' => $staffMaster['company_name'] ?? '',
                    'time_card_keys' => [],
                ];
            }

            if (!in_array($timeCardKey, $grouped[$resolvedStaffId]['time_card_keys'], true)) {
                $grouped[$resolvedStaffId]['time_card_keys'][] = $timeCardKey;
            }
        }

        $rows = array_values(array_filter(
            $grouped,
            static fn (array $row): bool => $company === '' || $row['company_name'] === $company
        ));

        usort($rows, static function (array $left, array $right): int {
            return [$left['staff_id'], $left['staff_name']] <=> [$right['staff_id'], $right['staff_name']];
        });

        return $rows;
    }
}

<?php

namespace App\Services\Admin\V2\Attendance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AttendanceV2ConfirmedStateService
{
    public function __construct(
        private readonly AttendanceV2AttendanceStaffService $attendanceStaffService,
    ) {
    }

    /**
     * @param list<string> $staffIds
     * @return array<string, bool>
     */
    public function mapByStaffIds(array $staffIds, int $year, int $month): array
    {
        $staffIds = array_values(array_unique(array_filter(array_map(
            static fn ($value) => trim((string) $value),
            $staffIds
        ), static fn (string $value): bool => $value !== '')));

        if ($staffIds === []) {
            return [];
        }

        $result = [];
        foreach ($staffIds as $staffId) {
            $result[$staffId] = false;
        }

        if (!Schema::connection('sqlsrv')->hasColumn('mx_time_cards', 'attendance_checked')) {
            return $result;
        }

        $fromDate = sprintf('%04d-%02d-01', $year, $month);
        $toDate = date('Y-m-t', strtotime($fromDate));
        $staffRows = $this->attendanceStaffService->staffs($fromDate, $toDate, '');

        $keysByStaffId = [];
        foreach ($staffRows as $staffRow) {
            $staffId = trim((string) ($staffRow['staff_id'] ?? ''));
            if ($staffId === '' || !isset($result[$staffId])) {
                continue;
            }

            $keysByStaffId[$staffId] = array_values(array_filter(array_map(
                static fn ($value) => trim((string) $value),
                (array) ($staffRow['time_card_keys'] ?? [])
            ), static fn (string $value): bool => $value !== ''));
        }

        $allKeys = [];
        foreach ($keysByStaffId as $keys) {
            foreach ($keys as $key) {
                $allKeys[$key] = true;
            }
        }

        if ($allKeys === []) {
            return $result;
        }

        $rows = DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards')
            ->selectRaw('LTRIM(RTRIM(staff_name)) as time_card_key')
            ->selectRaw('MIN(CASE WHEN ISNULL(attendance_checked, 0) = 1 THEN 1 ELSE 0 END) as all_checked')
            ->whereRaw('YEAR([work_date]) = ?', [$year])
            ->whereRaw('MONTH([work_date]) = ?', [$month])
            ->whereIn(DB::raw('LTRIM(RTRIM(staff_name))'), array_keys($allKeys))
            ->groupByRaw('LTRIM(RTRIM(staff_name))')
            ->get();

        $checkedByKey = [];
        foreach ($rows as $row) {
            $key = trim((string) ($row->time_card_key ?? ''));
            if ($key === '') {
                continue;
            }

            $checkedByKey[$key] = ((int) ($row->all_checked ?? 0)) === 1;
        }

        foreach ($keysByStaffId as $staffId => $keys) {
            if ($keys === []) {
                continue;
            }

            $allChecked = true;
            foreach ($keys as $key) {
                if (($checkedByKey[$key] ?? false) !== true) {
                    $allChecked = false;
                    break;
                }
            }

            $result[$staffId] = $allChecked;
        }

        return $result;
    }
}

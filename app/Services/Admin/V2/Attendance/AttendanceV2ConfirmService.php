<?php

namespace App\Services\Admin\V2\Attendance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AttendanceV2ConfirmService
{
    public function __construct(
        private readonly AttendanceV2AttendanceStaffService $attendanceStaffService,
    ) {
    }

    public function setConfirmed(string $staffId, int $year, int $month, bool $confirmed, string $confirmedBy = ''): int
    {
        $staffId = trim($staffId);
        if ($staffId === '' || $year < 2000 || $month < 1 || $month > 12) {
            return 0;
        }

        if (!Schema::connection('sqlsrv')->hasColumn('mx_time_cards', 'attendance_checked')) {
            return 0;
        }

        $fromDate = sprintf('%04d-%02d-01', $year, $month);
        $toDate = date('Y-m-t', strtotime($fromDate));
        $staffRows = $this->attendanceStaffService->staffs($fromDate, $toDate, '');

        $timeCardKeys = [];
        foreach ($staffRows as $staffRow) {
            if (trim((string) ($staffRow['staff_id'] ?? '')) !== $staffId) {
                continue;
            }

            $timeCardKeys = array_values(array_filter(array_map(
                static fn ($value) => trim((string) $value),
                (array) ($staffRow['time_card_keys'] ?? [])
            ), static fn (string $value): bool => $value !== ''));
            break;
        }

        if ($timeCardKeys === []) {
            return 0;
        }

        return DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards')
            ->whereRaw('YEAR([work_date]) = ?', [$year])
            ->whereRaw('MONTH([work_date]) = ?', [$month])
            ->whereIn(DB::raw('LTRIM(RTRIM(staff_name))'), $timeCardKeys)
            ->update([
                'attendance_checked' => $confirmed ? 1 : 0,
                'attendance_checked_at' => $confirmed ? now() : null,
                'attendance_checked_by' => $confirmed ? trim($confirmedBy) : null,
            ]);
    }
}

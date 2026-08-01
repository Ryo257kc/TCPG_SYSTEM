<?php

namespace App\Services\Admin\V2\Attendance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AttendanceV2ConfirmService
{
    // 勤怠の確定・解除を担当するサービス。
    // 確定時は有休使用履歴へ登録し、解除時は勤怠由来の有休使用履歴を削除する。
    public function __construct(
        private readonly AttendanceV2AttendanceStaffService $attendanceStaffService,
        private readonly AttendanceV2PaidLeaveUsageService $paidLeaveUsageService,
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
        $timeCardKeys = $this->timeCardKeys($staffId, $fromDate, $toDate);

        if ($timeCardKeys === []) {
            return 0;
        }

        $updated = DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards')
            ->whereRaw('YEAR([work_date]) = ?', [$year])
            ->whereRaw('MONTH([work_date]) = ?', [$month])
            ->whereIn(DB::raw('LTRIM(RTRIM(staff_name))'), $timeCardKeys)
            ->update([
                'attendance_checked' => $confirmed ? 1 : 0,
                'attendance_checked_at' => $confirmed ? now() : null,
                'attendance_checked_by' => $confirmed ? trim($confirmedBy) : null,
            ]);

        if ($updated > 0) {
            $confirmed
                ? $this->paidLeaveUsageService->sync($staffId, $year, $month, $timeCardKeys)
                : $this->paidLeaveUsageService->delete($staffId, $year, $month);
        }

        return $updated;
    }

    private function timeCardKeys(string $staffId, string $fromDate, string $toDate): array
    {
        $staffRows = $this->attendanceStaffService->staffs($fromDate, $toDate, '');

        foreach ($staffRows as $staffRow) {
            if (trim((string) ($staffRow['staff_id'] ?? '')) !== $staffId) {
                continue;
            }

            return array_values(array_filter(array_map(
                static fn ($value) => trim((string) $value),
                (array) ($staffRow['time_card_keys'] ?? [])
            ), static fn (string $value): bool => $value !== ''));
        }

        return [];
    }

}

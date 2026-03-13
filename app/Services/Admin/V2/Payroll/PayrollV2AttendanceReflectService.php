<?php

namespace App\Services\Admin\V2\Payroll;

use App\Services\Admin\V2\Attendance\AttendanceV2AttendanceStaffService;
use App\Services\Admin\V2\Attendance\AttendanceV2BulkReflectService;

class PayrollV2AttendanceReflectService
{
    public function __construct(
        private readonly AttendanceV2AttendanceStaffService $attendanceStaffService,
        private readonly AttendanceV2BulkReflectService $bulkReflectService,
    ) {
    }

    public function reflect(string $staffId, int $year, int $month): int
    {
        $staffId = trim($staffId);
        if ($staffId === '' || $year < 2000 || $month < 1 || $month > 12) {
            return 0;
        }

        [$attendanceYear, $attendanceMonth] = $this->previousYearMonth($year, $month);
        $fromDate = sprintf('%04d-%02d-01', $attendanceYear, $attendanceMonth);
        $toDate = date('Y-m-t', strtotime($fromDate));
        $staffRows = $this->attendanceStaffService->staffs($fromDate, $toDate, '');

        $targetRows = array_values(array_filter(
            $staffRows,
            static fn (array $row): bool => trim((string) ($row['staff_id'] ?? '')) === $staffId
        ));

        if ($targetRows === []) {
            return 0;
        }

        $result = $this->bulkReflectService->reflect($targetRows, $attendanceYear, $attendanceMonth);

        return (int) ($result['updated'] ?? 0);
    }

    /** @return array{0:int,1:int} */
    private function previousYearMonth(int $year, int $month): array
    {
        if ($month <= 1) {
            return [$year - 1, 12];
        }

        return [$year, $month - 1];
    }
}

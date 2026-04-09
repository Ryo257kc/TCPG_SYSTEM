<?php

namespace App\Services\Admin\V2\Attendance;

use Illuminate\Support\Facades\DB;

class AttendanceV2ShiftCandidatesService
{
    public function __construct(
        private readonly AttendanceV2StaffService $staffService,
    ) {
    }

    /**
     * @return list<array{staff_id:string,staff_name:string,division:string,store_name:string,company_name:string,existing:bool}>
     */
    public function candidates(int $year, int $month, string $companyName = ''): array
    {
        if ($year < 2000 || $month < 1 || $month > 12) {
            return [];
        }

        $fromDate = sprintf('%04d-%02d-01', $year, $month);
        $toDate = date('Y-m-t', strtotime($fromDate));

        $staffRows = $this->staffService->staffs($fromDate, $toDate, trim($companyName));
        $existingMap = $this->existingStaffMap($fromDate, $toDate);

        return array_values(array_map(
            static fn (array $row): array => [
                'staff_id' => $row['staff_id'],
                'staff_name' => $row['staff_name'],
                'division' => $row['division'],
                'store_name' => $row['store_name'],
                'company_name' => $row['company_name'],
                'existing' => isset($existingMap[$row['staff_id']]),
            ],
            $staffRows
        ));
    }

    /** @return array<string,true> */
    private function existingStaffMap(string $fromDate, string $toDate): array
    {
        $ids = DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards')
            ->where('work_date', '>=', $fromDate . ' 00:00:00')
            ->where('work_date', '<', date('Y-m-d 00:00:00', strtotime($toDate . ' +1 day')))
            ->pluck('staff_name')
            ->map(static fn ($id): string => trim((string) $id))
            ->filter(static fn (string $id): bool => $id !== '')
            ->unique()
            ->values()
            ->all();

        return array_fill_keys($ids, true);
    }
}

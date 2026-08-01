<?php

namespace App\Services\Admin\V2\Attendance;

use Illuminate\Support\Facades\DB;

class AttendanceV2ShiftDeleteService
{
    // 未打刻のシフト予定行を削除するサービス。
    // 実打刻がある勤怠行は削除しない。
    /**
     * @param list<string> $staffIds
     * @return array{deleted:int,skipped:int,deleted_ids:list<string>,skipped_ids:list<string>}
     */
    public function delete(int $year, int $month, array $staffIds): array
    {
        $result = [
            'deleted' => 0,
            'skipped' => 0,
            'deleted_ids' => [],
            'skipped_ids' => [],
        ];

        $staffIds = array_values(array_unique(array_filter(array_map(
            static fn($id): string => trim((string) $id),
            $staffIds
        ), static fn(string $id): bool => $id !== '')));

        if ($staffIds === [] || $year < 2000 || $month < 1 || $month > 12) {
            return $result;
        }

        [$fromDate, $toDate] = $this->monthRange($year, $month);

        foreach ($staffIds as $staffId) {
            $query = DB::connection('sqlsrv')
                ->table('dbo.mx_time_cards')
                ->whereRaw('LTRIM(RTRIM(staff_name)) = ?', [$staffId])
                ->where('work_date', '>=', $fromDate)
                ->where('work_date', '<', $toDate);

            if (!$query->exists()) {
                $result['skipped']++;
                $result['skipped_ids'][] = $staffId;
                continue;
            }

            if ($this->hasActualPunch($staffId, $fromDate, $toDate)) {
                $result['skipped']++;
                $result['skipped_ids'][] = $staffId;
                continue;
            }

            $deleted = (clone $query)->delete();
            if ($deleted > 0) {
                $result['deleted']++;
                $result['deleted_ids'][] = $staffId;
            } else {
                $result['skipped']++;
                $result['skipped_ids'][] = $staffId;
            }
        }

        return $result;
    }

    /** @return array{0:string,1:string} */
    private function monthRange(int $year, int $month): array
    {
        $fromDate = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $toDate = date('Y-m-01 00:00:00', strtotime(substr($fromDate, 0, 10) . ' +1 month'));

        return [$fromDate, $toDate];
    }

    private function hasActualPunch(string $staffId, string $fromDate, string $toDate): bool
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards')
            ->whereRaw('LTRIM(RTRIM(staff_name)) = ?', [$staffId])
            ->whereBetween('work_date', [$fromDate, $toDate])
            ->where(function ($q) {
                $columns = [
                    'actual_start',
                    'actual_leave',
                    'actual_break_out',
                    'actual_end',
                    'manager_approval',
                    'staff_request',
                ];

                foreach ($columns as $index => $column) {
                    if ($index === 0) {
                        $q->whereNotNull($column);
                    } else {
                        $q->orWhereNotNull($column);
                    }
                }
            })
            ->exists();
    }
}

<?php

namespace App\Services\Admin\V2\Attendance;

use Illuminate\Support\Facades\DB;

class AttendanceV2MetricService
{
    /**
     * @return array<string, array{attendance_checked:bool,work_in_num:float,absence_num:float,work_holiday_num:float,work_time:float,holiday_true:float,overtime:float,night_over_time:float}>
     */
    public function metricMap(int $year, int $month): array
    {
        $rows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month])
            ->select([
                'kyuyo_sho_no',
                'kyuyo_staff_id',
                'attendance_checked',
                'work_in_num',
                'absence_num',
                'work_horiday_num as work_holiday_num',
                'work_time',
                'horiday_true as holiday_true',
                'overtime',
                'night_over_time',
            ])
            ->orderBy('kyuyo_sho_no', 'desc')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $staffId = trim((string) ($row->kyuyo_staff_id ?? ''));
            if ($staffId === '' || isset($map[$staffId])) {
                continue;
            }

            $map[$staffId] = [
                'attendance_checked' => ((int) ($row->attendance_checked ?? 0)) === 1,
                'work_in_num' => (float) ($row->work_in_num ?? 0),
                'absence_num' => (float) ($row->absence_num ?? 0),
                'work_holiday_num' => (float) ($row->work_holiday_num ?? 0),
                'work_time' => (float) ($row->work_time ?? 0),
                'holiday_true' => (float) ($row->holiday_true ?? 0),
                'overtime' => (float) ($row->overtime ?? 0),
                'night_over_time' => (float) ($row->night_over_time ?? 0),
            ];
        }

        return $map;
    }

    /**
     * @param list<array{staff_id:string,staff_name:string,division:string,store_name:string,company_name:string}> $staffRows
     * @param array<string, array{attendance_checked:bool,work_in_num:float,absence_num:float,work_holiday_num:float,work_time:float,holiday_true:float,overtime:float,night_over_time:float}> $metricMap
     * @return list<array{staff_id:string,staff_name:string,division:string,store_name:string,company_name:string,metrics:array{attendance_checked:bool,work_in_num:float,absence_num:float,work_holiday_num:float,work_time:float,holiday_true:float,overtime:float,night_over_time:float}}>
     */
    public function mergeRows(array $staffRows, array $metricMap, string $selectedStaffId): array
    {
        $defaultMetric = [
            'attendance_checked' => false,
            'work_in_num' => 0.0,
            'absence_num' => 0.0,
            'work_holiday_num' => 0.0,
            'work_time' => 0.0,
            'holiday_true' => 0.0,
            'overtime' => 0.0,
            'night_over_time' => 0.0,
        ];

        $rows = [];
        foreach ($staffRows as $staff) {
            if ($selectedStaffId !== '' && $selectedStaffId !== $staff['staff_id']) {
                continue;
            }

            $rows[] = [
                'staff_id' => $staff['staff_id'],
                'staff_name' => $staff['staff_name'],
                'division' => $staff['division'],
                'store_name' => $staff['store_name'],
                'company_name' => $staff['company_name'],
                'metrics' => $metricMap[$staff['staff_id']] ?? $defaultMetric,
            ];
        }

        return $rows;
    }
}

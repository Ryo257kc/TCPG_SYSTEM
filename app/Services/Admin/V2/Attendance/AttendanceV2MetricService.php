<?php

namespace App\Services\Admin\V2\Attendance;

use Illuminate\Support\Facades\DB;

class AttendanceV2MetricService
{
    public function __construct(
        private readonly AttendanceV2PayrollTargetService $payrollTargetService,
    ) {
    }

    /**
     * @return array<string, array{work_in_num:float,absence_num:float,work_holiday_num:float,work_time:float,holiday_true:float,overtime:float,night_over_time:float}>
     */
    public function metricMap(int $year, int $month): array
    {
        $paymentDate = $this->payrollTargetService->resolvePaymentDate($year, $month);
        if ($paymentDate === '') {
            return [];
        }

        $rows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 0)
            ->whereRaw('CONVERT(date, [supply_month]) = ?', [$paymentDate])
            ->select([
                'kyuyo_sho_no',
                'kyuyo_staff_id',
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
     * @return array<string, array{holiday_work_time:float,late_early_time:float}>
     */
    public function categoryTimeMap(int $year, int $month): array
    {
        $rows = DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards')
            ->select([
                DB::raw('LTRIM(RTRIM(staff_name)) as time_card_key'),
                'work_type',
                'work_type_time',
            ])
            ->whereRaw('YEAR([work_date]) = ?', [$year])
            ->whereRaw('MONTH([work_date]) = ?', [$month])
            ->whereNotNull('staff_name')
            ->whereRaw('LTRIM(RTRIM(staff_name)) <> ?', [''])
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $timeCardKey = trim((string) ($row->time_card_key ?? ''));
            if ($timeCardKey === '') {
                continue;
            }

            $workType = trim((string) ($row->work_type ?? ''));
            $workTypeTime = (float) ($row->work_type_time ?? 0);

            $map[$timeCardKey] ??= [
                'holiday_work_time' => 0.0,
                'late_early_time' => 0.0,
            ];

            if ($workType === '休出') {
                $map[$timeCardKey]['holiday_work_time'] += $workTypeTime;
            }

            if (in_array($workType, ['遅刻', '早退', '遅早'], true)) {
                $map[$timeCardKey]['late_early_time'] += $workTypeTime;
            }
        }

        return $map;
    }

    /**
     * @param list<array{staff_id:string,staff_name:string,division:string,store_name:string,company_name:string,time_card_keys:list<string>}> $staffRows
     * @param array<string, array{work_in_num:float,absence_num:float,work_holiday_num:float,work_time:float,holiday_true:float,overtime:float,night_over_time:float}> $metricMap
     * @param array<string, array{holiday_work_time:float,late_early_time:float}> $categoryTimeMap
     * @return list<array{staff_id:string,staff_name:string,division:string,store_name:string,company_name:string,metrics:array{attendance_checked:bool,work_in_num:float,absence_num:float,work_holiday_num:float,work_time:float,holiday_true:float,overtime:float,night_over_time:float,holiday_work_time:float,late_early_time:float}}>
     */
    public function mergeRows(array $staffRows, array $metricMap, array $categoryTimeMap, string $selectedStaffId): array
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
            'holiday_work_time' => 0.0,
            'late_early_time' => 0.0,
        ];

        $rows = [];
        foreach ($staffRows as $staff) {
            if ($selectedStaffId !== '' && $selectedStaffId !== $staff['staff_id']) {
                continue;
            }

            $categoryTotals = [
                'holiday_work_time' => 0.0,
                'late_early_time' => 0.0,
            ];
            foreach ((array) ($staff['time_card_keys'] ?? []) as $timeCardKey) {
                $timeCardKey = trim((string) $timeCardKey);
                if ($timeCardKey === '' || !isset($categoryTimeMap[$timeCardKey])) {
                    continue;
                }

                $categoryTotals['holiday_work_time'] += (float) ($categoryTimeMap[$timeCardKey]['holiday_work_time'] ?? 0);
                $categoryTotals['late_early_time'] += (float) ($categoryTimeMap[$timeCardKey]['late_early_time'] ?? 0);
            }

            $rows[] = [
                'staff_id' => $staff['staff_id'],
                'staff_name' => $staff['staff_name'],
                'division' => $staff['division'],
                'store_name' => $staff['store_name'],
                'company_name' => $staff['company_name'],
                'metrics' => array_merge($defaultMetric, $metricMap[$staff['staff_id']] ?? [], $categoryTotals),
            ];
        }

        return $rows;
    }
}

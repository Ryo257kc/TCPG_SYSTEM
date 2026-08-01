<?php

namespace App\Services\Admin\V2\Attendance;

class AttendanceV2MetricService
{
    // 勤怠一覧の行へ月次集計を載せる表示用サービス。
    // 計算式は持たず、AttendanceV2MonthlySummaryService の結果をスタッフ単位にまとめるだけ。
    public function __construct(
        private readonly AttendanceV2PayrollTargetService $payrollTargetService,
        private readonly AttendanceV2MonthlySummaryService $monthlySummaryService,
    ) {}

    public function metricMap(int $year, int $month): array
    {
        // 勤怠一覧の数値は月次集計サービスを正とする。
        return $this->monthlySummaryService->summaryMapByTimeCardKey($year, $month);
    }

    public function categoryTimeMap(int $year, int $month): array
    {
        return [];
    }

    public function mergeRows(array $staffRows, array $metricMap, array $categoryTimeMap, string $selectedStaffId): array
    {
        $rows = [];

        foreach ($staffRows as $staff) {
            if ($selectedStaffId !== '' && $selectedStaffId !== $staff['staff_id']) {
                continue;
            }

            $metric = $this->emptyMetric();

            foreach ((array) ($staff['time_card_keys'] ?? []) as $timeCardKey) {
                $timeCardKey = trim((string) $timeCardKey);
                if ($timeCardKey === '' || !isset($metricMap[$timeCardKey])) {
                    continue;
                }

                $metric = $this->addMetric($metric, $metricMap[$timeCardKey]);
            }

            $rows[] = [
                'staff_id' => $staff['staff_id'],
                'staff_name' => $staff['staff_name'],
                'division' => $staff['division'],
                'store_name' => $staff['store_name'],
                'company_name' => $staff['company_name'],
                'metrics' => $metric,
            ];
        }

        return $rows;
    }

    private function emptyMetric(): array
    {
        return $this->monthlySummaryService->emptySummary();
    }

    private function addMetric(array $base, array $add): array
    {
        // 複数タイムカードキーの合算も月次集計サービスの合算ルールを使う。
        return $this->monthlySummaryService->addSummaries($base, $add);
    }
}

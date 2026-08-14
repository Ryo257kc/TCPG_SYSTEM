<?php

namespace App\Services\Admin\V2\Attendance;

use App\Services\Admin\V2\PaidLeave\PaidLeaveV2RemainingService;
use Illuminate\Support\Facades\DB;

class AttendanceV2BulkReflectService
{
    // 勤怠管理の月次集計を給与明細へ保存するサービス。
    // 保存する数値は AttendanceV2MonthlySummaryService::payrollPayload() を使う。
    public function __construct(
        private readonly AttendanceV2PayrollTargetService $payrollTargetService,
        private readonly AttendanceV2MonthlySummaryService $monthlySummaryService,
        private readonly PaidLeaveV2RemainingService $paidLeaveRemainingService,
        private readonly AttendanceV2PaidLeaveUsageService $paidLeaveUsageService,
    ) {
    }

    /**
     * 勤怠管理の月次集計を給与明細へ反映する。
     *
     * 勤怠の計算式は AttendanceV2MonthlySummaryService に集約し、
     * このクラスは対象スタッフの特定と給与テーブルへの保存だけを担当する。
     *
     * @param list<array{staff_id:string,time_card_keys:list<string>}> $staffRows
     * @return array{updated:int,locked:int,missing:int,recalculated:int}
     */
    public function reflect(array $staffRows, int $year, int $month): array
    {
        if ($year < 2000 || $month < 1 || $month > 12 || $staffRows === []) {
            return ['updated' => 0, 'locked' => 0, 'missing' => 0, 'recalculated' => 0];
        }

        [$targetYear, $targetMonth] = $this->payrollTargetService->targetPayrollYearMonth($year, $month);

        $timeCardKeyToStaffId = [];
        $staffTimeCardKeys = [];
        $staffIds = [];
        foreach ($staffRows as $staffRow) {
            $staffId = trim((string) ($staffRow['staff_id'] ?? ''));
            if ($staffId === '') {
                continue;
            }

            $staffIds[$staffId] = true;
            $staffTimeCardKeys[$staffId] = [];
            foreach ((array) ($staffRow['time_card_keys'] ?? []) as $timeCardKey) {
                $timeCardKey = trim((string) $timeCardKey);
                if ($timeCardKey !== '') {
                    $timeCardKeyToStaffId[$timeCardKey] = $staffId;
                    $staffTimeCardKeys[$staffId][] = $timeCardKey;
                }
            }
        }

        if ($timeCardKeyToStaffId === [] || $staffIds === []) {
            return ['updated' => 0, 'locked' => 0, 'missing' => 0, 'recalculated' => 0];
        }

        $summaryMap = $this->monthlySummaryService->summaryMapByTimeCardKey($year, $month, array_keys($timeCardKeyToStaffId));
        $summaries = [];
        foreach ($summaryMap as $timeCardKey => $timeCardSummary) {
            $staffId = $timeCardKeyToStaffId[$timeCardKey] ?? '';
            if ($staffId === '') {
                continue;
            }

            $summaries[$staffId] ??= $this->monthlySummaryService->emptySummary();
            $summaries[$staffId] = $this->monthlySummaryService->addSummaries($summaries[$staffId], $timeCardSummary);
        }

        $payrollRows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$targetYear])
            ->whereRaw('MONTH([supply_month]) = ?', [$targetMonth])
            ->whereIn(DB::raw('LTRIM(RTRIM([kyuyo_staff_id]))'), array_keys($staffIds))
            ->get(['kyuyo_sho_no', 'kyuyo_staff_id', 'edit_lock']);
        $payrollRowsByStaffId = [];
        foreach ($payrollRows as $payrollRow) {
            $staffId = trim((string) ($payrollRow->kyuyo_staff_id ?? ''));
            if ($staffId === '') {
                continue;
            }
            $payrollRowsByStaffId[$staffId] ??= [];
            $payrollRowsByStaffId[$staffId][] = $payrollRow;
        }

        $updated = 0;
        $locked = 0;
        $missing = 0;

        // 元の実装では、未ロックの給与行が1件も無いスタッフ（対象給与行が無い／全てロック済み）は
        // 有給同期・残日数取得の対象外だった。一括化するにあたっても同じスタッフ集合だけを対象にする。
        $unlockedRowsByStaffId = [];
        foreach (array_keys($staffIds) as $staffId) {
            $staffPayrollRows = $payrollRowsByStaffId[$staffId] ?? [];
            if ($staffPayrollRows === []) {
                $missing++;
                continue;
            }

            $unlockedPayrollRows = [];
            foreach ($staffPayrollRows as $payrollRow) {
                if (((int) ($payrollRow->edit_lock ?? 0)) === 1) {
                    $locked++;
                    continue;
                }

                $unlockedPayrollRows[] = $payrollRow;
            }

            if ($unlockedPayrollRows !== []) {
                $unlockedRowsByStaffId[$staffId] = $unlockedPayrollRows;
            }
        }

        // スタッフ単位で有給消化を都度同期・残日数取得していたのをまとめて実施する
        // （sync()とremainingDays()の単体呼び出しと同じ結果になるよう、集計ロジックは
        // AttendanceV2PaidLeaveUsageService::syncBulk()／PaidLeaveV2RemainingService::remainingDaysBulk()側で再現している）。
        $eligibleStaffIds = array_keys($unlockedRowsByStaffId);
        $eligibleTimeCardKeys = array_intersect_key($staffTimeCardKeys, $unlockedRowsByStaffId);
        $this->paidLeaveUsageService->syncBulk($eligibleTimeCardKeys, $year, $month);
        $remainingDaysByStaffId = $this->paidLeaveRemainingService->remainingDaysBulk($eligibleStaffIds);

        foreach ($unlockedRowsByStaffId as $staffId => $unlockedPayrollRows) {
            $summary = $summaries[$staffId] ?? $this->monthlySummaryService->emptySummary();
            $payload = $this->monthlySummaryService->payrollPayload($summary);
            $payload['horiday_true_num'] = $remainingDaysByStaffId[$staffId] ?? 0.0;

            foreach ($unlockedPayrollRows as $payrollRow) {
                if (!isset($payrollRow->kyuyo_sho_no)) {
                    continue;
                }

                DB::connection('sqlsrv_payroll')
                    ->table('dbo.mx_kyuyo_shou')
                    ->where('kyuyo_sho_no', (int) $payrollRow->kyuyo_sho_no)
                    ->update($payload);
                $updated++;
            }
        }

        return [
            'updated' => $updated,
            'locked' => $locked,
            'missing' => $missing,
            'recalculated' => 0,
        ];
    }
}

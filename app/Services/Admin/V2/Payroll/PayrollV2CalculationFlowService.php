<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;

/**
 * 給与計算ボタンの通り道を1本化するサービス。
 *
 * 個別の計算式は各正本サービスに置き、このクラスは呼び出し順だけを管理する。
 * 給与計算の全体順を確認したいときは、まずこのファイルを見る。
 */
class PayrollV2CalculationFlowService
{
    public function __construct(
        private readonly PayrollV2RecalculateService $recalculateService,
        private readonly PayrollV2OvertimeDeductionService $overtimeDeductionService,
        private readonly PayrollV2EmploymentInsuranceService $employmentInsuranceService,
        private readonly PayrollV2IncomeTaxService $incomeTaxService,
        private readonly PayrollV2UpdateService $updateService,
        private readonly PayrollV2FuyoService $fuyoService,
        private readonly PayrollV2BonusSocialInsuranceService $bonusSocialInsuranceService,
        private readonly PayrollV2BonusIncomeTaxCalcService $bonusIncomeTaxCalcService,
    ) {}

    /**
     * 月給の給与計算を最初から最後まで通す正規ルート。
     *
     * 1. 基本情報・交通費・社保・住民税・扶養・有休残
     * 2. 残業・深夜・休日・遅早・欠勤控除
     * 3. 雇用保険
     * 4. 所得税
     * 5. 最終合計
     */
    public function recalculateMonthly(string $staffId, int $year, int $month, ?string $companyName = null): int
    {
        if ($this->isOutsource($staffId)) {
            return (int) $this->recalculateService->recalculatePayrollMasterOnly($staffId, $year, $month, $companyName);
        }

        $updated = 0;
        $updated += (int) $this->recalculateService->recalculate($staffId, $year, $month, $companyName);
        $updated += (int) $this->overtimeDeductionService->recalculate($staffId, $year, $month, $companyName);
        $updated += (int) $this->updateService->refreshTotals($staffId, $year, $month, $companyName);
        $updated += (int) $this->employmentInsuranceService->recalculate($staffId, $year, $month);
        $updated += (int) $this->updateService->refreshTotals($staffId, $year, $month, $companyName);
        $updated += (int) $this->incomeTaxService->recalculate($staffId, $year, $month);
        $updated += (int) $this->updateService->refreshTotals($staffId, $year, $month, $companyName);

        return $updated;
    }

    /**
     * 勤怠や一部項目を更新した後、金額系だけを正規順で再計算する。
     *
     * 勤怠反映自体は呼び出し元で行い、その後にこのメソッドを通す。
     */
    public function recalculateAmountsAfterInputChange(string $staffId, int $year, int $month, ?string $companyName = null): int
    {
        if ($this->isOutsource($staffId)) {
            return (int) $this->updateService->refreshTotals($staffId, $year, $month, $companyName);
        }

        $updated = 0;
        $updated += (int) $this->overtimeDeductionService->recalculate($staffId, $year, $month, $companyName);
        $updated += (int) $this->updateService->refreshTotals($staffId, $year, $month, $companyName);
        $updated += (int) $this->employmentInsuranceService->recalculate($staffId, $year, $month);
        $updated += (int) $this->updateService->refreshTotals($staffId, $year, $month, $companyName);
        $updated += (int) $this->incomeTaxService->recalculate($staffId, $year, $month);
        $updated += (int) $this->updateService->refreshTotals($staffId, $year, $month, $companyName);

        return $updated;
    }

    /**
     * 雇用保険だけを再計算した後、必ず最終合計へ反映するルート。
     */
    public function recalculateAfterAttendanceReflect(string $staffId, int $year, int $month, ?string $companyName = null): int
    {
        $updated = 0;
        $updated += (int) $this->recalculateService->refreshBasicSalaryFromAttendance($staffId, $year, $month, $companyName);
        if ($this->isOutsource($staffId)) {
            $updated += (int) $this->updateService->refreshTotals($staffId, $year, $month, $companyName);

            return $updated;
        }

        $updated += (int) $this->recalculateAmountsAfterInputChange($staffId, $year, $month, $companyName);

        return $updated;
    }

    public function recalculateEmploymentInsurance(string $staffId, int $year, int $month, ?string $companyName = null): int
    {
        if ($this->isOutsource($staffId)) {
            return (int) $this->updateService->refreshTotals($staffId, $year, $month, $companyName);
        }

        $updated = 0;
        $updated += (int) $this->updateService->refreshTotals($staffId, $year, $month, $companyName);
        $updated += (int) $this->employmentInsuranceService->recalculate($staffId, $year, $month);
        $updated += (int) $this->updateService->refreshTotals($staffId, $year, $month, $companyName);

        return $updated;
    }

    /**
     * 所得税だけを再計算した後、必ず最終合計へ反映するルート。
     *
     * 画面の確認用 trace を返すため、所得税サービスの trace も保持する。
     *
     * @return array{updated:int,trace:array<string,mixed>}
     */
    public function recalculateIncomeTaxWithTrace(string $staffId, int $year, int $month, ?string $companyName = null): array
    {
        if ($this->isOutsource($staffId)) {
            $updated = (int) $this->updateService->refreshTotals($staffId, $year, $month, $companyName);

            return [
                'updated' => $updated,
                'trace' => ['mode' => 'excluded-gyomu-itaku'],
            ];
        }

        $updated = (int) $this->updateService->refreshTotals($staffId, $year, $month, $companyName);
        $result = $this->incomeTaxService->recalculateWithTrace($staffId, $year, $month);
        $updated += (int) ($result['updated'] ?? 0);
        $updated += (int) $this->updateService->refreshTotals($staffId, $year, $month, $companyName);

        return [
            'updated' => $updated,
            'trace' => (array) ($result['trace'] ?? []),
        ];
    }

    /**
     * 賞与計算を最初から最後まで通す正規ルート。
     *
     * 1. 扶養人数
     * 2. 賞与雇用保険
     * 3. 賞与社保
     * 4. 賞与所得税
     * 5. 賞与最終合計
     */
    public function recalculateBonus(string $staffId, int $year, int $month, string $paymentDate): int
    {
        $updated = 0;
        $updated += (int) $this->updateService->saveBonus($staffId, $year, $month, [
            'fuyo_sum' => $this->fuyoService->resolveByPaymentDate($staffId, $paymentDate),
        ]);
        if ($this->isOutsource($staffId)) {
            $updated += (int) $this->updateService->refreshBonusTotals($staffId, $year, $month);

            return $updated;
        }

        $updated += (int) $this->employmentInsuranceService->recalculateBonus($staffId, $paymentDate);
        $updated += (int) $this->bonusSocialInsuranceService->recalculate($staffId, $paymentDate);
        $updated += (int) $this->bonusIncomeTaxCalcService->recalculate($staffId, $paymentDate);
        $updated += (int) $this->updateService->refreshBonusTotals($staffId, $year, $month);

        return $updated;
    }

    private function isOutsource(string $staffId): bool
    {
        $staffId = trim($staffId);
        if ($staffId === '') {
            return false;
        }

        $division = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->whereRaw('LTRIM(RTRIM([staff_id])) = ?', [$staffId])
            ->value('staff_division');

        return str_contains(trim((string) $division), '業務委託');
    }
}

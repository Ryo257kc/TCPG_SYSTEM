<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;

/**
 * 給与・賞与の mx_kyuyo_shou サマリー行の取得・正規化を担当する。
 * 「振込額（差引支給額）」の計算式はここ(transferAmount)を正本とし、
 * Controller・帳票・スタッフポータルはすべてここの値を使う。式を個別に持たない。
 */
class PayrollV2SummaryService
{
    /** @return array<string, array<string, mixed>> */
    public function summaryMapByPaymentDate(string $paymentDate, bool $bonus = false): array
    {
        if (!$this->isValidPaymentDate($paymentDate)) {
            return [];
        }

        $rows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', $bonus ? 1 : 0)
            ->whereRaw('CONVERT(date, [supply_month]) = ?', [$paymentDate])
            ->orderBy('kyuyo_sho_no', 'desc')
            ->get();

        return $this->toMap($rows);
    }

    /** @return array<string, array<string, mixed>> */
    public function previousSummaryMapByPaymentDate(string $paymentDate, bool $bonus = false): array
    {
        $previousPaymentDate = $this->previousPaymentDate($paymentDate, $bonus);
        if ($previousPaymentDate === '') {
            return [];
        }

        return $this->summaryMapByPaymentDate($previousPaymentDate, $bonus);
    }

    /**
     * @param \Illuminate\Support\Collection<int, object> $rows
     * @return array<string, array<string, mixed>>
     */
    private function toMap($rows): array
    {
        $map = [];
        foreach ($rows as $row) {
            $staffId = trim((string) ($row->kyuyo_staff_id ?? ''));
            if ($staffId === '' || isset($map[$staffId])) {
                continue;
            }

            $map[$staffId] = $this->normalizeSummaryRow((array) $row);
        }

        return $map;
    }

    private function previousPaymentDate(string $paymentDate, bool $bonus = false): string
    {
        if (!$this->isValidPaymentDate($paymentDate)) {
            return '';
        }

        $dates = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->selectRaw('CONVERT(date, [supply_month]) as payment_date')
            ->where('bonus', $bonus ? 1 : 0)
            ->whereNotNull('supply_month')
            ->groupByRaw('CONVERT(date, [supply_month])')
            ->orderByRaw('CONVERT(date, [supply_month]) desc')
            ->get()
            ->map(static function ($row): string {
                $value = trim((string) ($row->payment_date ?? ''));
                return $value === '' ? '' : date('Y-m-d', strtotime($value));
            })
            ->filter(static fn (string $value): bool => $value !== '')
            ->values()
            ->all();

        $index = array_search($paymentDate, $dates, true);
        if ($index === false || !isset($dates[$index + 1])) {
            return '';
        }

        return $dates[$index + 1];
    }

    private function isValidPaymentDate(string $paymentDate): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($paymentDate)) === 1;
    }

    /**
     * @param list<array{staff_id:string,staff_name:string,division:string,store_code:string,store_name:string,company_name:string}> $staffRows
     * @param array<string, array<string, mixed>> $summaryMap
     * @param array<string, array<string, mixed>> $previousSummaryMap
     * @param array<string, array<string, mixed>> $kihonMap
     * @param array<string, array<string, mixed>> $staffMasterMap
     * @param array<string, array<string, mixed>> $shahoMap
     * @param array<string, array<string, mixed>> $residentMap
     * @return list<array{staff_id:string,staff_name:string,division:string,store_code:string,store_name:string,company_name:string,summary:array<string, mixed>,summary_prev:array<string, mixed>,kihon:array<string, mixed>,staff_master:array<string, mixed>,shaho:array<string, mixed>,resident:array<string, mixed>}>
     */
    public function mergeRows(
        array $staffRows,
        array $summaryMap,
        array $previousSummaryMap,
        array $kihonMap,
        array $staffMasterMap,
        array $shahoMap,
        array $residentMap,
        string $selectedStaffId
    ): array {
        $rows = [];
        foreach ($staffRows as $staff) {
            if ($selectedStaffId !== '' && $selectedStaffId !== $staff['staff_id']) {
                continue;
            }

            $rows[] = [
                'staff_id' => $staff['staff_id'],
                'staff_name' => $staff['staff_name'],
                'division' => $staff['division'],
                'store_code' => $staff['store_code'] ?? '',
                'store_name' => $staff['store_name'],
                'company_name' => $staff['company_name'],
                'summary' => $summaryMap[$staff['staff_id']] ?? [],
                'summary_prev' => $previousSummaryMap[$staff['staff_id']] ?? [],
                'kihon' => $kihonMap[$staff['staff_id']] ?? [],
                'staff_master' => $staffMasterMap[$staff['staff_id']] ?? [],
                'shaho' => $shahoMap[$staff['staff_id']] ?? [],
                'resident' => $residentMap[$staff['staff_id']] ?? [],
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeSummaryRow(array $row): array
    {
        $aliases = [
            'work_holiday_num' => 'work_horiday_num',
            'holiday_true' => 'horiday_true',
            'holiday_true_num' => 'horiday_true_num',
        ];

        foreach ($aliases as $canonical => $legacy) {
            if (!array_key_exists($canonical, $row) && array_key_exists($legacy, $row)) {
                $row[$canonical] = $row[$legacy];
            }
        }

        // 差引支給額は保存値（supply_deduction_sum）をそのまま表示する。帳票側で計算し直さない
        // （PayrollV2UpdateService::rebuildTotals()/rebuildBonusTotals()の保存時にtransferAmount()で
        // 確定・保存済み。ここでは読むだけ）。
        $row['transfer_amount'] = $this->toFloat($row['supply_deduction_sum'] ?? 0);

        return $row;
    }

    /**
     * 振込額（差引支給額）の正本計算。保存処理（PayrollV2UpdateService）だけが呼ぶ。
     * 支給合計 - 控除合計 - 年末調整 + 立替経費精算 + 会社立替費用 - 振込残額。
     * `docs/rules/payroll_bonus/03_wage_ledger.md` の規定式と一致させること。
     * 表示側（Controller・帳票・スタッフポータル）はこれを直接呼ばず、保存済みの
     * supply_deduction_sumを読むこと（「帳票側で再計算しない」というルールのため）。
     * @param array<string, mixed> $summary
     */
    public function transferAmount(array $summary): float
    {
        return $this->toFloat($summary['supply_sum'] ?? 0)
            - $this->toFloat($summary['deduction_sum'] ?? 0)
            - $this->toFloat($summary['adjustment_year_end'] ?? 0)
            + $this->toFloat($summary['cost_liquidation'] ?? 0)
            + $this->toFloat($summary['company_advance_cost'] ?? 0)
            - $this->toFloat($summary['transfer_balance'] ?? 0);
    }

    private function toFloat(mixed $value): float
    {
        if ($value === null) {
            return 0.0;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $text = str_replace([',', ' '], '', trim((string) $value));
        return $text !== '' && is_numeric($text) ? (float) $text : 0.0;
    }
}

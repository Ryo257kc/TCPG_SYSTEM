<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;

/**
 * 賞与所得税計算。
 * 月額表(甲欄・乙欄)の税額計算式そのものはPayrollV2IncomeTaxServiceを正本とし、
 * ここでは持たない(月割換算・賞与用の税率表など賞与固有のロジックだけを持つ)。
 */
class PayrollV2BonusIncomeTaxCalcService
{
    public function __construct(
        private readonly PayrollV2IncomeTaxService $incomeTaxService,
    ) {}

    // 賞与所得税計算
    public function recalculate(string $staffId, string $paymentDate): int
    {
        $staffId = trim($staffId);
        $paymentDate = trim($paymentDate);
        if ($staffId === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $paymentDate) !== 1) {
            return 0;
        }

        $bonusRow = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 1)
            ->whereRaw('CONVERT(date, [supply_month]) = ?', [$paymentDate])
            ->whereRaw('LTRIM(RTRIM([kyuyo_staff_id])) = ?', [$staffId])
            ->orderByDesc('kyuyo_sho_no')
            ->first([
                'kyuyo_sho_no',
                'bonus_amo',
                'kenpo',
                'kaigo',
                'kounen',
                'koyou',
                'child_support_funds',
                'fuyo_sum',
            ]);

        if (!$bonusRow || !isset($bonusRow->kyuyo_sho_no)) {
            return 0;
        }

        $staff = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->whereRaw('LTRIM(RTRIM([staff_id])) = ?', [$staffId])
            ->first(['staff_division', 'tax_amount']);

        $division = trim((string) ($staff->staff_division ?? ''));
        $taxAmount = trim((string) ($staff->tax_amount ?? ''));

        if (mb_strpos($division, '業務委託') !== false) {
            return (int) DB::connection('sqlsrv_payroll')
                ->table('dbo.mx_kyuyo_shou')
                ->where('kyuyo_sho_no', (int) $bonusRow->kyuyo_sho_no)
                ->update([
                    'income_tax' => 0,
                    'bonus_tax' => 0,
                ]);
        }

        $bonusTaxableBase = max(
            0.0,
            $this->num($bonusRow->bonus_amo ?? 0)
                - $this->num($bonusRow->kenpo ?? 0)
                - $this->num($bonusRow->kaigo ?? 0)
                - $this->num($bonusRow->child_support_funds ?? 0)
                - $this->num($bonusRow->kounen ?? 0)
                - $this->num($bonusRow->koyou ?? 0)
        );
        $fuyoNum = (int) ($bonusRow->fuyo_sum ?? 0);
        $previousNet = $this->num($this->loadPreviousPayrollRow($staffId, $paymentDate)?->syaho_deduction_sum ?? 0);

        if (mb_strpos($taxAmount, '乙欄') !== false) {
            $calc = $this->calcBonusOtsu($bonusTaxableBase, $previousNet);
        } else {
            $calc = $this->calcBonusKou($bonusTaxableBase, $previousNet, $fuyoNum);
        }

        return (int) DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_sho_no', (int) $bonusRow->kyuyo_sho_no)
            ->update([
                'income_tax' => (int) ($calc['tax'] ?? 0),
                'bonus_tax' => round((float) ($calc['rate'] ?? 0), 3),
            ]);
    }

    private function loadPreviousPayrollRow(string $staffId, string $paymentDate): ?object
    {
        $ts = strtotime($paymentDate);
        if ($ts === false) {
            return null;
        }

        $previousTs = strtotime('-1 month', $ts);
        if ($previousTs === false) {
            return null;
        }

        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 0)
            ->whereRaw('YEAR([supply_month]) = ?', [(int) date('Y', $previousTs)])
            ->whereRaw('MONTH([supply_month]) = ?', [(int) date('n', $previousTs)])
            ->whereRaw('LTRIM(RTRIM([kyuyo_staff_id])) = ?', [$staffId])
            ->orderByDesc('kyuyo_sho_no')
            ->first(['syaho_deduction_sum']);
    }

    /** @return array{tax:int,rate:float} */
    private function calcBonusKou(float $bonusTaxableBase, float $previousNet, int $fuyoNum): array
    {
        if ($bonusTaxableBase <= 0) {
            return ['tax' => 0, 'rate' => 0.0];
        }

        if ($previousNet <= 0 || $bonusTaxableBase > ($previousNet * 10)) {
            $monthly = $bonusTaxableBase / 6;
            $monthlyTax = (int) ($this->incomeTaxService->calcKou($monthly, $fuyoNum)['tax'] ?? 0);
            $tax = max(0, (int) floor($monthlyTax * 6));
            return ['tax' => $tax, 'rate' => $this->effectiveRate($tax, $bonusTaxableBase)];
        }

        $rate = $this->bonusRateKou($previousNet, $fuyoNum);
        return ['tax' => max(0, (int) floor($bonusTaxableBase * ($rate / 100))), 'rate' => $rate];
    }

    /** @return array{tax:int,rate:float} */
    private function calcBonusOtsu(float $bonusTaxableBase, float $previousNet): array
    {
        if ($bonusTaxableBase <= 0) {
            return ['tax' => 0, 'rate' => 0.0];
        }

        if ($previousNet <= 0 || $bonusTaxableBase > ($previousNet * 10)) {
            $monthly = $bonusTaxableBase / 6;
            $monthlyTax = (int) ($this->incomeTaxService->calcOtsu($monthly)['tax'] ?? 0);
            $tax = max(0, (int) floor($monthlyTax * 6));
            return ['tax' => $tax, 'rate' => $this->effectiveRate($tax, $bonusTaxableBase)];
        }

        $monthlyTax = (int) ($this->incomeTaxService->calcOtsu($previousNet)['tax'] ?? 0);
        $rate = $previousNet > 0 ? ($monthlyTax / $previousNet) * 100 : 0.0;
        return ['tax' => max(0, (int) floor($bonusTaxableBase * ($rate / 100))), 'rate' => $rate];
    }

    private function effectiveRate(int $tax, float $base): float
    {
        return $base > 0 ? ($tax / $base) * 100 : 0.0;
    }

    private function bonusRateKou(float $previousNet, int $fuyoNum): float
    {
        $bucket = max(0, min(7, $fuyoNum));
        $thresholds = [
            0 => [82000, 94000, 260000, 309000, 342000, 372000, 402000, 433000, 520000, 605000, 684000, 715000, 752000, 795000, 854000, 922000, 1318000, 1521000, 2621000, 3495000],
            1 => [107000, 250000, 289000, 346000, 373000, 401000, 430000, 463000, 520000, 621000, 705000, 739000, 778000, 821000, 882000, 952000, 1342000, 1526000, 2645000, 3527000],
            2 => [143000, 276000, 321000, 377000, 400000, 426000, 457000, 492000, 525000, 636000, 728000, 764000, 804000, 848000, 910000, 983000, 1367000, 1526000, 2669000, 3559000],
            3 => [181000, 300000, 354000, 405000, 424000, 452000, 484000, 517000, 550000, 651000, 751000, 788000, 830000, 876000, 938000, 1013000, 1391000, 1538000, 2693000, 3590000],
            4 => [218000, 300000, 387000, 431000, 452000, 477000, 509000, 540000, 577000, 666000, 774000, 813000, 856000, 903000, 966000, 1044000, 1416000, 1555000, 2716000, 3622000],
            5 => [251000, 304000, 412000, 457000, 479000, 503000, 531000, 564000, 604000, 681000, 798000, 838000, 881000, 930000, 994000, 1074000, 1440000, 1555000, 2740000, 3654000],
            6 => [284000, 343000, 438000, 483000, 505000, 527000, 553000, 589000, 630000, 697000, 821000, 862000, 907000, 957000, 1022000, 1104000, 1464000, 1555000, 2764000, 3685000],
            7 => [317000, 383000, 463000, 508000, 529000, 552000, 578000, 614000, 657000, 708000, 845000, 887000, 933000, 985000, 1051000, 1135000, 1489000, 1583000, 2788000, 3717000],
        ];
        $rates = [0.000, 2.042, 4.084, 6.126, 8.168, 10.210, 12.252, 14.294, 16.336, 18.378, 20.420, 22.462, 24.504, 26.546, 28.588, 30.630, 32.672, 35.735, 38.798, 41.861, 45.945];

        foreach (($thresholds[$bucket] ?? $thresholds[7]) as $index => $maxExclusive) {
            if ($previousNet < $maxExclusive) {
                return $rates[$index];
            }
        }

        return $rates[array_key_last($rates)];
    }

    private function num(mixed $value): float
    {
        if ($value === null) {
            return 0.0;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return 0.0;
        }

        $text = str_replace([',', ' '], '', $text);
        return is_numeric($text) ? (float) $text : 0.0;
    }
}

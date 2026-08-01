<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;

class PayrollV2BonusIncomeTaxCalcService
{

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
                ->update(['income_tax' => 0]);
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
            $incomeTax = $this->calcBonusOtsu($bonusTaxableBase, $previousNet);
        } else {
            $incomeTax = $this->calcBonusKou($bonusTaxableBase, $previousNet, $fuyoNum);
        }

        return (int) DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_sho_no', (int) $bonusRow->kyuyo_sho_no)
            ->update(['income_tax' => $incomeTax]);
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

    private function calcBonusKou(float $bonusTaxableBase, float $previousNet, int $fuyoNum): int
    {
        if ($bonusTaxableBase <= 0) {
            return 0;
        }

        if ($previousNet <= 0 || $bonusTaxableBase > ($previousNet * 10)) {
            $monthly = $bonusTaxableBase / 6;
            $monthlyTax = (int) ($this->calcKou($monthly, $fuyoNum)['tax'] ?? 0);
            return max(0, (int) floor($monthlyTax * 6));
        }

        $rate = $this->bonusRateKou($previousNet, $fuyoNum);
        return max(0, (int) floor($bonusTaxableBase * ($rate / 100)));
    }

    private function calcBonusOtsu(float $bonusTaxableBase, float $previousNet): int
    {
        if ($bonusTaxableBase <= 0) {
            return 0;
        }

        if ($previousNet <= 0 || $bonusTaxableBase > ($previousNet * 10)) {
            $monthly = $bonusTaxableBase / 6;
            $monthlyTax = (int) ($this->calcOtsu($monthly)['tax'] ?? 0);
            return max(0, (int) floor($monthlyTax * 6));
        }

        $monthlyTax = (int) ($this->calcOtsu($previousNet)['tax'] ?? 0);
        $rate = $previousNet > 0 ? ($monthlyTax / $previousNet) * 100 : 0.0;
        return max(0, (int) floor($bonusTaxableBase * ($rate / 100)));
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

    private function calcKou(float $syahoDeductionSum, int $fuyoNum): array
    {
        if ($syahoDeductionSum < 88000) {
            return ['tax' => 0];
        }

        $salaryDeduction = $this->salaryDeduction($syahoDeductionSum);
        $baseDeduction = $this->kisoKoujo($syahoDeductionSum);
        $dependentDeduction = max(0, $fuyoNum) * 31667 + $baseDeduction;
        $taxable = $syahoDeductionSum - $salaryDeduction - $dependentDeduction;

        if ($taxable <= 0) {
            return ['tax' => 0];
        }

        if ($taxable <= 162500) {
            $tax = $taxable * 0.05105;
        } elseif ($taxable <= 275000) {
            $tax = $taxable * 0.10210 - 8296;
        } elseif ($taxable <= 579166) {
            $tax = $taxable * 0.20420 - 36374;
        } elseif ($taxable <= 750000) {
            $tax = $taxable * 0.23483 - 54113;
        } elseif ($taxable <= 1500000) {
            $tax = $taxable * 0.33693 - 130688;
        } elseif ($taxable <= 3333333) {
            $tax = $taxable * 0.40840 - 237893;
        } else {
            $tax = $taxable * 0.45945 - 408061;
        }

        return ['tax' => max(0, $this->roundToNearest10($tax))];
    }

    private function calcOtsu(float $syahoDeductionSum): array
    {
        $amount = max(0.0, $syahoDeductionSum);
        if ($amount < 88000) {
            return ['tax' => max(0, (int) floor($amount * 0.03063))];
        }

        $baseDeduction = 48334.0;
        if ($amount <= 98999) {
            $otsu = $amount - fmod(($amount - 88000), 1000);
            $otsu15 = $otsu * 1.5;
            $otsu25 = $otsu * 2.5;
        } elseif ($amount <= 220999) {
            $otsu = $amount - fmod(($amount - 99000), 2000);
            $otsu15 = $otsu * 1.5;
            $otsu25 = $otsu * 2.5;
        } elseif ($amount <= 1009999) {
            $otsu = $amount - fmod(($amount - 221000), 3000);
            $otsu15 = $otsu * 1.5;
            $otsu25 = $otsu * 2.5;
        } elseif ($amount <= 1720000) {
            $otsu = 397600 + ($amount - 1010000) * 0.4084;
            $otsu15 = $otsu;
            $otsu25 = $otsu;
        } else {
            $otsu = 687600 + ($amount - 1720000) * 0.45945;
            $otsu15 = $otsu;
            $otsu25 = $otsu;
        }

        $otsu25Taxable = $this->otsuTaxable($otsu25, $baseDeduction);
        $otsu15Taxable = $this->otsuTaxable($otsu15, $baseDeduction);
        $otsu25Tax = $this->otsuTaxAmount($otsu25Taxable);
        $otsu15Tax = $this->otsuTaxAmount($otsu15Taxable);

        $tax = round((($otsu25Tax - $otsu15Tax) * 1.021) / 100, 0, PHP_ROUND_HALF_UP) * 100;
        return ['tax' => max(0, (int) $tax)];
    }

    private function salaryDeduction(float $amount): float
    {
        if ($amount <= 158333) {
            return 54167;
        }
        if ($amount <= 299999) {
            return floor($amount * 0.3 + 6667);
        }
        if ($amount <= 549999) {
            return floor($amount * 0.2 + 36667);
        }
        if ($amount <= 708330) {
            return floor($amount * 0.1 + 91667);
        }

        return 162500;
    }

    private function kisoKoujo(float $amount): float
    {
        if ($amount <= 2120833) {
            return 48334;
        }
        if ($amount <= 2162499) {
            return 40000;
        }
        if ($amount <= 2204166) {
            return 26667;
        }
        if ($amount <= 2245833) {
            return 13334;
        }

        return 0;
    }

    private function otsuTaxable(float $value, float $baseDeduction): float
    {
        if ($value <= 158333) {
            return $value - 54167 - $baseDeduction;
        }
        if ($value <= 299999) {
            return $value - ($value * 0.3 + 6667) - $baseDeduction;
        }
        if ($value <= 549999) {
            return $value - ($value * 0.2 + 36667) - $baseDeduction;
        }
        if ($value <= 708330) {
            return $value - ($value * 0.1 + 91667) - $baseDeduction;
        }

        return $value - 162500 - $baseDeduction;
    }

    private function otsuTaxAmount(float $taxable): int
    {
        if ($taxable <= 0) {
            return 0;
        }
        if ($taxable <= 162500) {
            return (int) floor($taxable * 0.05);
        }
        if ($taxable <= 275000) {
            return (int) floor($taxable * 0.1 - 8125);
        }
        if ($taxable <= 579166) {
            return (int) floor($taxable * 0.2 - 35625);
        }
        if ($taxable <= 750000) {
            return (int) floor($taxable * 0.23 - 53000);
        }
        if ($taxable <= 1500000) {
            return (int) floor($taxable * 0.33 - 128000);
        }
        if ($taxable <= 3333333) {
            return (int) floor($taxable * 0.4 - 233000);
        }

        return (int) floor($taxable * 0.45945 - 409061);
    }

    private function roundToNearest10(float $value): int
    {
        return (int) (round($value / 10, 0, PHP_ROUND_HALF_UP) * 10);
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

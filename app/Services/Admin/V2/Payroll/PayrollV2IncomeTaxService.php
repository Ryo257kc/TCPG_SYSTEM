<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;

class PayrollV2IncomeTaxService
{
    public function recalculate(string $staffId, int $year, int $month): int
    {
        return (int) ($this->recalculateWithTrace($staffId, $year, $month)['updated'] ?? 0);
    }

    /**
     * 月給の所得税を計算する正本。
     *
     * 税額は現在の給与明細にある課税合計・社保・雇用保険・扶養人数から計算する。
     * 呼び出し元は更新後に PayrollV2UpdateService::refreshTotals() を通して控除合計を作り直す。
     */
    public function recalculateWithTrace(string $staffId, int $year, int $month): array
    {
        $staffId = trim($staffId);
        if ($staffId === '' || $year < 2000 || $month < 1 || $month > 12) {
            return ['updated' => 0, 'trace' => ['error' => 'invalid-args']];
        }

        $payroll = DB::connection('sqlsrv_payroll');

        $row = $payroll->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month])
            ->whereRaw('LTRIM(RTRIM([kyuyo_staff_id])) = ?', [$staffId])
            ->orderByDesc('kyuyo_sho_no')
            ->first([
                'kyuyo_sho_no',
                'taxation_sum',
                'syaho_sum',
                'kenpo',
                'kaigo',
                'kounen',
                'koyou',
                'child_support_funds',
                'fuyo_sum',
            ]);

        if (!$row || !isset($row->kyuyo_sho_no)) {
            return ['updated' => 0, 'trace' => ['error' => 'payroll-row-not-found']];
        }

        $staff = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->whereRaw('LTRIM(RTRIM([staff_id])) = ?', [$staffId])
            ->first(['staff_division', 'tax_amount']);

        $division = trim((string) ($staff->staff_division ?? ''));
        $taxAmount = trim((string) ($staff->tax_amount ?? ''));

        $kenpo = (float) ($row->kenpo ?? 0);
        $kaigo = (float) ($row->kaigo ?? 0);
        $kounen = (float) ($row->kounen ?? 0);
        $koyou = (float) ($row->koyou ?? 0);
        $childSupportFunds = (float) ($row->child_support_funds ?? 0);
        $taxationSum = (float) ($row->taxation_sum ?? 0);
        $fuyoNum = (int) ($row->fuyo_sum ?? 0);

        $fieldSyahoSum = $kenpo + $kaigo + $childSupportFunds + $kounen + $koyou;
        $syahoSum = is_numeric($row->syaho_sum ?? null) ? (float) $row->syaho_sum : $fieldSyahoSum;
        $syahoDeductionSum = $taxationSum - $syahoSum;
        if ($syahoDeductionSum < 0) {
            $syahoDeductionSum = 0;
        }

        $trace = [
            'staff_id' => $staffId,
            'target_month' => sprintf('%04d-%02d', $year, $month),
            'tax_amount' => $taxAmount,
            'staff_division' => $division,
            'taxation_sum' => $taxationSum,
            'kenpo' => $kenpo,
            'kaigo' => $kaigo,
            'kounen' => $kounen,
            'koyou' => $koyou,
            'child_support_funds' => $childSupportFunds,
            'field_syaho_sum' => $fieldSyahoSum,
            'syaho_sum' => $syahoSum,
            'syaho_deduction_sum' => $syahoDeductionSum,
            'fuyo_sum' => $fuyoNum,
        ];

        $incomeTax = 0;
        if (!str_contains($division, '業務委託')) {
            if (mb_strpos($taxAmount, '乙欄') !== false) {
                $calc = $this->calcOtsu($syahoDeductionSum, $year);
                $incomeTax = (int) ($calc['tax'] ?? 0);
                $trace['mode'] = 'otsu';
                $trace['detail'] = $calc['detail'] ?? [];
            } else {
                $calc = $this->calcKou($syahoDeductionSum, $fuyoNum, $year);
                $incomeTax = (int) ($calc['tax'] ?? 0);
                $trace['mode'] = 'kou';
                $trace['detail'] = $calc['detail'] ?? [];
            }
        } else {
            $trace['mode'] = 'excluded-gyomu-itaku';
            $trace['detail'] = [];
        }

        $updated = $payroll->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_sho_no', (int) $row->kyuyo_sho_no)
            ->update([
                'syaho_sum' => round($syahoSum, 0),
                'syaho_deduction_sum' => round($syahoDeductionSum, 0),
                'income_tax' => $incomeTax,
            ]);

        $trace['income_tax'] = $incomeTax;

        return ['updated' => $updated, 'trace' => $trace];
    }

    /**
     * 月額表(甲欄)による所得税計算。賞与の月割換算(PayrollV2BonusIncomeTaxCalcService)からも呼ぶため public。
     * $year は給与所得控除・基礎控除逓減の年度分岐に使う（年調のYearEndCalculationServiceと同じ
     * パターン。2026-08-15、旧Access VBAで古い表がコメントアウトされているのを見つけて追加。
     * 2025年の法改正で最低控除額が55万円→65万円に上がった前後で表が変わっている）。
     */
    public function calcKou(float $syahoDeductionSum, int $fuyoNum, int $year): array
    {
        if ($syahoDeductionSum < 88000) {
            return ['tax' => 0, 'detail' => ['rule' => '< 88000']];
        }

        $a = $syahoDeductionSum;
        $shokoujyo = $this->salaryDeduction($a, $year);
        $kiso = $this->kisoKoujo($a, $year);
        $fuyo = max(0, $fuyoNum) * 31667 + $kiso;

        $taxable = $a - $shokoujyo - $fuyo;
        if ($taxable <= 0) {
            return [
                'tax' => 0,
                'detail' => [
                    'a' => $a,
                    'shokoujyo' => $shokoujyo,
                    'kiso' => $kiso,
                    'fuyo' => $fuyo,
                    'taxable' => $taxable,
                    'bracket' => 'taxable<=0',
                ],
            ];
        }

        $tax = 0.0;
        $bracket = '';
        if ($taxable <= 162500) {
            $tax = $taxable * 0.05105;
            $bracket = '0-162500';
        } elseif ($taxable <= 275000) {
            $tax = $taxable * 0.10210 - 8296;
            $bracket = '162501-275000';
        } elseif ($taxable <= 579166) {
            $tax = $taxable * 0.20420 - 36374;
            $bracket = '275001-579166';
        } elseif ($taxable <= 750000) {
            $tax = $taxable * 0.23483 - 54113;
            $bracket = '579167-750000';
        } elseif ($taxable <= 1500000) {
            $tax = $taxable * 0.33693 - 130688;
            $bracket = '750001-1500000';
        } elseif ($taxable <= 3333333) {
            $tax = $taxable * 0.40840 - 237893;
            $bracket = '1500001-3333333';
        } else {
            $tax = $taxable * 0.45945 - 408061;
            $bracket = '3333334+';
        }

        $taxRounded = max(0, $this->roundToNearest10($tax));

        return [
            'tax' => $taxRounded,
            'detail' => [
                'a' => $a,
                'shokoujyo' => $shokoujyo,
                'kiso' => $kiso,
                'fuyo' => $fuyo,
                'taxable' => $taxable,
                'bracket' => $bracket,
                'tax_raw' => $tax,
                'tax_rounded_10' => $taxRounded,
            ],
        ];
    }

    /**
     * 月額表(乙欄)による所得税計算。賞与の月割換算(PayrollV2BonusIncomeTaxCalcService)からも呼ぶため public。
     * $year引数は将来この関数内の表を年度分岐させる時のために追加したが、現状ここでは未使用
     * （kisoは元々固定値48334で、kisoKoujo()のブランケットとは別物として扱われていたため、
     * 挙動を変えないよう手を付けていない。要確認）。
     */
    public function calcOtsu(float $syahoDeductionSum, int $year): array
    {
        $a = max(0.0, $syahoDeductionSum);
        if ($a < 88000) {
            $tax = max(0, (int) floor($a * 0.03063));
            return ['tax' => $tax, 'detail' => ['rule' => '< 88000', 'a' => $a]];
        }

        $kiso = 48334.0;
        $otu = 0.0;
        $otu15 = 0.0;
        $otu25 = 0.0;
        $bracket = '';

        if ($a <= 98999) {
            $otu = $a - fmod(($a - 88000), 1000);
            $otu15 = $otu * 1.5;
            $otu25 = $otu * 2.5;
            $bracket = '88000-98999';
        } elseif ($a <= 220999) {
            $otu = $a - fmod(($a - 99000), 2000);
            $otu15 = $otu * 1.5;
            $otu25 = $otu * 2.5;
            $bracket = '99000-220999';
        } elseif ($a <= 1009999) {
            $otu = $a - fmod(($a - 221000), 3000);
            $otu15 = $otu * 1.5;
            $otu25 = $otu * 2.5;
            $bracket = '221000-1009999';
        } elseif ($a <= 1720000) {
            $otu = 397600 + ($a - 1010000) * 0.4084;
            $otu15 = $otu;
            $otu25 = $otu;
            $bracket = '1010000-1720000';
        } else {
            $otu = 687600 + ($a - 1720000) * 0.45945;
            $otu15 = $otu;
            $otu25 = $otu;
            $bracket = '1720000+';
        }

        $otu25Taxable = $this->otsuTaxable($otu25, $kiso);
        $otu15Taxable = $this->otsuTaxable($otu15, $kiso);

        $otu25Tax = $this->otsuTaxAmount($otu25Taxable);
        $otu15Tax = $this->otsuTaxAmount($otu15Taxable);

        $tax = round((($otu25Tax - $otu15Tax) * 1.021) / 100, 0, PHP_ROUND_HALF_UP) * 100;
        $tax = max(0, (int) $tax);

        return [
            'tax' => $tax,
            'detail' => [
                'a' => $a,
                'bracket' => $bracket,
                'otu' => $otu,
                'otu15' => $otu15,
                'otu25' => $otu25,
                'kiso' => $kiso,
                'otu15_taxable' => $otu15Taxable,
                'otu25_taxable' => $otu25Taxable,
                'otu15_tax' => $otu15Tax,
                'otu25_tax' => $otu25Tax,
                'tax_rounded_100' => $tax,
            ],
        ];
    }

    /**
     * 給与所得控除（月額）。2026-08-15、旧Access VBA（本番の元ネタ）で古い表が
     * コメントアウトされて残っているのを見つけ、年度分岐を追加した
     * （最低控除額が2025年の法改正で55万円→65万円相当に上がった前後で表が変わっている）。
     * 2025年より前の値はVBAのコメントアウト分から復元。年調のYearEndCalculationServiceと
     * 同じ「年度で分岐、新ルールは過去年分に遡って適用しない」パターン。
     */
    private function salaryDeduction(float $a, int $year): float
    {
        if ($year >= 2025) {
            if ($a <= 158333) {
                return 54167;
            }
            if ($a <= 299999) {
                return floor($a * 0.3 + 6667);
            }
            if ($a <= 549999) {
                return floor($a * 0.2 + 36667);
            }
            if ($a <= 708330) {
                return floor($a * 0.1 + 91667);
            }
            return 162500;
        }

        // 2024年以前（旧Access VBAより復元）。
        if ($a <= 135416) {
            return 45834;
        }
        if ($a <= 149999) {
            return floor($a * 0.4) - 8333;
        }
        if ($a <= 299999) {
            return floor($a * 0.3 + 6667);
        }
        if ($a <= 549999) {
            return floor($a * 0.2 + 36667);
        }
        if ($a <= 708330) {
            return floor($a * 0.1 + 91667);
        }
        return 162500;
    }

    /**
     * 基礎控除逓減（月額換算・甲欄用）。要確認：旧Access VBAのコメントアウト分は
     * 「Case 2245834 To 2245833」のように範囲が壊れており、2025年より前の正確な閾値が
     * 復元できなかった。年度分岐の枠だけ用意し、2024年以前は暫定的に現行表のまま使う
     * （実データで過去年分の是正が必要になったら、正しい閾値をユーザーに確認してから直す）。
     */
    private function kisoKoujo(float $a, int $year): float
    {
        if ($a <= 2120833) {
            return 48334;
        }
        if ($a <= 2162499) {
            return 40000;
        }
        if ($a <= 2204166) {
            return 26667;
        }
        if ($a <= 2245833) {
            return 13334;
        }
        return 0;
    }

    private function otsuTaxable(float $value, float $kiso): float
    {
        if ($value <= 158333) {
            return $value - 54167 - $kiso;
        }
        if ($value <= 299999) {
            return $value - ($value * 0.3 + 6667) - $kiso;
        }
        if ($value <= 549999) {
            return $value - ($value * 0.2 + 36667) - $kiso;
        }
        if ($value <= 708330) {
            return $value - ($value * 0.1 + 91667) - $kiso;
        }
        return $value - 162500 - $kiso;
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
}

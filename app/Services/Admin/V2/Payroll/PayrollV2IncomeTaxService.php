<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;

class PayrollV2IncomeTaxService
{
    public function recalculate(string $staffId, int $year, int $month): int
    {
        return (int) ($this->recalculateWithTrace($staffId, $year, $month)['updated'] ?? 0);
    }

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
                'kenpo',
                'kaigo',
                'kounen',
                'koyou',
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
        $taxationSum = (float) ($row->taxation_sum ?? 0);
        $fuyoNum = (int) ($row->fuyo_sum ?? 0);

        $syahoSum = $kenpo + $kaigo + $kounen + $koyou;
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
            'syaho_sum' => $syahoSum,
            'syaho_deduction_sum' => $syahoDeductionSum,
            'fuyo_sum' => $fuyoNum,
        ];

        $incomeTax = 0;
        if (mb_strpos($division, '業務委託') === false) {
            if (mb_strpos($taxAmount, '乙欄') !== false) {
                $calc = $this->calcOtsu($syahoDeductionSum);
                $incomeTax = (int) ($calc['tax'] ?? 0);
                $trace['mode'] = 'otsu';
                $trace['detail'] = $calc['detail'] ?? [];
            } else {
                $calc = $this->calcKou($syahoDeductionSum, $fuyoNum);
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

    private function calcKou(float $syahoDeductionSum, int $fuyoNum): array
    {
        if ($syahoDeductionSum < 88000) {
            return ['tax' => 0, 'detail' => ['rule' => '< 88000']];
        }

        $a = $syahoDeductionSum;
        $shokoujyo = $this->salaryDeduction($a);
        $kiso = $this->kisoKoujo($a);
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

    private function calcOtsu(float $syahoDeductionSum): array
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

    private function salaryDeduction(float $a): float
    {
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

    private function kisoKoujo(float $a): float
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

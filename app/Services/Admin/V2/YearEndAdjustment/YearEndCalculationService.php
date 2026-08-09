<?php

namespace App\Services\Admin\V2\YearEndAdjustment;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 年末調整の計算ロジック（正本）。
 *
 * YearEndAdjustmentV2Controller から計算部分だけを切り出したもの。
 * PDF帳票の座標・レイアウトはこのクラスに置かない（Controller側に残す）。
 * 法改定で年度ごとに表が変わる計算（基礎控除・配偶者控除・給与所得控除など）は、
 * このファイル内で targetYear により分岐する形に統一する。分岐を追加するときは
 * docs/rules/year_end_adjustment/03_calculation.md も一緒に更新すること。
 */
class YearEndCalculationService
{
    /** @return array<string, mixed> */
    public function calculateYearEndPayload(object $nenTyo, string $staffId, int $targetYear): array
    {
        $payroll = $this->yearEndPayrollTotals($staffId, $targetYear);
        $dependents = $this->yearEndDependentTotals($staffId, $targetYear);
        $retired = $this->isYearEndRetired($staffId);

        $zenShotoku = $this->money($nenTyo->zen_shotoku ?? 0);
        $zenSyaho = $this->money($nenTyo->zen_syaho_kou ?? 0);
        $zenKyuyoTax = $this->money($nenTyo->zen_kyuyo_tax ?? 0);
        $zenBonusTax = $this->money($nenTyo->zen_bonus_tax ?? 0);

        $kyuyoTeateSum = $payroll['salary_taxable'] + $zenShotoku;
        $bonusEtc = $payroll['bonus_taxable'];
        $bonusKyuyoSum = $kyuyoTeateSum + $bonusEtc;
        $kyuyoTeateTax = $payroll['salary_income_tax'] + $zenKyuyoTax;
        $bonusTax = $payroll['bonus_income_tax'] + $zenBonusTax;
        $siharaiShotoku = $kyuyoTeateTax + $bonusTax;
        $kyuSyahoFeeKou = $payroll['syaho_sum'] + $zenSyaho;

        $shotokuDeduction = $this->salaryIncomeAfterDeduction($bonusKyuyoSum, $targetYear);

        // 基礎控除は合計所得金額に応じた段階制。年度で表が変わるため targetYear で分岐する
        // basicDeductionAmount() を使う。mx_nen_tyo.kiso_koujyo に既存値があれば、それを
        // 優先する（年調是正などで過去に手入力・確定した金額を上書きしないため）。
        $kisoKoujyo = $this->money($nenTyo->kiso_koujyo ?? 0);
        if ($kisoKoujyo <= 0) {
            $kisoKoujyo = $this->basicDeductionAmount($shotokuDeduction, $targetYear);
        }

        // 配偶者控除・配偶者特別控除：配偶者の合計所得金額(haigu_shotoku、本人が申告した値)と
        // 本人の合計所得金額から、国税庁の金額表で計算する（配偶者控除の額＝haigu_deduction／
        // 配偶者特別控除の額＝haigu_toku_deduction。どちらか一方のみ発生する）。
        // ※旧カラム名はhaigu_toku_deduction／haigu_toku_deduction_amoで、「toku」が付いているのが
        // 実は「特別」ではない方（regular）という紛らわしい命名だった。テストサーバーの段階で
        // sp_renameにより物理カラム名ごと修正済み（2026年8月）。
        // 計算対象にするかどうかは mx_fuyo の配偶者行の deduction_target（控除対象チェック）を
        // 正とする。扶養親族と同じ扱いで、控除対象チェックが入っている配偶者だけを計算する。
        $haiguShotoku = $this->money($nenTyo->haigu_shotoku ?? 0);
        if ($dependents['haigu_deduction_target']) {
            // 配偶者の所得0円（専業主婦・主夫等）は「配偶者控除の対象外」ではなく「配偶者の
            // 合計所得金額が58万円以下」に該当するため、満額の配偶者控除が入る。
            $spousalDeduction = $this->spousalDeductionAmounts($shotokuDeduction, $haiguShotoku, (bool) $dependents['haigu_elderly'], $targetYear);
        } else {
            $spousalDeduction = ['regular' => 0, 'special' => 0];
        }
        $haiguDeduction = (float) $spousalDeduction['regular'];
        $haiguTokuDeduction = (float) $spousalDeduction['special'];

        // 基礎控除申告書「区分Ⅰ」（A/B/C）。配偶者控除等申告書の金額表で列を選ぶ
        // spousalDeductionAmounts() の900万/950万/1000万円のしきい値と同じ判定を、
        // 表示用の記号として求めるだけ（計算式はspousalDeductionAmounts()と共通）。
        $kisoBunrui = $this->kisoBunruiLabel($shotokuDeduction, $targetYear);

        $shinSyahoFeeKou = $this->money($nenTyo->shin_syaho_fee_kou ?? 0);
        $shunKigyouFeeKou = $this->money($nenTyo->shun_kigyou_fee_kou ?? 0);
        $seimeiFeeKou = $this->money($nenTyo->seimei_fee_kou ?? 0);
        $jishunFeeKou = $this->money($nenTyo->jishun_fee_kou ?? 0);
        $tyoseiKoujyo = $this->money($nenTyo->tyosei_koujyo ?? 0);
        $jyuKariKou = $this->money($nenTyo->jyu_kari_kou ?? 0);

        $deductionSum = $dependents['deduction_sum'];
        $shotokuDeductionSum = $kyuSyahoFeeKou
            + $shinSyahoFeeKou
            + $shunKigyouFeeKou
            + $seimeiFeeKou
            + $jishunFeeKou
            + $deductionSum
            + $haiguDeduction
            + $haiguTokuDeduction
            + $kisoKoujyo;

        $saKazeiShotoku = max(0, (int) floor(($shotokuDeduction - $tyoseiKoujyo - $shotokuDeductionSum) / 1000) * 1000);
        $sanshutuShotoku = $this->calculatedIncomeTax($saKazeiShotoku);
        $nentyoShotokuAmo = max(0, $sanshutuShotoku - $jyuKariKou);
        $nentyoNenTax = (int) floor(($nentyoShotokuAmo * 1.021) / 100) * 100;
        $saExcess = $nentyoNenTax - $siharaiShotoku;

        $fuyoReport = (int) ($nenTyo->fuyo_deduction_report ?? 1) === 1;
        $taxAmount = trim((string) ($nenTyo->tax_amount ?? ''));
        if ($taxAmount === '乙欄') {
            $kisoKoujyo = 0;
            $kisoBunrui = '';
            $deductionSum = 0;
            $haiguDeduction = 0;
            $haiguTokuDeduction = 0;
            $kyuSyahoFeeKou = 0;
            $shotokuDeductionSum = 0;
            $saKazeiShotoku = 0;
            $sanshutuShotoku = 0;
            $nentyoShotokuAmo = 0;
            $nentyoNenTax = $siharaiShotoku;
            $saExcess = 0;
        } elseif (!$fuyoReport || $retired) {
            $kisoKoujyo = 0;
            $kisoBunrui = '';
            $deductionSum = 0;
            $haiguDeduction = 0;
            $haiguTokuDeduction = 0;
            $shotokuDeduction = 0;
            $shotokuDeductionSum = 0;
            $saKazeiShotoku = 0;
            $sanshutuShotoku = 0;
            $nentyoShotokuAmo = 0;
            $nentyoNenTax = 0;
            $saExcess = $retired ? 0 : -$siharaiShotoku;
        }

        return [
            'kyuyo_teate_sum' => $kyuyoTeateSum,
            'bonus_etc' => $bonusEtc,
            'bonus_kyuyo_sum' => $bonusKyuyoSum,
            'kyuyo_teate_tax' => $kyuyoTeateTax,
            'bonus_tax' => $bonusTax,
            'siharai_shotoku' => $siharaiShotoku,
            'kyu_syaho_fee_kou' => $kyuSyahoFeeKou,
            'toku_fu' => $dependents['toku_fu'],
            'rou_dou' => $dependents['rou_dou'],
            'rou_dou_gai' => $dependents['rou_dou_gai'],
            'fuyo_ta' => $dependents['fuyo_ta'],
            'shougai_dou_toku' => $dependents['shougai_dou_toku'],
            'shougai_toku' => $dependents['shougai_toku'],
            'shougai_ta' => $dependents['shougai_ta'],
            'dependent_under_16' => $dependents['dependent_under_16'],
            'deduction_sum' => $deductionSum,
            'kiso_koujyo' => $kisoKoujyo,
            'kiso_bunrui' => $kisoBunrui,
            // 区分Ⅱ（配偶者控除等申告書側、①②③表記）は区分Ⅰ（基礎控除申告書側、A/B/C表記）と
            // 全く同じ900万/950万/1000万円のしきい値判定なので、同じ計算結果をそのまま使う
            // （表示するときだけController側の記号変換を分ける）。
            'haigu_bunrui' => $kisoBunrui,
            'haigu_deduction' => $haiguDeduction,
            'haigu_toku_deduction' => $haiguTokuDeduction,
            'shotoku_deduction' => $shotokuDeduction,
            'shotoku_deduction_sum' => $shotokuDeductionSum,
            'sa_kazei_shotoku' => $saKazeiShotoku,
            'sanshutu_shotoku' => $sanshutuShotoku,
            'nentyo_shotoku_amo' => $nentyoShotokuAmo,
            'nentyo_nen_tax' => $nentyoNenTax,
            'sa_excess' => $saExcess,
        ];
    }

    /** @return array{salary_taxable:float,bonus_taxable:float,salary_income_tax:float,bonus_income_tax:float,syaho_sum:float} */
    private function yearEndPayrollTotals(string $staffId, int $targetYear): array
    {
        $rows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_staff_id', $staffId)
            ->whereYear('supply_month', $targetYear)
            ->get(['bonus', 'taxation_sum', 'income_tax', 'syaho_sum']);

        $totals = [
            'salary_taxable' => 0.0,
            'bonus_taxable' => 0.0,
            'salary_income_tax' => 0.0,
            'bonus_income_tax' => 0.0,
            'syaho_sum' => 0.0,
        ];

        foreach ($rows as $row) {
            $isBonus = (int) ($row->bonus ?? 0) === 1;
            $taxable = $this->money($row->taxation_sum ?? 0);
            $incomeTax = $this->money($row->income_tax ?? 0);
            if ($isBonus) {
                $totals['bonus_taxable'] += $taxable;
                $totals['bonus_income_tax'] += $incomeTax;
            } else {
                $totals['salary_taxable'] += $taxable;
                $totals['salary_income_tax'] += $incomeTax;
            }
            $totals['syaho_sum'] += $this->money($row->syaho_sum ?? 0);
        }

        return $totals;
    }

    /**
     * 扶養親族等の区分ごとの控除額（正本）。yearEndDependentTotals()の計算と、源泉徴収簿PDFの
     * 「人数×単価」内訳表示の両方がここを参照する（数字を2箇所に書いて将来ズレるのを防ぐため）。
     *
     * @return array<string, int>
     */
    public function dependentDeductionRates(): array
    {
        return [
            'toku_fu' => 630000,
            'rou_dou' => 580000,
            'rou_dou_gai' => 480000,
            'fuyo_ta' => 380000,
            'shougai_dou_toku' => 750000,
            'shougai_toku' => 400000,
            'shougai_ta' => 270000,
        ];
    }

    /** @return array<string, int|float> */
    private function yearEndDependentTotals(string $staffId, int $targetYear): array
    {
        $rows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_fuyo')
            ->where('staff_id', $staffId)
            ->whereYear('registration_date', $targetYear)
            ->get();

        $totals = [
            'toku_fu' => 0,
            'rou_dou' => 0,
            'rou_dou_gai' => 0,
            'fuyo_ta' => 0,
            'shougai_dou_toku' => 0,
            'shougai_toku' => 0,
            'shougai_ta' => 0,
            'dependent_under_16' => 0,
            'deduction_sum' => 0.0,
            'haigu_elderly' => false,
            'haigu_deduction_target' => false,
        ];

        foreach ($rows as $row) {
            $target = (int) ($row->deduction_target ?? 0) === 1;
            $relationship = trim((string) ($row->fuyo_relationship ?? ''));
            $age = $this->ageAtYearEnd($row->fuyo_birthday ?? null, $targetYear);
            $failure = trim((string) ($row->failure_judgment ?? ''));
            $kyojyu = trim((string) ($row->kyojyu ?? ''));

            if ($target && $age < 16) {
                $totals['dependent_under_16']++;
            }

            if (!$target) {
                continue;
            }

            if (($relationship === '夫' || $relationship === '妻')) {
                // 配偶者控除・配偶者特別控除は扶養控除額(deduction_sum)に含めず、
                // calculateYearEndPayload() 側で haigu_deduction／haigu_toku_deduction
                // として別枠で計算する（国税庁の年末調整のしかた 18〜19ページで、扶養控除の
                // 人数に配偶者を含めない旨、配偶者(特別)控除額は源泉徴収簿の⑰欄に別記する旨が
                // 明記されている）。
                // この時点で deduction_target=1（控除対象チェック）の配偶者行だけがここに来る
                // （上の !$target で弾かれるため）。これを配偶者控除等の計算対象そのものの判定に
                // 使う。老人控除対象配偶者（70歳以上）かどうかも合わせて記録する。
                $totals['haigu_deduction_target'] = true;
                $totals['haigu_elderly'] = $age >= 70;
                continue;
            }

            $rates = $this->dependentDeductionRates();

            if ($age >= 19 && $age < 23) {
                $totals['toku_fu']++;
                $totals['deduction_sum'] += $rates['toku_fu'];
            } elseif ($age >= 70) {
                if ($kyojyu === '同居') {
                    $totals['rou_dou']++;
                    $totals['deduction_sum'] += $rates['rou_dou'];
                } else {
                    $totals['rou_dou_gai']++;
                    $totals['deduction_sum'] += $rates['rou_dou_gai'];
                }
            } elseif (($age >= 16 && $age <= 18) || ($age >= 23 && $age < 70)) {
                $totals['fuyo_ta']++;
                $totals['deduction_sum'] += $rates['fuyo_ta'];
            }

            if (in_array($failure, ['A1', 'A2', '1級', '2級'], true)) {
                if ($kyojyu === '同居') {
                    $totals['shougai_dou_toku']++;
                    $totals['deduction_sum'] += $rates['shougai_dou_toku'];
                } else {
                    $totals['shougai_toku']++;
                    $totals['deduction_sum'] += $rates['shougai_toku'];
                }
            } elseif ($failure !== '') {
                $totals['shougai_ta']++;
                $totals['deduction_sum'] += $rates['shougai_ta'];
            }
        }

        return $totals;
    }

    /**
     * 基礎控除額（合計所得金額に応じた段階制）。
     *
     * 年度ごとに国税庁の表が変わるため targetYear で分岐する。年調是正で過去年分を
     * 再計算する場合でも、その年の表で計算されるよう年度ごとに分けて実装すること。
     *
     * 令和6年（2024年）分以前・令和7年（2025年）分・令和8年（2026年）分以後、
     * いずれも国税庁公式サイトで確認済み
     * （https://www.nta.go.jp/taxes/shiraberu/taxanswer/shotoku/1199.htm）。
     * 900万/950万/1,000万円というサブ区分（kiso_bunrui用）は基礎控除額そのものには
     * 影響しない（配偶者控除等申告書の金額表の列選択にのみ使う別の区分）。
     */
    private function basicDeductionAmount(float $totalIncome, int $targetYear): int
    {
        if ($targetYear >= 2026) {
            // 令和8年（2026年）分以後：令和7年分にあった132万超2,350万円以下の
            // 4段階（88万/68万/63万/58万）が一律58万円に統合される。
            if ($totalIncome <= 1320000) {
                return 950000;
            }
            if ($totalIncome <= 23500000) {
                return 580000;
            }
            if ($totalIncome <= 24000000) {
                return 480000;
            }
            if ($totalIncome <= 24500000) {
                return 320000;
            }
            if ($totalIncome <= 25000000) {
                return 160000;
            }
            return 0;
        }

        if ($targetYear === 2025) {
            if ($totalIncome <= 1320000) {
                return 950000;
            }
            if ($totalIncome <= 3360000) {
                return 880000;
            }
            if ($totalIncome <= 4890000) {
                return 680000;
            }
            if ($totalIncome <= 6550000) {
                return 630000;
            }
            if ($totalIncome <= 23500000) {
                return 580000;
            }
            if ($totalIncome <= 24000000) {
                return 480000;
            }
            if ($totalIncome <= 24500000) {
                return 320000;
            }
            if ($totalIncome <= 25000000) {
                return 160000;
            }
            return 0;
        }

        // 令和6年（2024年）分以前。
        if ($totalIncome <= 24000000) {
            return 480000;
        }
        if ($totalIncome <= 24500000) {
            return 320000;
        }
        if ($totalIncome <= 25000000) {
            return 160000;
        }

        return 0;
    }

    /**
     * 基礎控除額表の境界（万円単位、基礎控除の額と区分Ⅰのどちらかが変わる点）。
     * kisoBunruiLabel()・kisoBracketOptions()の両方がこれを使うので、境界を
     * 二重に書かない（ズレる事故を防ぐため）。
     *
     * @return array<int, int>
     */
    private function kisoBracketManBoundaries(int $targetYear): array
    {
        if ($targetYear < 2025) {
            return [2400, 2450, 2500];
        }
        if ($targetYear === 2025) {
            return [132, 336, 489, 655, 900, 950, 1000, 2350, 2400, 2450, 2500];
        }

        return [132, 900, 950, 1000, 2350, 2400, 2450, 2500];
    }

    /**
     * 基礎控除申告書の「区分Ⅰ」に保存する文字列。旧Access側の運用に合わせて、
     * 900万/950万/1,000万円の3区分にまとめず、実際に該当した細かい所得区分
     * （「◯◯万円超◯◯万円以下」）をそのまま保存する。帳票の枠にA/B/C記号だけを
     * 印字するときは、この文字列ではなくkisoBunruiSymbol()で改めて求める
     * （文字列の突き合わせだと年度で行数が変わったときに対応できないため）。
     *
     * 令和7年（2025年）分のみユーザー確認済み。2024年以前はいつからこの区分になったか
     * 未確認のため、2025年分未満は空欄のままにする。
     */
    private function kisoBunruiLabel(float $totalIncome, int $targetYear): string
    {
        if ($targetYear < 2025) {
            return '';
        }

        $lowerMan = 0;
        foreach ($this->kisoBracketManBoundaries($targetYear) as $upperMan) {
            if ($totalIncome <= $upperMan * 10000) {
                return $lowerMan === 0 ? "{$upperMan}万円以下" : "{$lowerMan}万円超{$upperMan}万円以下";
            }
            $lowerMan = $upperMan;
        }

        return "{$lowerMan}万円超";
    }

    /**
     * 区分ⅠのA/B/C記号（配偶者控除等の金額表の列選択専用。900万/950万/1,000万円の
     * 3区分のみで、基礎控除の額そのものには影響しない）。
     */
    private function kisoBunruiSymbol(float $totalIncome, int $targetYear): string
    {
        if ($targetYear < 2025) {
            return '';
        }
        if ($totalIncome <= 9000000) {
            return 'A';
        }
        if ($totalIncome <= 9500000) {
            return 'B';
        }
        if ($totalIncome <= 10000000) {
            return 'C';
        }

        return '';
    }

    /**
     * kiso_bunruiに保存されている文字列（kisoBunruiLabel()の戻り値）から、
     * 帳票に印字するA/B/C記号を逆引きする。年度によって細かい区分の行数が
     * 変わるため、文字列を直接パースせず、その年のkisoBracketOptions()と
     * 突き合わせて求める。
     */
    public function kisoBunruiSymbolForLabel(string $label, int $targetYear): string
    {
        foreach ($this->kisoBracketOptions($targetYear) as $row) {
            if ($row['label'] === $label) {
                return $row['symbol'];
            }
        }

        return '';
    }

    /**
     * 基礎控除の額（kiso_koujyo）と区分Ⅰ（kiso_bunrui）を、金額・区分のどちらかが
     * 変わる境界ごとに1行にまとめた一覧。画面の選択肢に使う。
     * 金額・区分は既存のbasicDeductionAmount()/kisoBunruiSymbol()から算出するので、
     * ここに金額を書き直さない（表とズレる事故を防ぐため）。
     *
     * @return array<int, array{label: string, amount: int, symbol: string}>
     */
    public function kisoBracketOptions(int $targetYear): array
    {
        $manBoundaries = $this->kisoBracketManBoundaries($targetYear);

        $rows = [];
        $lowerMan = 0;
        foreach ($manBoundaries as $upperMan) {
            $sampleIncome = (float) ($upperMan * 10000);
            $rows[] = [
                'label' => $lowerMan === 0 ? "{$upperMan}万円以下" : "{$lowerMan}万円超{$upperMan}万円以下",
                'amount' => $this->basicDeductionAmount($sampleIncome, $targetYear),
                'symbol' => $this->kisoBunruiSymbol($sampleIncome, $targetYear),
            ];
            $lowerMan = $upperMan;
        }
        $sampleIncome = (float) ($lowerMan * 10000 + 1);
        $rows[] = [
            'label' => "{$lowerMan}万円超",
            'amount' => $this->basicDeductionAmount($sampleIncome, $targetYear),
            'symbol' => $this->kisoBunruiSymbol($sampleIncome, $targetYear),
        ];

        return $rows;
    }

    /**
     * 配偶者控除額・配偶者特別控除額（令和7年分、国税庁「年末調整のしかた」18〜20ページ
     * および配偶者控除等申告書の金額表で確認済み）。
     *
     * 本人の合計所得金額が1,000万円超、または配偶者の合計所得金額が133万円超の場合は
     * どちらも0円。配偶者の合計所得金額が58万円以下なら配偶者控除（regular）、
     * 58万円超133万円以下なら配偶者特別控除（special）。両方が同時に発生することはない。
     *
     * @return array{regular: int, special: int}
     */
    private function spousalDeductionAmounts(float $taxpayerIncome, float $spouseIncome, bool $spouseIsElderly, int $targetYear): array
    {
        $none = ['regular' => 0, 'special' => 0];

        if ($targetYear < 2025) {
            // 2024年以前：未確認のため計算しない（要確認）。
            return $none;
        }

        // 配偶者の所得0円（専業主婦・主夫等）は「58万円以下」の枠に該当し満額の対象になる。
        // spousalDeductionAmounts() は呼び出し側（has_spouse）で配偶者の有無を判定済みの前提。
        if ($spouseIncome < 0 || $taxpayerIncome > 10000000) {
            return $none;
        }

        if ($taxpayerIncome <= 9000000) {
            $column = 0;
        } elseif ($taxpayerIncome <= 9500000) {
            $column = 1;
        } else {
            $column = 2;
        }

        if ($spouseIncome <= 580000) {
            $amounts = $spouseIsElderly ? [480000, 320000, 160000] : [380000, 260000, 130000];
            return ['regular' => $amounts[$column], 'special' => 0];
        }

        if ($spouseIncome > 1330000) {
            return $none;
        }

        // 配偶者特別控除：配偶者の合計所得金額の上限ごとの [900万以下, 900〜950万, 950〜1000万]
        $specialTable = [
            [950000, [380000, 260000, 130000]],
            [1000000, [360000, 240000, 120000]],
            [1050000, [310000, 210000, 110000]],
            [1100000, [260000, 180000, 90000]],
            [1150000, [210000, 140000, 70000]],
            [1200000, [160000, 110000, 60000]],
            [1250000, [110000, 80000, 40000]],
            [1300000, [60000, 40000, 20000]],
            [1330000, [30000, 20000, 10000]],
        ];
        foreach ($specialTable as [$upperBound, $amounts]) {
            if ($spouseIncome <= $upperBound) {
                return ['regular' => 0, 'special' => $amounts[$column]];
            }
        }

        return $none;
    }

    private function salaryIncomeAfterDeduction(float $income, int $targetYear): int
    {
        if ($income <= 0) {
            return 0;
        }

        if ($targetYear >= 2025) {
            if ($income <= 650999) {
                return 0;
            }
            if ($income <= 1899999) {
                return (int) ($income - 650000);
            }
            // 給与等の金額が190万円以上660万円未満の人は、実収入をそのまま計算式に入れず、
            // 国税庁「給与所得控除後の給与等の金額の表」（4,000円刻み）で区間の下限額に丸めて
            // から計算式を適用する（国税庁 令和7年分資料 47〜54ページで確認済み）。
            if ($income <= 3599999) {
                $bucketed = (int) floor($income / 4000) * 4000;
                return (int) floor($bucketed * 0.7 - 80000);
            }
            if ($income <= 6599999) {
                $bucketed = (int) floor($income / 4000) * 4000;
                return (int) floor($bucketed * 0.8 - 440000);
            }
            // 660万円以上は表を使わず、実収入から直接計算する（1円未満切り捨て）。
            if ($income <= 8499999) {
                return (int) floor($income * 0.9 - 1100000);
            }
            return (int) ($income - 1950000);
        }

        if ($income <= 550999) {
            return 0;
        }
        if ($income <= 1618999) {
            return (int) ($income - 550000);
        }
        if ($income <= 1619999) {
            return (int) floor($income * 0.6 + 97600);
        }
        if ($income <= 1621999) {
            return (int) floor($income * 0.6 + 98000);
        }
        if ($income <= 1623999) {
            return (int) floor($income * 0.6 + 98800);
        }
        if ($income <= 1627999) {
            return (int) floor($income * 0.6 + 99600);
        }
        if ($income <= 1799999) {
            return (int) floor($income * 0.6 + 100000);
        }
        if ($income <= 3599999) {
            return (int) floor($income * 0.7 - 80000);
        }
        if ($income <= 6599999) {
            return (int) floor($income * 0.8 - 440000);
        }
        if ($income <= 8499999) {
            return (int) floor($income * 0.9 - 1100000);
        }
        return (int) ($income - 1950000);
    }

    private function calculatedIncomeTax(float $taxable): int
    {
        if ($taxable <= 0) {
            return 0;
        }
        if ($taxable <= 1950000) {
            return (int) floor($taxable * 0.05);
        }
        if ($taxable <= 3300000) {
            return (int) floor($taxable * 0.1 - 97500);
        }
        if ($taxable <= 6950000) {
            return (int) floor($taxable * 0.2 - 427500);
        }
        if ($taxable <= 9000000) {
            return (int) floor($taxable * 0.23 - 636000);
        }
        if ($taxable <= 18000000) {
            return (int) floor($taxable * 0.33 - 1536000);
        }
        if ($taxable <= 40000000) {
            return (int) floor($taxable * 0.4 - 2796000);
        }
        return (int) floor($taxable * 0.45 - 4796000);
    }

    /** 扶養控除申告書PDFの年齢表示でも使うため public にしている。 */
    public function ageAtYearEnd(mixed $birthday, int $targetYear): int
    {
        if ($birthday === null || $birthday === '') {
            return 0;
        }

        try {
            $birth = new \DateTimeImmutable((string) $birthday);
            $yearEnd = new \DateTimeImmutable(sprintf('%04d-12-31', $targetYear));
            return (int) $birth->diff($yearEnd)->y;
        } catch (\Throwable) {
            return 0;
        }
    }

    private function isYearEndRetired(string $staffId): bool
    {
        if (!Schema::connection('sqlsrv')->hasTable('mx_staffs')) {
            return false;
        }

        $taiDate = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->where('staff_id', $staffId)
            ->value('tai_date');

        return trim((string) ($taiDate ?? '')) !== '';
    }

    private function money(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '', (string) $value);
    }
}

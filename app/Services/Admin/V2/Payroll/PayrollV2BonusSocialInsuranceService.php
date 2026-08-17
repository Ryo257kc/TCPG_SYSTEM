<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PayrollV2BonusSocialInsuranceService
{
    public function __construct(
        private readonly PayrollV2ShahoService $shahoService,
    ) {}

    public function recalculate(string $staffId, string $paymentDate): int
    {
        $staffId = trim($staffId);
        $paymentDate = trim($paymentDate);

        if ($staffId === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $paymentDate) !== 1) {
            return 0;
        }

        $row = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 1)
            ->whereRaw('LTRIM(RTRIM([kyuyo_staff_id])) = ?', [$staffId])
            ->whereRaw('CONVERT(date, [supply_month]) = ?', [$paymentDate])
            ->orderByDesc('kyuyo_sho_no')
            ->first(['kyuyo_sho_no', 'bonus_amo']);

        if (!$row || !isset($row->kyuyo_sho_no)) {
            return 0;
        }

        $year = (int) substr($paymentDate, 0, 4);
        $month = (int) substr($paymentDate, 5, 2);
        $companyId = $this->resolveCompanyId($staffId);
        $bonusRates = $this->loadBonusRates($companyId, $paymentDate);
        $birthday = $this->loadBirthday($staffId);

        $gross = $this->num($row->bonus_amo ?? 0);
        $currentStandard = floor($gross / 1000) * 1000;
        $targets = $this->resolveTargetStandards($staffId, (int) $row->kyuyo_sho_no, $paymentDate, $currentStandard);

        $kenpo = $this->employeeInsuranceAmount(
            $targets['kenpo_target_standard'],
            $bonusRates['kenpo_rate'] ?? 0
        );

        $kaigo = 0;
        if ($this->shouldApplyKaigo($birthday, $year, $month)) {
            $kenpoKaigoTotal = $this->employeeInsuranceAmount(
                $targets['kenpo_target_standard'],
                $bonusRates['kaigo_rate'] ?? 0
            );

            $kaigo = max(0, $kenpoKaigoTotal - $kenpo);
        }

        $kounen = $this->employeeInsuranceAmount(
            $targets['kounen_target_standard'],
            $bonusRates['kounen_rate'] ?? 0
        );

        $childSupportFunds = $this->employeeInsuranceAmount(
            $targets['kenpo_target_standard'],
            $bonusRates['kodomo_shien'] ?? 0
        );

        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_sho_no', (int) $row->kyuyo_sho_no)
            ->update([
                'kenpo' => $kenpo,
                'kaigo' => $kaigo,
                'kounen' => $kounen,
                'child_support_funds' => $childSupportFunds,
            ]);
    }

    /**
     * 会社負担一覧（賞与版）向け。標準賞与額の上限キャップ（recalculate()と同じ
     * resolveTargetStandards()）を踏まえた自己/会社/計を返す。自己側は帳票側で
     * 再計算せず、recalculate()が保存済みの値（$summaryのkenpo/kaigo/kounen/
     * child_support_funds）をそのまま使う。会社側・合計だけここで算出する
     * （2026-08-15、月給用のPayrollV2SocialInsuranceAmountService::statementAmounts()の
     * 賞与版として新規追加）。
     *
     * @param array<string, mixed> $summary mx_kyuyo_shouの対象行（bonus_amo・kenpo等を含む）
     * @return array<string, int|float>
     */
    public function statementAmounts(string $staffId, string $paymentDate, int $kyuyoShoNo, array $summary): array
    {
        $companyId = $this->resolveCompanyId($staffId);
        $bonusRates = $this->loadBonusRates($companyId, $paymentDate);
        $birthday = $this->loadBirthday($staffId);
        $year = (int) substr($paymentDate, 0, 4);
        $month = (int) substr($paymentDate, 5, 2);

        $gross = $this->num($summary['bonus_amo'] ?? 0);
        $currentStandard = floor($gross / 1000) * 1000;
        $targets = $this->resolveTargetStandards($staffId, $kyuyoShoNo, $paymentDate, $currentStandard);

        $kenpoSelf = (int) round($this->num($summary['kenpo'] ?? 0));
        $kaigoSelf = (int) round($this->num($summary['kaigo'] ?? 0));
        $kounenSelf = (int) round($this->num($summary['kounen'] ?? 0));
        $childSupportSelf = (int) round($this->num($summary['child_support_funds'] ?? 0));

        $kenpoTotal = $this->officeInsuranceAmount($targets['kenpo_target_standard'], $bonusRates['kenpo_rate'] ?? 0);
        $kaigoTotal = 0;
        if ($this->shouldApplyKaigo($birthday, $year, $month)) {
            $kenpoKaigoTotal = $this->officeInsuranceAmount($targets['kenpo_target_standard'], $bonusRates['kaigo_rate'] ?? 0);
            $kaigoTotal = max(0, $kenpoKaigoTotal - $kenpoTotal);
        }
        $kounenTotal = $this->officeInsuranceAmount($targets['kounen_target_standard'], $bonusRates['kounen_rate'] ?? 0);
        $childSupportTotal = $this->officeInsuranceAmount($targets['kenpo_target_standard'], $bonusRates['kodomo_shien'] ?? 0);
        $jidouOffice = $this->officeInsuranceAmount($targets['kounen_target_standard'], $bonusRates['jidou_rate'] ?? 0);

        $kenpoOffice = max(0, $kenpoTotal - $kenpoSelf);
        $kaigoOffice = max(0, $kaigoTotal - $kaigoSelf);
        $kounenOffice = max(0, $kounenTotal - $kounenSelf);
        $childSupportOffice = max(0, $childSupportTotal - $childSupportSelf);

        return [
            'kenpo_standard' => $targets['kenpo_target_standard'],
            'kounen_standard' => $targets['kounen_target_standard'],
            'kenpo_self' => $kenpoSelf,
            'kenpo_office' => $kenpoOffice,
            'kenpo_total' => $kenpoTotal,
            'kaigo_self' => $kaigoSelf,
            'kaigo_office' => $kaigoOffice,
            'kaigo_total' => $kaigoTotal,
            'kounen_self' => $kounenSelf,
            'kounen_office' => $kounenOffice,
            'kounen_total' => $kounenTotal,
            'child_support_self' => $childSupportSelf,
            'child_support_funds' => $childSupportOffice,
            'child_support_total' => $childSupportTotal,
            'jidou_office' => $jidouOffice,
            'self_total' => $kenpoSelf + $kaigoSelf + $kounenSelf + $childSupportSelf,
            'office_total' => $kenpoOffice + $kaigoOffice + $kounenOffice + $jidouOffice + $childSupportOffice,
            'grand_total' => $kenpoTotal + $kaigoTotal + $kounenTotal + $jidouOffice + $childSupportTotal,
        ];
    }

    private function officeInsuranceAmount(float $standard, float $ratePercent): int
    {
        if ($standard <= 0 || $ratePercent <= 0) {
            return 0;
        }

        return (int) ceil($standard * ($ratePercent / 100));
    }

    /**
     * 標準賞与額の上限キャップ（健保：年度累計573万円、厚年：同月累計150万円）を反映した
     * 対象標準額。児童手当拠出金（PayrollV2EmploymentInsuranceService::recalculateBonus）も
     * 厚生年金と同じ上限を使うため、ここから呼ぶ（2026-08-17、public化）。
     * @return array{kenpo_target_standard:float,kounen_target_standard:float}
     */
    public function resolveTargetStandards(string $staffId, int $currentRowId, string $paymentDate, float $currentStandard): array
    {
        $selectedTs = strtotime($paymentDate);
        if ($selectedTs === false) {
            return [
                'kenpo_target_standard' => 0.0,
                'kounen_target_standard' => 0.0,
            ];
        }

        $sameMonthEnd = date('Y-m-t', $selectedTs);
        $fiscalStart = ((int) date('n', $selectedTs) >= 4)
            ? date('Y-04-01', $selectedTs)
            : date('Y-04-01', strtotime('-1 year', $selectedTs));

        $historyRows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->select(['kyuyo_sho_no', 'supply_month', 'bonus_amo'])
            ->where('bonus', 1)
            ->whereRaw('LTRIM(RTRIM([kyuyo_staff_id])) = ?', [$staffId])
            ->whereNotNull('supply_month')
            ->whereRaw('CONVERT(date, [supply_month]) >= ?', [$fiscalStart])
            ->whereRaw('CONVERT(date, [supply_month]) <= ?', [$sameMonthEnd])
            ->where('kyuyo_sho_no', '<>', $currentRowId)
            ->get()
            ->map(static fn ($row): array => [
                'supply_month' => (string) ($row->supply_month ?? ''),
                'bonus_amo' => $row->bonus_amo ?? 0,
            ])
            ->all();

        return self::computeTargetStandards($historyRows, $paymentDate, $currentStandard);
    }

    /**
     * 標準報酬月額の上限判定（健保：年度累計573万円、厚年：同月累計150万円）の正本。
     * 賞与一覧画面の表示（PayrollV2Controller::attachBonusCalc()）もここを呼ぶ
     * （以前は表示側が独自に同じような式を持っていて、同月境界の扱いが少しズレていた）。
     *
     * 要確認（2026-08-15）：この573万円/150万円は年度で分岐しておらず固定値。法定の上限額で
     * 変更頻度は低い認識のため、変更が確認されるまでは分岐を追加せず保留する
     * （[[feedback_year_end_calc_needs_year_gating]]の対象だが、旧Access側にも参照できる
     * 過去の変更履歴が見当たらなかったため）。もし将来この上限額が変わったら、
     * PayrollV2IncomeTaxService::salaryDeduction()と同じ「$year引数で分岐」パターンで対応する。
     *
     * @param list<array{supply_month:string,bonus_amo:mixed}> $historyRows 自分自身の行は含めない、他の賞与履歴
     * @return array{kenpo_target_standard:float,kounen_target_standard:float}
     */
    public static function computeTargetStandards(array $historyRows, string $paymentDate, float $currentStandard): array
    {
        $selectedTs = strtotime($paymentDate);
        if ($selectedTs === false) {
            return [
                'kenpo_target_standard' => 0.0,
                'kounen_target_standard' => 0.0,
            ];
        }

        $sameMonthStart = date('Y-m-01', $selectedTs);
        $sameMonthEnd = date('Y-m-t', $selectedTs);

        $sameMonthOtherStandard = 0.0;
        $fiscalPrior = 0.0;

        foreach ($historyRows as $historyRow) {
            $historyDate = trim((string) ($historyRow['supply_month'] ?? ''));
            $historyTs = strtotime($historyDate);
            if ($historyTs === false) {
                continue;
            }

            $historyGross = $historyRow['bonus_amo'] ?? 0;
            $historyGross = is_numeric($historyGross) ? (float) $historyGross : 0.0;
            $historyStandard = floor($historyGross / 1000) * 1000;
            $historyYmd = date('Y-m-d', $historyTs);

            if ($historyYmd < $paymentDate) {
                $fiscalPrior += $historyStandard;
            }

            if ($historyYmd >= $sameMonthStart && $historyYmd <= $sameMonthEnd) {
                $sameMonthOtherStandard += $historyStandard;
            }
        }

        $kenpoCapRemaining = max(5730000 - $fiscalPrior, 0);
        $kenpoTargetStandard = min($currentStandard, $kenpoCapRemaining);

        $kounenCapRemaining = max(1500000 - $sameMonthOtherStandard, 0);
        $kounenTargetStandard = min($currentStandard, $kounenCapRemaining);

        return [
            'kenpo_target_standard' => $kenpoTargetStandard,
            'kounen_target_standard' => $kounenTargetStandard,
        ];
    }
    /** @return array{kenpo_rate:float,kaigo_rate:float,kounen_rate:float,kodomo_shien:float,jidou_rate:float} */
    private function loadBonusRates(string $companyId, string $paymentDate): array
    {
        $query = DB::connection('sqlsrv_payroll')->table('dbo.mx_syaho');

        if ($companyId !== '' && $this->hasPayrollColumn('mx_syaho', 'office_no')) {
            $query->whereRaw('LTRIM(RTRIM(CAST(office_no AS nvarchar(50)))) = ?', [$companyId]);
        }

        $rows = $query
            ->orderByDesc('syaho_no')
            ->get([
                'kenpo_apply_date',
                'kenpo_rate',
                'kaigo_apply_date',
                'kaigo_rate',
                'kou_apply_date',
                'kounen_rate',
                'kodomo_shien',
                'kodomo_shien_date',
                'jidou_apply_date',
                'jidou_rate',
            ]);

        return [
            'kenpo_rate' => $this->pickApplicableRate($rows, 'kenpo_apply_date', 'kenpo_rate', $paymentDate),
            'kaigo_rate' => $this->pickApplicableRate($rows, 'kaigo_apply_date', 'kaigo_rate', $paymentDate),
            'kounen_rate' => $this->pickApplicableRate($rows, 'kou_apply_date', 'kounen_rate', $paymentDate),
            'kodomo_shien' => $this->pickApplicableRate($rows, 'kodomo_shien_date', 'kodomo_shien', $paymentDate),
            'jidou_rate' => $this->pickApplicableRate($rows, 'jidou_apply_date', 'jidou_rate', $paymentDate),
        ];
    }

    private function pickApplicableRate(iterable $rows, string $applyDateColumn, string $rateColumn, string $paymentDate): float
    {
        foreach ($rows as $row) {
            $applyDate = trim((string) ($row->{$applyDateColumn} ?? ''));
            $rate = $this->num($row->{$rateColumn} ?? null);
            if ($rate <= 0) {
                continue;
            }

            if ($applyDate === '' || substr($applyDate, 0, 10) <= $paymentDate) {
                return $rate;
            }
        }

        return 0.0;
    }
    private function loadBirthday(string $staffId): ?\DateTimeImmutable
    {
        $row = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->whereRaw('LTRIM(RTRIM(staff_id)) = ?', [$staffId])
            ->first(['birthday']);

        return $this->toDate($row->birthday ?? null);
    }

    private function resolveCompanyId(string $staffId): string
    {
        $row = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs as s')
            ->leftJoin('dbo.mx_stores as st', 'st.store_code', '=', 's.section')
            ->whereRaw('LTRIM(RTRIM(s.staff_id)) = ?', [$staffId])
            ->first(['st.company_id']);

        return trim((string) ($row->company_id ?? ''));
    }

    private function hasPayrollColumn(string $table, string $column): bool
    {
        return Schema::connection('sqlsrv_payroll')->hasColumn($table, $column)
            || Schema::connection('sqlsrv_payroll')->hasColumn('dbo.' . $table, $column);
    }

    private function shouldApplyKaigo(?\DateTimeImmutable $birthday, int $year, int $month): bool
    {
        if ($birthday === null) {
            return false;
        }

        $start = $birthday->modify('+40 years')->modify('-1 day');
        $targetMonthStart = new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month));

        return $targetMonthStart >= $start->modify('first day of this month')->setTime(0, 0);
    }

    private function toDate(mixed $value): ?\DateTimeImmutable
    {
        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        if ($value === null) {
            return null;
        }

        $s = trim((string) $value);
        if ($s === '') {
            return null;
        }

        $ts = strtotime($s);
        if ($ts === false) {
            return null;
        }

        return (new \DateTimeImmutable())->setTimestamp($ts);
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

    private function employeeInsuranceAmount(float $standard, float $ratePercent): int
    {
        if ($standard <= 0 || $ratePercent <= 0) {
            return 0;
        }
        $total = (int) ceil($standard * ($ratePercent / 100));
        return (int) ceil($total / 2);
    }
}

<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;

class PayrollV2SocialInsuranceAmountService
{
    /** @var array<string,int> staffId => company_id */
    private array $companyIdCache = [];

    /** @var array<string,array{kenpo_rate:float,kaigo_rate:float,kounen_rate:float,jidou_rate:float,kodomo_shien:float}> "companyId|paymentDate" => rates */
    private array $ratesCache = [];

    /** @return array{kenpo_rate:float,kaigo_rate:float,kounen_rate:float,jidou_rate:float,kodomo_shien:float} */
    public function loadRatesForStaff(string $staffId, string $paymentDate): array
    {
        if (!array_key_exists($staffId, $this->companyIdCache)) {
            $this->companyIdCache[$staffId] = $this->resolveCompanyId($staffId);
        }

        return $this->loadRates($this->companyIdCache[$staffId], $paymentDate);
    }

    /** @return array{kenpo_rate:float,kaigo_rate:float,kounen_rate:float,jidou_rate:float,kodomo_shien:float} */
    public function loadRates(string|int $officeNo, string $paymentDate): array
    {
        $cacheKey = $officeNo . '|' . $paymentDate;
        if (array_key_exists($cacheKey, $this->ratesCache)) {
            return $this->ratesCache[$cacheKey];
        }

        $this->ratesCache[$cacheKey] = $this->loadRatesUncached($officeNo, $paymentDate);

        return $this->ratesCache[$cacheKey];
    }

    /** @return array{kenpo_rate:float,kaigo_rate:float,kounen_rate:float,jidou_rate:float,kodomo_shien:float} */
    private function loadRatesUncached(string|int $officeNo, string $paymentDate): array
    {
        $cutoffMonth = (new \DateTimeImmutable($paymentDate))
            ->modify('first day of this month')
            ->format('Y-m-01');

        $base = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_syaho')
            ->whereRaw('office_no = ?', [(int) $officeNo]);

        $kenpoRow = (clone $base)
            ->where('kenpo_apply_date', '<', $cutoffMonth)
            ->orderByDesc('kenpo_apply_date')
            ->first(['kenpo_apply_date', 'kenpo_rate']);

        $kaigoRow = (clone $base)
            ->where('kaigo_apply_date', '<', $cutoffMonth)
            ->orderByDesc('kaigo_apply_date')
            ->first(['kaigo_apply_date', 'kaigo_rate']);

        $kounenRow = (clone $base)
            ->where('kou_apply_date', '<', $cutoffMonth)
            ->orderByDesc('kou_apply_date')
            ->first(['kou_apply_date', 'kounen_rate']);

        $jidouRow = (clone $base)
            ->where('jidou_apply_date', '<', $cutoffMonth)
            ->orderByDesc('jidou_apply_date')
            ->first(['jidou_apply_date', 'jidou_rate']);

        $kodomoRow = (clone $base)
            ->where('kodomo_shien_date', '<', $cutoffMonth)
            ->orderByDesc('kodomo_shien_date')
            ->first(['kodomo_shien_date', 'kodomo_shien']);

        return [
            'kenpo_rate' => $this->num($kenpoRow->kenpo_rate ?? 0),
            'kaigo_rate' => $this->num($kaigoRow->kaigo_rate ?? 0),
            'kounen_rate' => $this->num($kounenRow->kounen_rate ?? 0),
            'jidou_rate' => $this->num($jidouRow->jidou_rate ?? 0),
            'kodomo_shien' => $this->num($kodomoRow->kodomo_shien ?? 0),
        ];
    }

    /** @return array{kenpo:int,kaigo:int,kounen:int,jidou_office:int,child_support_funds:int} */
    public function payrollSummary(array $shaho, ?\DateTimeImmutable $birthday, int $year, int $month): array
    {
        $totals = $this->fullAmounts($shaho, $birthday, $year, $month);

        $kenpo = $this->employeeInsuranceAmount($this->num($shaho['kenpo_monthly_amo'] ?? 0), $this->num($shaho['kenpo_rate'] ?? 0));
        $kaigo = 0;
        if ($this->shouldApplyKaigo($birthday, $year, $month)) {
            $kenpoKaigoTotal = $this->employeeInsuranceAmount($this->num($shaho['kenpo_monthly_amo'] ?? 0), $this->num($shaho['kaigo_rate'] ?? 0));
            $kaigo = max(0, $kenpoKaigoTotal - $kenpo);
        }
        $kounen = $this->employeeInsuranceAmount($this->num($shaho['kounen_monthly_amo'] ?? 0), $this->num($shaho['kounen_rate'] ?? 0));
        $childSupportFunds = $this->employeeInsuranceAmount(
            $this->num($shaho['kenpo_monthly_amo'] ?? 0),
            $this->num($shaho['kodomo_shien'] ?? 0)
        );

        return [
            'kenpo' => $kenpo,
            'kaigo' => $kaigo,
            'kounen' => $kounen,
            'jidou_office' => $totals['jidou_office'],
            'child_support_funds' => $childSupportFunds,
        ];
    }

    /** @return array<string,int|float> */
    public function statementAmounts(array $summary, array $shaho, ?\DateTimeImmutable $birthday, int $year, int $month): array
    {
        $totals = $this->fullAmounts($shaho, $birthday, $year, $month);

        $kenpoSelf = (int) round($this->num($summary['kenpo'] ?? 0));
        $kaigoSelf = (int) round($this->num($summary['kaigo'] ?? 0));
        $kounenSelf = (int) round($this->num($summary['kounen'] ?? 0));
        $childSupportSelf = (int) round($this->num($summary['child_support_funds'] ?? 0));

        $kenpoOffice = max(0, $totals['kenpo_total'] - $kenpoSelf);
        $kaigoOffice = max(0, $totals['kaigo_total'] - $kaigoSelf);
        $kounenOffice = max(0, $totals['kounen_total'] - $kounenSelf);
        $childSupportOffice = max(0, $totals['child_support_funds'] - $childSupportSelf);
        $officeOnly = $totals['jidou_office'] + $childSupportOffice;

        return [
            'kenpo_standard' => $this->num($shaho['kenpo_monthly_amo'] ?? 0),
            'kounen_standard' => $this->num($shaho['kounen_monthly_amo'] ?? 0),
            'kenpo_self' => $kenpoSelf,
            'kenpo_office' => $kenpoOffice,
            'kenpo_total' => $totals['kenpo_total'],
            'kaigo_self' => $kaigoSelf,
            'kaigo_office' => $kaigoOffice,
            'kaigo_total' => $totals['kaigo_total'],
            'kounen_self' => $kounenSelf,
            'kounen_office' => $kounenOffice,
            'kounen_total' => $totals['kounen_total'],
            'jidou_office' => $totals['jidou_office'],
            'child_support_funds' => $childSupportOffice,
            // 要確認：child_support_fundsは元々「会社負担額」の意味で使われている
            // （mx_kyuyo_shou.child_support_fundsの「自己」値とは別物）ため、印刷帳票の
            // 自己/会社/計3列表示用にchild_support_self/child_support_totalを追加した
            // （2026-08-15、会社負担一覧の項目追加に伴い）。
            'child_support_self' => $childSupportSelf,
            'child_support_total' => $childSupportSelf + $childSupportOffice,
            'self_total' => $kenpoSelf + $kaigoSelf + $kounenSelf + $childSupportSelf,
            'office_total' => $kenpoOffice + $kaigoOffice + $kounenOffice + $officeOnly,
            'grand_total' => $totals['kenpo_total'] + $totals['kaigo_total'] + $totals['kounen_total'] + $totals['jidou_office'] + $totals['child_support_funds'],
        ];
    }

    /** @return array{kenpo_total:int,kaigo_total:int,kounen_total:int,jidou_office:int,child_support_funds:int} */
    private function fullAmounts(array $shaho, ?\DateTimeImmutable $birthday, int $year, int $month): array
    {
        $kenpoStandard = $this->num($shaho['kenpo_monthly_amo'] ?? 0);
        $kounenStandard = $this->num($shaho['kounen_monthly_amo'] ?? 0);

        $kenpoRate = $this->num($shaho['kenpo_rate'] ?? 0);
        $kaigoIncludedRate = $this->num($shaho['kaigo_rate'] ?? 0);
        $kounenRate = $this->num($shaho['kounen_rate'] ?? 0);

        $kenpoTotal = $this->officeInsuranceAmount($kenpoStandard, $kenpoRate);
        $kaigoTotal = 0;
        if ($this->shouldApplyKaigo($birthday, $year, $month)) {
            $kenpoKaigoTotal = $this->officeInsuranceAmount($kenpoStandard, $kaigoIncludedRate);
            $kaigoTotal = max(0, $kenpoKaigoTotal - $kenpoTotal);
        }

        return [
            'kenpo_total' => $kenpoTotal,
            'kaigo_total' => $kaigoTotal,
            'kounen_total' => $this->officeInsuranceAmount($kounenStandard, $kounenRate),
            'jidou_office' => $this->officeInsuranceAmount($kounenStandard, $this->num($shaho['jidou_rate'] ?? 0)),
            'child_support_funds' => $this->officeInsuranceAmount($kenpoStandard, $this->num($shaho['kodomo_shien'] ?? 0)),
        ];
    }

    private function resolveCompanyId(string $staffId): int
    {
        $row = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs as s')
            ->leftJoin('dbo.mx_stores as st', 'st.store_code', '=', 's.section')
            ->leftJoin('dbo.mx_companies as c', 'c.company_id', '=', 'st.company_id')
            ->whereRaw('LTRIM(RTRIM(s.staff_id)) = ?', [trim($staffId)])
            ->first(['c.company_id']);

        return (int) ($row->company_id ?? 0);
    }

    public function toDate(mixed $value): ?\DateTimeImmutable
    {
        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        $timestamp = strtotime($text);
        if ($timestamp === false) {
            return null;
        }

        return (new \DateTimeImmutable())->setTimestamp($timestamp);
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

    private function employeeInsuranceAmount(float $standard, float $ratePercent): int
    {
        if ($standard <= 0 || $ratePercent <= 0) {
            return 0;
        }

        $total = (int) ceil($standard * ($ratePercent / 100));
        return (int) ceil($total / 2);
    }

    private function officeInsuranceAmount(float $standard, float $ratePercent): int
    {
        if ($standard <= 0 || $ratePercent <= 0) {
            return 0;
        }

        return (int) ceil($standard * ($ratePercent / 100));
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

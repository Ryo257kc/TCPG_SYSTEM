<?php

namespace App\Services\Admin\V2\Report;

use DateInterval;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

class ReportV2RishokuServiceV3
{
    /** @return list<string> */
    public function companyOptions(): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_staffs as s')
            ->leftJoin('dbo.mx_stores as st', 'st.store_code', '=', 's.section')
            ->leftJoin('dbo.mx_companies as c', 'c.company_id', '=', 'st.company_id')
            ->whereNotNull('s.tai_date')
            ->whereRaw("LTRIM(RTRIM(CAST(s.tai_date as nvarchar(50)))) <> ''")
            ->whereNotNull('c.company_name')
            ->whereRaw("LTRIM(RTRIM(c.company_name)) <> ''")
            ->distinct()
            ->orderBy('c.company_name')
            ->pluck('c.company_name')
            ->map(static fn ($value): string => trim((string) $value))
            ->filter(static fn (string $value): bool => $value !== '')
            ->values()
            ->all();
    }

    /**
     * @return list<array{staff_id:string,label:string,retire_date:string,company_name:string}>
     */
    public function staffOptions(string $companyName = ''): array
    {
        $query = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs as s')
            ->leftJoin('dbo.mx_stores as st', 'st.store_code', '=', 's.section')
            ->leftJoin('dbo.mx_companies as c', 'c.company_id', '=', 'st.company_id')
            ->select([
                's.staff_id',
                's.staff_name',
                's.staff_name_furi',
                's.tai_date',
                'c.company_name',
            ])
            ->whereNotNull('s.tai_date')
            ->whereRaw("LTRIM(RTRIM(CAST(s.tai_date as nvarchar(50)))) <> ''");

        if ($companyName !== '') {
            $query->where('c.company_name', $companyName);
        }

        return $query
            ->orderByDesc('s.tai_date')
            ->orderBy('s.staff_id')
            ->get()
            ->map(function ($row): array {
                $staffId = trim((string) ($row->staff_id ?? ''));
                $staffName = trim((string) ($row->staff_name ?? ''));
                $staffKana = trim((string) ($row->staff_name_furi ?? ''));
                $companyName = trim((string) ($row->company_name ?? ''));
                $retireDate = $this->dateValue($row->tai_date ?? null);

                $label = $staffName;
                if ($staffKana !== '') {
                    $label .= ' / ' . $staffKana;
                }
                if ($retireDate !== '') {
                    $label .= ' / 離職 ' . $retireDate;
                }
                if ($companyName !== '') {
                    $label .= ' / ' . $companyName;
                }

                return [
                    'staff_id' => $staffId,
                    'label' => $label,
                    'retire_date' => $retireDate,
                    'company_name' => $companyName,
                ];
            })
            ->filter(static fn (array $row): bool => $row['staff_id'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return array{
     *   profile: array<string,string>,
     *   filters: array<string,string|int>,
     *   rows: list<array<string,mixed>>,
     *   totals: array<string,int>
     * }
     */
    public function build(string $staffId = '', string $companyName = '', int $months = 36): array
    {
        $staffId = trim($staffId);
        $months = max(12, min(48, $months));

        $empty = [
            'profile' => $this->emptyProfile(),
            'filters' => [
                'company_name' => $companyName,
                'staff_id' => $staffId,
                'months' => $months,
            ],
            'rows' => [],
            'totals' => [
                'eligible_by_days' => 0,
                'eligible_by_hours' => 0,
                'gross_total' => 0,
            ],
        ];

        if ($staffId === '') {
            return $empty;
        }

        $staff = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs as s')
            ->leftJoin('dbo.mx_stores as st', 'st.store_code', '=', 's.section')
            ->leftJoin('dbo.mx_companies as c', 'c.company_id', '=', 'st.company_id')
            ->select([
                's.staff_id',
                's.staff_name',
                's.staff_name_furi',
                's.nyu_date',
                's.tai_date',
                's.staff_division',
                's.employment',
                's.post_num',
                's.address',
                's.home_tel',
                's.mobile_tel',
                's.my_number',
                's.koyou_num',
                'st.store_name',
                'c.company_name',
                'c.postal_code as company_post_num',
                'c.company_address',
                'c.tel as company_tel',
                'c.labor_insurance_number',
                'c.closing_day',
            ])
            ->where('s.staff_id', $staffId)
            ->first();

        if (!$staff) {
            return $empty;
        }

        $retireDate = $this->toDate($staff->tai_date ?? null);
        if ($retireDate === null) {
            return $empty;
        }

        $profile = [
            'staff_id' => trim((string) ($staff->staff_id ?? '')),
            'staff_name' => trim((string) ($staff->staff_name ?? '')),
            'staff_name_kana' => trim((string) ($staff->staff_name_furi ?? '')),
            'join_date' => $this->dateValue($staff->nyu_date ?? null),
            'retire_date' => $retireDate->format('Y/m/d'),
            'staff_division' => trim((string) ($staff->staff_division ?? '')),
            'employment' => trim((string) ($staff->employment ?? '')),
            'company_name' => trim((string) ($staff->company_name ?? '')),
            'store_name' => trim((string) ($staff->store_name ?? '')),
            'company_post_num' => trim((string) ($staff->company_post_num ?? '')),
            'post_num' => trim((string) ($staff->post_num ?? '')),
            'address' => trim((string) ($staff->address ?? '')),
            'home_tel' => trim((string) ($staff->home_tel ?? '')),
            'mobile_tel' => trim((string) ($staff->mobile_tel ?? '')),
            'my_number' => trim((string) ($staff->my_number ?? '')),
            'koyou_num' => trim((string) ($staff->koyou_num ?? '')),
            'company_address' => trim((string) ($staff->company_address ?? '')),
            'company_tel' => trim((string) ($staff->company_tel ?? '')),
            'labor_insurance_number' => trim((string) ($staff->labor_insurance_number ?? '')),
            'closing_day' => trim((string) ($staff->closing_day ?? '')),
        ];

        $joinDate = $this->toDate($staff->nyu_date ?? null);
        $payrollRows = $this->payrollRows($staffId, $months);
        $periodCount = count($payrollRows) > 0 ? count($payrollRows) : $months;
        $periods = $this->buildPeriods($retireDate, $periodCount, $profile['closing_day']);

        $rows = [];
        $eligibleByDays = 0;
        $eligibleByHours = 0;
        $grossTotal = 0;

        foreach ($periods as $index => $period) {
            $payroll = $payrollRows[$index] ?? null;

            $basisDays = $payroll ? $this->basisDays($payroll, $profile, $period['wage_start_raw'], $period['wage_end_raw']) : null;
            $workDays = $payroll ? $this->floatValue($payroll->work_in_num ?? null) : null;
            $paidLeaveDays = $payroll ? $this->floatValue($payroll->horiday_true ?? null) : null;
            $workHours = $payroll ? $this->floatValue($payroll->work_time ?? ($payroll->work_time_num ?? null)) : null;
            $absenceDays = $payroll ? $this->floatValue($payroll->absence_num ?? null) : null;
            $gross = $payroll ? (int) round($this->floatValue($payroll->supply_sum ?? null), 0) : null;
            $isHourlyOrDaily = $this->isHourlyOrDaily($profile);
            $wageA = $gross !== null && !$isHourlyOrDaily ? $gross : null;
            $wageB = $gross !== null && $isHourlyOrDaily ? $gross : null;

            $eligibilityDays = ($workDays ?? 0.0) + ($paidLeaveDays ?? 0.0);
            $eligibleDays = $payroll !== null && $eligibilityDays >= 11;
            $eligibleHours = $payroll !== null && !$eligibleDays && ($workHours ?? 0.0) >= 80;

            if ($eligibleDays) {
                $eligibleByDays++;
            }
            if ($eligibleHours) {
                $eligibleByHours++;
            }
            if ($gross !== null) {
                $grossTotal += $gross;
            }

            $rows[] = [
                'row_no' => $index + 1,
                'separation_period' => $period['separation_period'],
                'payment_basis_days' => $period['calendar_days'],
                'payment_date' => $payroll ? $this->dateValue($payroll->supply_month ?? null) : '',
                'wage_period' => $period['wage_period'],
                'before_join' => $payroll === null
                    && $joinDate !== null
                    && (($wageEnd = $this->toDate($period['wage_end_raw'])) !== null)
                    && $wageEnd < $joinDate,
                'basis_days' => $basisDays,
                'work_days' => $workDays,
                'absence_days' => $absenceDays,
                'paid_leave_days' => $paidLeaveDays,
                'eligibility_days' => $payroll ? $eligibilityDays : null,
                'work_hours' => $workHours,
                'gross_amount' => $gross,
                'wage_a_amount' => $wageA,
                'wage_b_amount' => $wageB,
                'eligible_by_days' => $eligibleDays,
                'eligible_by_hours' => $eligibleHours,
                'has_payroll' => $payroll !== null,
            ];
        }

        return [
            'profile' => $profile,
            'filters' => [
                'company_name' => $companyName,
                'staff_id' => $staffId,
                'months' => $months,
            ],
            'rows' => $rows,
            'totals' => [
                'eligible_by_days' => $eligibleByDays,
                'eligible_by_hours' => $eligibleByHours,
                'gross_total' => $grossTotal,
            ],
        ];
    }

    /**
     * @return list<array{
     *   separation_period:string,
     *   period_start_raw:string,
     *   period_end_raw:string,
     *   wage_start_raw:string,
     *   wage_end_raw:string,
     *   calendar_days:int,
     *   wage_period:string
     * }>
     */
    private function buildPeriods(DateTimeImmutable $retireDate, int $months, string $closingDay = ''): array
    {
        $rows = [];

        for ($i = 0; $i < $months; $i++) {
            $end = $retireDate->sub(new DateInterval('P' . $i . 'M'));
            $start = $end->sub(new DateInterval('P1M'))->modify('+1 day');

            ['label' => $wagePeriod, 'start_raw' => $wageStartRaw, 'end_raw' => $wageEndRaw] = $this->wagePeriodForOffset($retireDate, $i, $closingDay);

            $rows[] = [
                'separation_period' => sprintf('%s～%s', $start->format('n/j'), $end->format('n/j')),
                'period_start_raw' => $start->format('Y-m-d'),
                'period_end_raw' => $end->format('Y-m-d'),
                'wage_start_raw' => $wageStartRaw,
                'wage_end_raw' => $wageEndRaw,
                'calendar_days' => (int) $start->diff($end)->days + 1,
                'wage_period' => $wagePeriod,
            ];
        }

        return $rows;
    }

    /**
     * @return array{label:string,start_raw:string,end_raw:string}
     */
    private function wagePeriodForOffset(DateTimeImmutable $retireDate, int $offset, string $closingDay): array
    {
        $closingDay = trim($closingDay);

        if ($closingDay === '') {
            return ['label' => '', 'start_raw' => '', 'end_raw' => ''];
        }

        if ($closingDay === '末') {
            if ($offset === 0) {
                $start = $retireDate->modify('first day of this month');
                $end = $retireDate;
            } else {
                $month = $retireDate->modify('first day of this month')->sub(new DateInterval('P' . $offset . 'M'));
                $start = $month;
                $end = $month->modify('last day of this month');
            }

            return [
                'label' => sprintf('%s～%s', $start->format('n/j'), $end->format('n/j')),
                'start_raw' => $start->format('Y-m-d'),
                'end_raw' => $end->format('Y-m-d'),
            ];
        }

        $closingNumber = (int) preg_replace('/\D+/', '', $closingDay);
        if ($closingNumber <= 0) {
            return ['label' => '', 'start_raw' => '', 'end_raw' => ''];
        }

        $currentMonthClose = $this->closingDateForMonth($retireDate->modify('first day of this month'), $closingNumber);
        $baseEnd = $retireDate > $currentMonthClose
            ? $currentMonthClose
            : $this->closingDateForMonth($retireDate->modify('first day of this month')->sub(new DateInterval('P1M')), $closingNumber);

        if ($offset === 0) {
            $start = $baseEnd->modify('+1 day');
            $end = $retireDate;
        } else {
            $end = $this->closingDateForMonth(
                $baseEnd->modify('first day of this month')->sub(new DateInterval('P' . ($offset - 1) . 'M')),
                $closingNumber
            );
            $previousClose = $this->closingDateForMonth(
                $end->modify('first day of this month')->sub(new DateInterval('P1M')),
                $closingNumber
            );
            $start = $previousClose->modify('+1 day');
        }

        return [
            'label' => sprintf('%s～%s', $start->format('n/j'), $end->format('n/j')),
            'start_raw' => $start->format('Y-m-d'),
            'end_raw' => $end->format('Y-m-d'),
        ];
    }

    private function closingDateForMonth(DateTimeImmutable $monthStart, int $closingNumber): DateTimeImmutable
    {
        $monthStart = $monthStart->modify('first day of this month');
        $lastDay = (int) $monthStart->format('t');
        $day = min(max(1, $closingNumber), $lastDay);

        return $monthStart->setDate(
            (int) $monthStart->format('Y'),
            (int) $monthStart->format('m'),
            $day
        );
    }

    /** @return list<object> */
    private function payrollRows(string $staffId, int $limit): array
    {
        if ($staffId === '') {
            return [];
        }

        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->select([
                'supply_month',
                'work_in_num',
                'absence_num',
                'horiday_true',
                'work_time',
                'work_time_num',
                'taxation_sum',
                'not_taxation_sum',
                'supply_sum',
                'bonus',
            ])
            ->where('bonus', 0)
            ->where('kyuyo_staff_id', $staffId)
            ->whereNotNull('supply_month')
            ->orderByDesc('supply_month')
            ->limit($limit)
            ->get()
            ->values()
            ->all();
    }

    /** @param array<string,mixed> $profile */
    private function basisDays(object $payroll, array $profile, string $wageStartRaw, string $wageEndRaw): float
    {
        $wageStart = $this->toDate($wageStartRaw);
        $wageEnd = $this->toDate($wageEndRaw);
        if ($wageStart === null || $wageEnd === null) {
            return 0.0;
        }

        $calendarDays = (int) $wageStart->diff($wageEnd)->days + 1;
        $absenceDays = $this->floatValue($payroll->absence_num ?? null);
        $workInDays = $this->floatValue($payroll->work_in_num ?? null);
        $paidLeaveDays = $this->floatValue($payroll->horiday_true ?? null);

        if ($this->isHourlyOrDaily($profile)) {
            return round($workInDays + $paidLeaveDays, 1);
        }

        return round(max(0.0, $calendarDays - $absenceDays), 1);
    }

    /** @param array<string,mixed> $profile */
    private function isHourlyOrDaily(array $profile): bool
    {
        $division = trim((string) ($profile['staff_division'] ?? ''));
        $employment = trim((string) ($profile['employment'] ?? ''));

        return str_contains($division, 'パート')
            || str_contains($division, 'アルバイト')
            || str_contains($division, '日給')
            || str_contains($division, '時給')
            || str_contains($employment, 'パート')
            || str_contains($employment, 'アルバイト')
            || str_contains($employment, '日給')
            || str_contains($employment, '時給');
    }

    private function emptyProfile(): array
    {
        return [
            'staff_id' => '',
            'staff_name' => '',
            'staff_name_kana' => '',
            'join_date' => '',
            'retire_date' => '',
            'staff_division' => '',
            'employment' => '',
            'company_name' => '',
            'store_name' => '',
            'company_post_num' => '',
            'post_num' => '',
            'address' => '',
            'home_tel' => '',
            'mobile_tel' => '',
            'my_number' => '',
            'koyou_num' => '',
            'company_address' => '',
            'company_tel' => '',
            'labor_insurance_number' => '',
            'closing_day' => '',
        ];
    }

    private function dateValue(mixed $value): string
    {
        $date = $this->toDate($value);
        return $date?->format('Y/m/d') ?? '';
    }

    private function toDate(mixed $value): ?DateTimeImmutable
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        $ts = strtotime($raw);
        if ($ts === false) {
            return null;
        }

        return new DateTimeImmutable(date('Y-m-d', $ts));
    }

    private function floatValue(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }
}

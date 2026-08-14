<?php

namespace App\Services\Admin\V2\Report;

use Illuminate\Support\Facades\DB;

class ReportV2BonusPaymentService
{
    /** @return list<int> */
    public function availableYears(): array
    {
        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->selectRaw('YEAR([supply_month]) as report_year')
            ->where('bonus', 1)
            ->whereNotNull('supply_month')
            ->groupByRaw('YEAR([supply_month])')
            ->orderByRaw('YEAR([supply_month]) desc')
            ->pluck('report_year')
            ->map(static fn ($year): int => (int) $year)
            ->filter(static fn (int $year): bool => $year > 0)
            ->values()
            ->all();
    }

    /** @return list<string> */
    public function companyOptions(int $year): array
    {
        $staffIds = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 1)
            ->whereNotNull('supply_month')
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->pluck('kyuyo_staff_id')
            ->map(static fn ($value): string => trim((string) $value))
            ->filter(static fn (string $value): bool => $value !== '')
            ->unique()
            ->values()
            ->all();

        if ($staffIds === []) {
            return [];
        }

        return DB::connection('sqlsrv')
            ->table('dbo.mx_staffs as s')
            ->leftJoin('dbo.mx_stores as st', 'st.store_code', '=', 's.section')
            ->leftJoin('dbo.mx_companies as c', 'c.company_id', '=', 'st.company_id')
            ->whereIn(DB::raw('LTRIM(RTRIM(s.staff_id))'), $staffIds)
            ->whereNotNull('c.company_name')
            ->whereRaw('LTRIM(RTRIM(c.company_name)) <> ?', [''])
            ->distinct()
            ->orderBy('c.company_name')
            ->pluck('c.company_name')
            ->map(static fn ($value): string => trim((string) $value))
            ->filter(static fn (string $value): bool => $value !== '')
            ->values()
            ->all();
    }

    /** @return list<string> */
    public function paymentMonthOptions(int $year, string $companyName = ''): array
    {
        $bonusRows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->select(['kyuyo_staff_id', 'supply_month'])
            ->where('bonus', 1)
            ->whereNotNull('supply_month')
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->get();

        if ($bonusRows->isEmpty()) {
            return [];
        }

        $staffIds = $bonusRows
            ->pluck('kyuyo_staff_id')
            ->map(static fn ($value): string => trim((string) $value))
            ->filter(static fn (string $value): bool => $value !== '')
            ->unique()
            ->values()
            ->all();

        $allowedStaffIds = $staffIds;
        if ($companyName !== '') {
            $allowedStaffIds = DB::connection('sqlsrv')
                ->table('dbo.mx_staffs as s')
                ->leftJoin('dbo.mx_stores as st', 'st.store_code', '=', 's.section')
                ->leftJoin('dbo.mx_companies as c', 'c.company_id', '=', 'st.company_id')
                ->whereIn(DB::raw('LTRIM(RTRIM(s.staff_id))'), $staffIds)
                ->where('c.company_name', $companyName)
                ->pluck('s.staff_id')
                ->map(static fn ($value): string => trim((string) $value))
                ->filter(static fn (string $value): bool => $value !== '')
                ->unique()
                ->values()
                ->all();
        }

        return $bonusRows
            ->filter(static function ($row) use ($allowedStaffIds): bool {
                return in_array(trim((string) ($row->kyuyo_staff_id ?? '')), $allowedStaffIds, true);
            })
            ->map(static fn ($row): string => date('Y-m', strtotime((string) ($row->supply_month ?? ''))))
            ->filter(static fn (string $value): bool => $value !== '')
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function rows(int $year, string $companyName = '', string $paymentMonth = ''): array
    {
        $bonusRows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->select([
                'kyuyo_sho_no',
                'kyuyo_staff_id',
                'supply_month',
                'bonus_amo',
                'kenpo',
                'kaigo',
                'kounen',
                'koyou',
                'syaho_sum',
                'income_tax',
                'syaho_target_sum',
                'fuyo_sum',
            ])
            ->where('bonus', 1)
            ->whereNotNull('supply_month')
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->when($paymentMonth !== '', function ($query) use ($paymentMonth) {
                $query->whereRaw('CONVERT(char(7), [supply_month], 120) = ?', [$paymentMonth]);
            })
            ->orderByDesc('supply_month')
            ->orderBy('kyuyo_staff_id')
            ->get();

        $staffIds = $bonusRows
            ->pluck('kyuyo_staff_id')
            ->map(static fn ($value): string => trim((string) $value))
            ->filter(static fn (string $value): bool => $value !== '')
            ->unique()
            ->values()
            ->all();

        if ($staffIds === []) {
            return [];
        }

        $staffRowsQuery = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs as s')
            ->leftJoin('dbo.mx_stores as st', 'st.store_code', '=', 's.section')
            ->leftJoin('dbo.mx_companies as c', 'c.company_id', '=', 'st.company_id')
            ->select([
                's.staff_id',
                's.staff_name',
                's.staff_name_furi',
                's.birthday',
                's.sex',
                's.staff_division',
                's.syaho_seiri_num',
                'st.store_name',
                'c.company_name',
                'c.health_office_code',
            ])
            ->whereIn(DB::raw('LTRIM(RTRIM(s.staff_id))'), $staffIds);

        if ($companyName !== '') {
            $staffRowsQuery->where('c.company_name', $companyName);
        }

        $staffRows = $staffRowsQuery->get();

        $staffMap = [];
        foreach ($staffRows as $staffRow) {
            $staffId = trim((string) ($staffRow->staff_id ?? ''));
            if ($staffId === '') {
                continue;
            }

            $staffMap[$staffId] = [
                'staff_id' => $staffId,
                'staff_name' => trim((string) ($staffRow->staff_name ?? '')),
                'staff_name_furi' => trim((string) ($staffRow->staff_name_furi ?? '')),
                'birthday_wareki' => $this->warekiCode($staffRow->birthday ?? null),
                'sex' => trim((string) ($staffRow->sex ?? '')),
                'staff_division' => trim((string) ($staffRow->staff_division ?? '')),
                'syaho_seiri_num' => trim((string) ($staffRow->syaho_seiri_num ?? '')),
                'syaho_seiri_num_padded' => str_pad(trim((string) ($staffRow->syaho_seiri_num ?? '')), 2, '0', STR_PAD_LEFT),
                'store_name' => trim((string) ($staffRow->store_name ?? '')),
                'company_name' => trim((string) ($staffRow->company_name ?? '')),
                'health_office_code' => trim((string) ($staffRow->health_office_code ?? '')),
            ];
        }

        $rows = [];
        foreach ($bonusRows as $bonusRow) {
            $staffId = trim((string) ($bonusRow->kyuyo_staff_id ?? ''));
            if ($staffId === '' || !isset($staffMap[$staffId])) {
                continue;
            }

            $staff = $staffMap[$staffId];
            $rows[] = [
                'kyuyo_sho_no' => (int) ($bonusRow->kyuyo_sho_no ?? 0),
                'staff_id' => $staffId,
                'staff_name' => $staff['staff_name'],
                'staff_name_furi' => $staff['staff_name_furi'],
                'birthday_wareki' => $staff['birthday_wareki'],
                'sex' => $staff['sex'],
                'staff_division' => $staff['staff_division'],
                'syaho_seiri_num' => $staff['syaho_seiri_num'],
                'syaho_seiri_num_padded' => $staff['syaho_seiri_num_padded'],
                'company_name' => $staff['company_name'],
                'store_name' => $staff['store_name'],
                'health_office_code' => $staff['health_office_code'],
                'payment_date' => $this->dateValue($bonusRow->supply_month ?? null),
                'bonus_amo' => $this->floatValue($bonusRow->bonus_amo ?? null),
                'bonus_total_rounded' => floor($this->floatValue($bonusRow->bonus_amo ?? null) / 1000) * 1000,
                'syaho_target_sum' => $this->floatValue($bonusRow->syaho_target_sum ?? null),
                'kenpo' => $this->floatValue($bonusRow->kenpo ?? null),
                'kaigo' => $this->floatValue($bonusRow->kaigo ?? null),
                'kounen' => $this->floatValue($bonusRow->kounen ?? null),
                'koyou' => $this->floatValue($bonusRow->koyou ?? null),
                'syaho_sum' => $this->floatValue($bonusRow->syaho_sum ?? null),
                'income_tax' => $this->floatValue($bonusRow->income_tax ?? null),
                'fuyo_sum' => (int) ($bonusRow->fuyo_sum ?? 0),
            ];
        }

        return $rows;
    }

    private function dateValue(mixed $value): string
    {
        $raw = trim((string) $value);
        if ($raw === '' || strtotime($raw) === false) {
            return '';
        }

        return date('Y/m/d', strtotime($raw));
    }

    private function floatValue(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function warekiCode(mixed $birthday): string
    {
        $raw = trim((string) $birthday);
        if ($raw === '' || strtotime($raw) === false) {
            return '';
        }

        $ts = strtotime($raw);
        $date = date('Y-m-d', $ts);
        if ($date >= '2019-05-01') {
            return '9-' . sprintf('%02d%s', (int) date('Y', $ts) - 2018, date('md', $ts));
        }
        if ($date >= '1989-01-08') {
            return '7-' . sprintf('%02d%s', (int) date('Y', $ts) - 1988, date('md', $ts));
        }
        if ($date >= '1926-12-25') {
            return '5-' . sprintf('%02d%s', (int) date('Y', $ts) - 1925, date('md', $ts));
        }

        return date('Ymd', $ts);
    }
}

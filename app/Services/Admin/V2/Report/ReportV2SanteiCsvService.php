<?php

namespace App\Services\Admin\V2\Report;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportV2SanteiCsvService
{
    public function __construct(
        private readonly ReportV2SanteiService $santeiService,
    ) {
    }

    public function build(int $year, string $companyName, string $outputDate): string
    {
        $rows = $this->santeiService->rows($year, $companyName);
        if ($rows === []) {
            return '';
        }

        $company = $this->companyMeta($companyName, $rows[0]);
        $staffKanaMap = $this->staffKanaMap($rows);

        $lines = [];
        $lines[] = implode(',', [
            '42',
            $company['prefecture_code'],
            $company['business_code'],
            str_pad((string) ($this->eraYear($year . '-06-15') + 2), 3, '0', STR_PAD_LEFT),
            $outputDate,
            '22223',
        ]);
        $lines[] = '[kanri]';
        $lines[] = ',001';
        $lines[] = implode(',', [
            '42',
            $company['prefecture_code'],
            $company['business_code'],
            $company['office_number'],
            $company['postal_1'],
            $company['postal_2'],
            $company['company_address'],
            $this->sanitizeCompanyNameForCsv($company['company_name']),
            $company['ceo_name'],
            $company['tel_1'],
            $company['tel_2'],
            $company['tel_3'],
        ]);
        $lines[] = '[data]';

        foreach ($rows as $row) {
            $month4 = $row['months'][4] ?? [];
            $month5 = $row['months'][5] ?? [];
            $month6 = $row['months'][6] ?? [];

            $m4Amount = (int) round((float) ($month4['syaho_target_sum'] ?? 0), 0, PHP_ROUND_HALF_UP);
            $m5Amount = (int) round((float) ($month5['syaho_target_sum'] ?? 0), 0, PHP_ROUND_HALF_UP);
            $m6Amount = (int) round((float) ($month6['syaho_target_sum'] ?? 0), 0, PHP_ROUND_HALF_UP);
            $sumAmount = $m4Amount + $m5Amount + $m6Amount;

            $cols = array_fill(0, 53, '');
            $cols[0] = '2225700';
            $cols[1] = '42';
            $cols[2] = $company['prefecture_code'];
            $cols[3] = $company['business_code'];
            $cols[4] = str_pad((string) ($row['syaho_seiri_num'] ?? ''), 6, '0', STR_PAD_LEFT);
            $cols[5] = $this->sanitizeKanaForCsv($staffKanaMap[$row['staff_id']] ?? '');
            $cols[6] = (string) ($row['staff_name'] ?? '');
            $cols[7] = $this->birthdayWarekiCode((string) ($row['birthday_wareki'] ?? ''));
            $cols[8] = $this->birthdayWarekiYymmdd((string) ($row['birthday_wareki'] ?? ''));
            $cols[9] = '9';
            $cols[10] = sprintf('%02d', $this->eraYear($year . '-06-15'));
            $cols[11] = '09';
            $cols[12] = $this->monthlyAmoCode((float) ($row['shaho_info']['kenpo_monthly_amo'] ?? 0));
            $cols[13] = $this->monthlyAmoCode((float) ($row['shaho_info']['kounen_monthly_amo'] ?? 0));
            $cols[14] = '9';
            $cols[15] = sprintf('%02d', $this->eraYear(($year - 1) . '-06-15'));
            $cols[16] = '09';
            $cols[21] = '04';
            $cols[22] = '05';
            $cols[23] = '06';
            $cols[24] = $this->dayValue($month4['basis_days'] ?? 0);
            $cols[25] = $this->dayValue($month5['basis_days'] ?? 0);
            $cols[26] = $this->dayValue($month6['basis_days'] ?? 0);
            $cols[27] = (string) $m4Amount;
            $cols[28] = (string) $m5Amount;
            $cols[29] = (string) $m6Amount;
            $cols[30] = '0000000';
            $cols[31] = '0000000';
            $cols[32] = '0000000';
            $cols[33] = (string) $m4Amount;
            $cols[34] = (string) $m5Amount;
            $cols[35] = (string) $m6Amount;
            $cols[36] = (string) $sumAmount;
            $cols[37] = (string) floor($sumAmount / 3);

            $lines[] = implode(',', $cols);
        }

        return implode("\r\n", $lines) . "\r\n";
    }

    /** @param array<string,mixed> $fallback */
    private function companyMeta(string $companyName, array $fallback): array
    {
        $companySelects = [
            'c.company_name',
            'c.company_address',
            'c.office_number',
            'c.health_office_code',
            'c.tel',
            'c.ceo_name',
            'st.postal_code',
        ];

        if ($this->hasCompanyColumn('company_name_kana')) {
            $companySelects[] = 'c.company_name_kana';
        }

        $row = DB::connection('sqlsrv')
            ->table('dbo.mx_companies as c')
            ->leftJoin('dbo.mx_stores as st', 'st.company_id', '=', 'c.company_id')
            ->where('c.company_name', $companyName)
            ->orderBy('st.store_code')
            ->first($companySelects);

        $postal = $this->postalParts((string) ($row->postal_code ?? ''));
        $tel = $this->telParts((string) ($row->tel ?? ($fallback['tel'] ?? '')));

        return [
            'prefecture_code' => $this->prefectureCode((string) ($row->health_office_code ?? ($fallback['health_office_code'] ?? ''))),
            'business_code' => $this->businessCode((string) ($row->health_office_code ?? ($fallback['health_office_code'] ?? ''))),
            'company_name' => (string) ($row->company_name ?? ($fallback['company_name_formatted'] ?? $companyName)),
            'company_address' => (string) ($row->company_address ?? ($fallback['company_address'] ?? '')),
            'office_number' => trim((string) ($row->office_number ?? '')),
            'health_office_code' => trim((string) ($row->health_office_code ?? ($fallback['health_office_code'] ?? ''))),
            'ceo_name' => (string) ($row->ceo_name ?? ($fallback['ceo_name'] ?? '')),
            'postal_1' => $postal[0],
            'postal_2' => $postal[1],
            'tel_1' => $tel[0],
            'tel_2' => $tel[1],
            'tel_3' => $tel[2],
        ];
    }

    /** @param list<array<string,mixed>> $rows @return array<string,string> */
    private function staffKanaMap(array $rows): array
    {
        $staffIds = array_values(array_filter(array_map(
            static fn (array $row): string => trim((string) ($row['staff_id'] ?? '')),
            $rows
        )));

        if ($staffIds === []) {
            return [];
        }

        $map = [];
        $staffRows = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->whereIn('staff_id', $staffIds)
            ->get(['staff_id', 'staff_name_furi']);

        foreach ($staffRows as $staffRow) {
            $staffId = trim((string) ($staffRow->staff_id ?? ''));
            if ($staffId === '') {
                continue;
            }

            $map[$staffId] = trim((string) ($staffRow->staff_name_furi ?? ''));
        }

        return $map;
    }

    private function hasCompanyColumn(string $column): bool
    {
        try {
            return Schema::connection('sqlsrv')->hasColumn('mx_companies', $column)
                || Schema::connection('sqlsrv')->hasColumn('dbo.mx_companies', $column);
        } catch (\Throwable) {
            return false;
        }
    }

    private function monthlyAmoCode(float $amount): string
    {
        $value = (int) round($amount / 1000, 0, PHP_ROUND_HALF_UP);
        return str_pad((string) $value, 4, '0', STR_PAD_LEFT);
    }

    private function dayValue(mixed $days): string
    {
        $value = is_numeric($days) ? (float) $days : 0.0;
        if (abs($value - floor($value)) < 0.0001) {
            return (string) (int) $value;
        }

        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
    }

    private function birthdayWarekiCode(string $birthdayWareki): string
    {
        $birthdayWareki = trim($birthdayWareki);
        if ($birthdayWareki === '') {
            return '';
        }

        $parts = preg_split('/-/', $birthdayWareki, 2);
        if (is_array($parts) && count($parts) === 2) {
            return trim((string) $parts[0]);
        }

        $digits = preg_replace('/\D+/', '', $birthdayWareki) ?? '';
        if (strlen($digits) >= 7) {
            return substr($digits, 0, 1);
        }

        return '';
    }

    private function birthdayWarekiYymmdd(string $birthdayWareki): string
    {
        $birthdayWareki = trim($birthdayWareki);
        if ($birthdayWareki === '') {
            return '';
        }

        $parts = preg_split('/-/', $birthdayWareki, 2);
        if (is_array($parts) && count($parts) === 2) {
            $digits = preg_replace('/\D+/', '', (string) $parts[1]) ?? '';
            if ($digits !== '') {
                return str_pad(substr($digits, -6), 6, '0', STR_PAD_LEFT);
            }
        }

        $digits = preg_replace('/\D+/', '', $birthdayWareki) ?? '';
        if ($digits === '') {
            return '';
        }

        if (strlen($digits) > 6) {
            $digits = substr($digits, -6);
        }

        return str_pad($digits, 6, '0', STR_PAD_LEFT);
    }

    /** @return array{0:string,1:string} */
    private function postalParts(string $postal): array
    {
        if (preg_match('/(\d{3})-?(\d{4})/u', $postal, $m) === 1) {
            return [$m[1], $m[2]];
        }

        return ['', ''];
    }

    /** @return array{0:string,1:string,2:string} */
    private function telParts(string $tel): array
    {
        $tel = trim($tel);
        if ($tel === '') {
            return ['', '', ''];
        }

        if (preg_match('/^(\d+)-(\d+)-(\d+)$/', $tel, $m) === 1) {
            return [$m[1], $m[2], $m[3]];
        }

        $digits = preg_replace('/\D+/', '', $tel) ?? '';
        if (strlen($digits) === 10) {
            return [substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6, 4)];
        }
        if (strlen($digits) === 11) {
            return [substr($digits, 0, 3), substr($digits, 3, 4), substr($digits, 7, 4)];
        }

        return [$tel, '', ''];
    }

    private function eraYear(string $date): int
    {
        $ts = strtotime($date);
        if ($ts === false) {
            return 0;
        }

        $d = new \DateTimeImmutable(date('Y-m-d', $ts));
        $reiwa = new \DateTimeImmutable('2019-05-01');
        $heisei = new \DateTimeImmutable('1989-01-08');
        $showa = new \DateTimeImmutable('1926-12-25');

        if ($d >= $reiwa) {
            return ((int) $d->format('Y')) - 2018;
        }
        if ($d >= $heisei) {
            return ((int) $d->format('Y')) - 1988;
        }
        if ($d >= $showa) {
            return ((int) $d->format('Y')) - 1925;
        }

        return 0;
    }

    private function prefectureCode(string $healthOfficeCode): string
    {
        $healthOfficeCode = trim($healthOfficeCode);
        if ($healthOfficeCode === '') {
            return '';
        }

        if (preg_match('/(\d{2})/u', $healthOfficeCode, $m) === 1) {
            return $m[1];
        }

        return '';
    }

    private function businessCode(string $healthOfficeCode): string
    {
        $healthOfficeCode = trim($healthOfficeCode);
        if ($healthOfficeCode === '') {
            return '';
        }

        if (preg_match('/^\s*(\d{2})[-ｰー－ ]*([0-9A-Za-z]+)\s*$/u', $healthOfficeCode, $m) === 1) {
            return mb_convert_kana($m[2], 'as', 'UTF-8');
        }

        $trimmed = preg_replace('/^\s*\d{2}[-ｰー－ ]*/u', '', $healthOfficeCode) ?? '';
        $trimmed = trim($trimmed);
        if ($trimmed !== '') {
            return mb_convert_kana($trimmed, 'as', 'UTF-8');
        }

        $digits = preg_replace('/\D+/', '', $healthOfficeCode) ?? '';
        if (strlen($digits) >= 3) {
            $code = ltrim(substr($digits, 2), '0');
            return $code !== '' ? $code : substr($digits, 2);
        }

        return '';
    }

    private function sanitizeCompanyNameForCsv(string $companyName): string
    {
        $companyName = trim($companyName);
        if ($companyName === '') {
            return '';
        }

        return str_replace(['㈱', '(株)', '（株）'], '株式会社', $companyName);
    }

    private function sanitizeKanaForCsv(string $kana): string
    {
        $kana = trim($kana);
        if ($kana === '') {
            return '';
        }

        $kana = str_replace(['　', '縲'], ' ', $kana);
        $kana = mb_convert_kana($kana, 'CV', 'UTF-8');
        $kana = preg_replace('/\s+/u', ' ', $kana) ?? $kana;

        return trim($kana);
    }
}

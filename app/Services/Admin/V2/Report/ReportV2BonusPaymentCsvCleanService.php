<?php

namespace App\Services\Admin\V2\Report;

use Illuminate\Support\Facades\DB;

class ReportV2BonusPaymentCsvCleanService
{
    public function __construct(
        private readonly ReportV2BonusPaymentService $bonusPaymentService,
    ) {
    }

    public function build(int $year, string $companyName, string $paymentMonth, string $outputDate, string $mediaNo = '020'): string
    {
        $rows = $this->bonusPaymentService->rows($year, $companyName, $paymentMonth);
        if ($rows === []) {
            return '';
        }

        $company = $this->companyMeta($companyName, $rows[0]);
        $headerParts = $this->healthOfficeParts($company['health_office_code']);

        $lines = [];
        $lines[] = implode(',', [
            '42',
            $headerParts['left_code'],
            $headerParts['right_code'],
            str_pad(trim($mediaNo), 3, '0', STR_PAD_LEFT),
            $outputDate,
            '22223',
        ]);
        $lines[] = '[kanri]';
        $lines[] = ',001';
        $lines[] = implode(',', [
            '42',
            $headerParts['left_code'],
            $headerParts['right_code'],
            $company['office_number'],
            $company['postal_1'],
            $company['postal_2'],
            $company['company_address'],
            $this->sanitizeCompanyName($company['company_name']),
            trim($company['ceo_name']),
            $company['tel_1'],
            $company['tel_2'],
            $company['tel_3'],
        ]);
        $lines[] = '[data]';

        foreach ($rows as $row) {
            $birthdayParts = $this->warekiCodeParts((string) ($row['birthday_wareki'] ?? ''));
            $paymentParts = $this->warekiDateParts((string) ($row['payment_date'] ?? ''));
            $cols = array_fill(0, 21, '');
            $cols[0] = '2265700';
            $cols[1] = '42';
            $cols[2] = $headerParts['left_code'];
            $cols[3] = $headerParts['right_code'];
            $cols[4] = str_pad(trim((string) ($row['syaho_seiri_num'] ?? '')), 6, '0', STR_PAD_LEFT);
            $cols[5] = $this->sanitizeKana((string) ($row['staff_name_furi'] ?? ''));
            $cols[6] = trim((string) ($row['staff_name'] ?? ''));
            $cols[7] = $birthdayParts['era_code'];
            $cols[8] = $birthdayParts['yymmdd'];
            $cols[9] = $paymentParts['era_code'];
            $cols[10] = $paymentParts['yymmdd'];
            $cols[11] = $this->moneyValue((float) ($row['bonus_amo'] ?? 0));
            $cols[12] = '0';
            $cols[13] = $this->moneyValue((float) ($row['bonus_total_rounded'] ?? 0));

            $lines[] = implode(',', $cols);
        }

        return implode("\r\n", $lines) . "\r\n";
    }

    /** @param array<string,mixed> $fallback */
    private function companyMeta(string $companyName, array $fallback): array
    {
        $row = DB::connection('sqlsrv')
            ->table('dbo.mx_companies as c')
            ->leftJoin('dbo.mx_stores as st', 'st.company_id', '=', 'c.company_id')
            ->where('c.company_name', $companyName)
            ->orderBy('st.store_code')
            ->first([
                'c.company_name',
                'c.company_address',
                'c.office_number',
                'c.health_office_code',
                'c.tel',
                'c.ceo_name',
                'st.postal_code',
            ]);

        $postal = $this->postalParts((string) ($row->postal_code ?? ''));
        $tel = $this->telParts((string) ($row->tel ?? ''));

        return [
            'company_name' => trim((string) ($row->company_name ?? ($fallback['company_name'] ?? $companyName))),
            'company_address' => trim((string) ($row->company_address ?? '')),
            'office_number' => trim((string) ($row->office_number ?? '')),
            'health_office_code' => trim((string) ($row->health_office_code ?? ($fallback['health_office_code'] ?? ''))),
            'ceo_name' => trim((string) ($row->ceo_name ?? '')),
            'postal_1' => $postal[0],
            'postal_2' => $postal[1],
            'tel_1' => $tel[0],
            'tel_2' => $tel[1],
            'tel_3' => $tel[2],
        ];
    }

    /** @return array{left_code:string,right_code:string} */
    private function healthOfficeParts(string $healthOfficeCode): array
    {
        $raw = trim($healthOfficeCode);
        if ($raw === '') {
            return ['left_code' => '', 'right_code' => ''];
        }

        $normalized = preg_replace('/\s+/u', '', $raw) ?? $raw;
        $left = mb_substr($normalized, 0, 2, 'UTF-8');
        $right = mb_substr($normalized, -3, null, 'UTF-8');

        if (preg_match('/^\d{2}$/u', $left) !== 1 || $right === '') {
            return ['left_code' => '', 'right_code' => ''];
        }

        return [
            'left_code' => $left,
            'right_code' => $right,
        ];
    }

    /** @return array{era_code:string,yymmdd:string} */
    private function warekiCodeParts(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return ['era_code' => '', 'yymmdd' => ''];
        }

        $parts = preg_split('/-/', $value, 2);
        if (is_array($parts) && count($parts) === 2) {
            $digits = preg_replace('/\D+/', '', (string) $parts[1]) ?? '';
            return [
                'era_code' => trim((string) $parts[0]),
                'yymmdd' => str_pad(substr($digits, -6), 6, '0', STR_PAD_LEFT),
            ];
        }

        return ['era_code' => '', 'yymmdd' => ''];
    }

    /** @return array{era_code:string,yymmdd:string} */
    private function warekiDateParts(string $date): array
    {
        $ts = strtotime($date);
        if ($ts === false) {
            return ['era_code' => '', 'yymmdd' => ''];
        }

        $ymd = date('Y-m-d', $ts);
        if ($ymd >= '2019-05-01') {
            return ['era_code' => '9', 'yymmdd' => sprintf('%02d%s', (int) date('Y', $ts) - 2018, date('md', $ts))];
        }
        if ($ymd >= '1989-01-08') {
            return ['era_code' => '7', 'yymmdd' => sprintf('%02d%s', (int) date('Y', $ts) - 1988, date('md', $ts))];
        }
        if ($ymd >= '1926-12-25') {
            return ['era_code' => '5', 'yymmdd' => sprintf('%02d%s', (int) date('Y', $ts) - 1925, date('md', $ts))];
        }

        return ['era_code' => '', 'yymmdd' => date('ymd', $ts)];
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

    private function sanitizeKana(string $kana): string
    {
        $kana = trim($kana);
        if ($kana === '') {
            return '';
        }

        $kana = mb_convert_kana($kana, 'kVs', 'UTF-8');
        $kana = str_replace("\u{3000}", ' ', $kana);
        $kana = preg_replace('/\s+/u', ' ', $kana) ?? $kana;

        return trim($kana);
    }

    private function sanitizeCompanyName(string $companyName): string
    {
        return str_replace(['㈱', '(株)', '（株）'], '株式会社', trim($companyName));
    }

    private function moneyValue(float $amount): string
    {
        return (string) ((int) floor($amount));
    }
}

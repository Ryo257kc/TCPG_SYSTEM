<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;

class PayrollV2MonthService
{
    /** @return list<string> */
    public function availablePaymentDates(bool $bonus = false): array
    {
        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->selectRaw('CONVERT(date, [supply_month]) as payment_date')
            ->where('bonus', $bonus ? 1 : 0)
            ->whereNotNull('supply_month')
            ->groupByRaw('CONVERT(date, [supply_month])')
            ->orderByRaw('CONVERT(date, [supply_month]) desc')
            ->get()
            ->map(static function ($row): string {
                $value = trim((string) ($row->payment_date ?? ''));
                return $value === '' ? '' : date('Y-m-d', strtotime($value));
            })
            ->filter(static fn(string $value): bool => $value !== '')
            ->values()
            ->all();
    }

    public function normalizePaymentDate(string $selectedPaymentDate, array $availablePaymentDates): string
    {
        $selectedPaymentDate = trim($selectedPaymentDate);
        if ($selectedPaymentDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedPaymentDate)) {
            return $selectedPaymentDate;
        }

        return $availablePaymentDates[0] ?? now('Asia/Tokyo')->format('Y-m-d');
    }

    public function monthFromPaymentDate(string $paymentDate): string
    {
        $ts = strtotime($paymentDate);
        if ($ts === false) {
            return now('Asia/Tokyo')->format('Y-m');
        }

        return date('Y-m', $ts);
    }
}

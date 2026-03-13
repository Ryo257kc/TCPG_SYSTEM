<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;

class PayrollV2CreateCandidatesService
{
    /**
     * @return array{candidates:list<array{staff_id:string,staff_name:string,division:string,store_name:string,company_name:string,retire_date:string,candidate_type:string,existing:bool}>,suggested_payment_date:string,payment_date_options:list<string>}
     */
    public function payload(int $year, int $month, string $companyName = '', string $paymentDate = ''): array
    {
        [$targetYear, $targetMonth] = $this->targetYearMonth($year, $month, $paymentDate);

        return [
            'candidates' => $this->candidates($targetYear, $targetMonth, $companyName, $paymentDate),
            'suggested_payment_date' => $paymentDate !== '' ? $paymentDate : $this->suggestedPaymentDate($year, $month),
            'payment_date_options' => $this->paymentDateOptions(),
        ];
    }

    /**
     * @return list<array{staff_id:string,staff_name:string,division:string,store_name:string,company_name:string,retire_date:string,candidate_type:string,existing:bool}>
     */
    public function candidates(int $year, int $month, string $companyName = '', string $paymentDate = ''): array
    {
        if ($year < 2000 || $month < 1 || $month > 12) {
            return [];
        }

        $monthStart = new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month));
        $prevStart = $monthStart->modify('-1 month');
        $prevEnd = $monthStart->modify('-1 day');

        $query = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs as s')
            ->leftJoin('dbo.mx_stores as st', 'st.store_code', '=', 's.section')
            ->select([
                's.staff_id',
                's.staff_name',
                's.staff_division',
                's.tai_date',
                'st.store_name',
                'st.company_name',
            ])
            ->whereNotNull('s.staff_id')
            ->whereRaw('LTRIM(RTRIM(s.staff_id)) <> ?', [''])
            ->where(function ($q) use ($prevStart, $prevEnd) {
                $q->whereNull('s.tai_date')
                    ->orWhereBetween('s.tai_date', [
                        $prevStart->format('Y-m-d 00:00:00'),
                        $prevEnd->format('Y-m-d 23:59:59'),
                    ]);
            });

        $companyName = trim($companyName);
        if ($companyName !== '') {
            $query->whereRaw("LTRIM(RTRIM(ISNULL(st.company_name, ''))) = ?", [$companyName]);
        }

        $existingMap = $this->existingStaffMapByPaymentDate($paymentDate);

        return $query
            ->orderBy('s.staff_id')
            ->get()
            ->map(function ($row) use ($existingMap) {
                $staffId = trim((string) ($row->staff_id ?? ''));
                $retireDate = trim((string) ($row->tai_date ?? ''));

                return [
                    'staff_id' => $staffId,
                    'staff_name' => trim((string) ($row->staff_name ?? '')),
                    'division' => trim((string) ($row->staff_division ?? '')),
                    'store_name' => trim((string) ($row->store_name ?? '')),
                    'company_name' => trim((string) ($row->company_name ?? '')),
                    'retire_date' => $retireDate === '' ? '' : date('Y-m-d', strtotime($retireDate)),
                    'candidate_type' => $retireDate === '' ? 'active' : 'retired_prev_month',
                    'existing' => isset($existingMap[$staffId]),
                ];
            })
            ->filter(static fn (array $row) => $row['staff_id'] !== '')
            ->values()
            ->all();
    }

    public function suggestedPaymentDate(int $year, int $month): string
    {
        $value = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month])
            ->whereNotNull('supply_month')
            ->orderBy('supply_month')
            ->value('supply_month');

        if ($value === null) {
            return '';
        }

        $ts = strtotime((string) $value);
        return $ts === false ? '' : date('Y-m-d', $ts);
    }

    /** @return list<string> */
    public function paymentDateOptions(): array
    {
        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 0)
            ->whereNotNull('supply_month')
            ->orderBy('supply_month', 'desc')
            ->pluck('supply_month')
            ->map(static function ($value): string {
                $ts = strtotime((string) $value);
                return $ts === false ? '' : date('Y-m-d', $ts);
            })
            ->filter(static fn (string $value): bool => $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    /** @return array<string,true> */
    private function existingStaffMapByPaymentDate(string $paymentDate): array
    {
        $paymentDate = trim($paymentDate);
        if (!$this->isValidPaymentDate($paymentDate)) {
            return [];
        }

        $ids = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 0)
            ->whereRaw('CONVERT(date, [supply_month]) = ?', [$paymentDate])
            ->pluck('kyuyo_staff_id')
            ->map(static fn ($id) => trim((string) $id))
            ->filter(static fn ($id) => $id !== '')
            ->values()
            ->all();

        return array_fill_keys($ids, true);
    }

    /** @return array{0:int,1:int} */
    private function targetYearMonth(int $year, int $month, string $paymentDate): array
    {
        $paymentDate = trim($paymentDate);
        if ($this->isValidPaymentDate($paymentDate)) {
            return [(int) substr($paymentDate, 0, 4), (int) substr($paymentDate, 5, 2)];
        }

        return [$year, $month];
    }

    private function isValidPaymentDate(string $paymentDate): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $paymentDate) === 1;
    }
}
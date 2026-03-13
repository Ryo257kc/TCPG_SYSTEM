<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;

class PayrollV2CreateService
{
    public function __construct(
        private readonly PayrollV2CreateCandidatesService $createCandidatesService,
        private readonly PayrollV2RecalculateService $recalculateService,
    ) {
    }

    /**
     * @param list<string> $staffIds
     * @return array{created:int,skipped:int,created_ids:list<string>,skipped_ids:list<string>}
     */
    public function create(int $year, int $month, array $staffIds, string $companyName = '', string $paymentDate = ''): array
    {
        $result = [
            'created' => 0,
            'skipped' => 0,
            'created_ids' => [],
            'skipped_ids' => [],
        ];

        $staffIds = array_values(array_unique(array_filter(array_map(
            static fn ($id) => trim((string) $id),
            $staffIds
        ), static fn ($id) => $id !== '')));

        if ($staffIds === []) {
            return $result;
        }

        $paymentDate = trim($paymentDate);
        if (!$this->isValidPaymentDate($paymentDate)) {
            return $result;
        }

        $targetYear = (int) substr($paymentDate, 0, 4);
        $targetMonth = (int) substr($paymentDate, 5, 2);
        $supplyMonth = $paymentDate . ' 00:00:00';

        foreach ($staffIds as $staffId) {
            if ($this->exists($staffId, $paymentDate)) {
                $result['skipped']++;
                $result['skipped_ids'][] = $staffId;
                continue;
            }

            DB::connection('sqlsrv_payroll')
                ->table('dbo.mx_kyuyo_shou')
                ->insert([
                    'kyuyo_staff_id' => $staffId,
                    'supply_month' => $supplyMonth,
                    'bonus' => 0,
                    'attendance_checked' => 0,
                    'attendance_checked_at' => null,
                    'attendance_checked_by' => null,
                    'edit_lock' => 0,
                ]);

            $this->recalculateService->recalculate($staffId, $targetYear, $targetMonth, $companyName);

            $result['created']++;
            $result['created_ids'][] = $staffId;
        }

        return $result;
    }

    private function exists(string $staffId, string $paymentDate): bool
    {
        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 0)
            ->whereRaw('CONVERT(date, [supply_month]) = ?', [$paymentDate])
            ->whereRaw('LTRIM(RTRIM([kyuyo_staff_id])) = ?', [$staffId])
            ->exists();
    }

    private function isValidPaymentDate(string $paymentDate): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $paymentDate) === 1;
    }
}
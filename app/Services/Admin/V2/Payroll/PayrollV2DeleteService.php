<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;

class PayrollV2DeleteService
{
    /**
     * @param list<string> $staffIds
     * @return array{deleted:int,skipped:int,deleted_ids:list<string>,skipped_ids:list<string>}
     */
    public function delete(array $staffIds, string $paymentDate = ''): array
    {
        $result = [
            'deleted' => 0,
            'skipped' => 0,
            'deleted_ids' => [],
            'skipped_ids' => [],
        ];

        $staffIds = array_values(array_unique(array_filter(array_map(
            static fn ($id) => trim((string) $id),
            $staffIds
        ), static fn ($id) => $id !== '')));

        $paymentDate = trim($paymentDate);
        if ($staffIds === [] || preg_match('/^\d{4}-\d{2}-\d{2}$/', $paymentDate) !== 1) {
            return $result;
        }

        foreach ($staffIds as $staffId) {
            $deleted = DB::connection('sqlsrv_payroll')
                ->table('dbo.mx_kyuyo_shou')
                ->where('bonus', 0)
                ->whereRaw('CONVERT(date, [supply_month]) = ?', [$paymentDate])
                ->whereRaw('LTRIM(RTRIM([kyuyo_staff_id])) = ?', [$staffId])
                ->delete();

            if ($deleted > 0) {
                $result['deleted']++;
                $result['deleted_ids'][] = $staffId;
            } else {
                $result['skipped']++;
                $result['skipped_ids'][] = $staffId;
            }
        }

        return $result;
    }
}
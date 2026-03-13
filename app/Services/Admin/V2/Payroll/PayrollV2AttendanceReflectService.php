<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;

class PayrollV2AttendanceReflectService
{
    /**
     * 指定給与月の明細へ、前月明細の勤怠項目を反映する
     */
    public function reflect(string $staffId, int $year, int $month): int
    {
        $staffId = trim($staffId);
        if ($staffId === '' || $year < 2000 || $month < 1 || $month > 12) {
            return 0;
        }

        [$prevYear, $prevMonth] = $this->previousYearMonth($year, $month);

        $conn = DB::connection('sqlsrv_payroll');

        $current = $conn->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$year])
            ->whereRaw('MONTH([supply_month]) = ?', [$month])
            ->whereRaw('LTRIM(RTRIM([kyuyo_staff_id])) = ?', [$staffId])
            ->orderByDesc('kyuyo_sho_no')
            ->first(['kyuyo_sho_no']);

        if (!$current || !isset($current->kyuyo_sho_no)) {
            return 0;
        }

        $prev = $conn->table('dbo.mx_kyuyo_shou')
            ->where('bonus', 0)
            ->whereRaw('YEAR([supply_month]) = ?', [$prevYear])
            ->whereRaw('MONTH([supply_month]) = ?', [$prevMonth])
            ->whereRaw('LTRIM(RTRIM([kyuyo_staff_id])) = ?', [$staffId])
            ->orderByDesc('kyuyo_sho_no')
            ->first($this->sourceColumns());

        if (!$prev) {
            return 0;
        }

        $payload = [];
        foreach ($this->columnMap() as $from => $to) {
            $payload[$to] = $prev->{$from} ?? null;
        }

        return $conn->table('dbo.mx_kyuyo_shou')
            ->where('kyuyo_sho_no', (int) $current->kyuyo_sho_no)
            ->update($payload);
    }

    /** @return array<string,string> from=>to */
    private function columnMap(): array
    {
        return [
            'work_in_num' => 'work_in_num',
            'absence_num' => 'absence_num',
            'work_time' => 'work_time',
            'work_time_num' => 'work_time_num',
            'work_horiday_num' => 'work_horiday_num',
            'overtime' => 'overtime',
            'late_time' => 'late_time',
            'night_over_time' => 'night_over_time',
            'horiday_true' => 'horiday_true',
            'horiday_true_num' => 'horiday_true_num',
            'days_closed' => 'days_closed',
            'time_closed' => 'time_closed',
            'work_kiso_num' => 'work_kiso_num',
        ];
    }

    /** @return list<string> */
    private function sourceColumns(): array
    {
        return array_keys($this->columnMap());
    }

    /** @return array{0:int,1:int} */
    private function previousYearMonth(int $year, int $month): array
    {
        if ($month <= 1) {
            return [$year - 1, 12];
        }

        return [$year, $month - 1];
    }
}

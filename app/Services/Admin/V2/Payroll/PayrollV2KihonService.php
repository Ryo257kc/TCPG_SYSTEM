<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;

class PayrollV2KihonService
{
    /** @return array<string, array<string, mixed>> */

    // 基本マスタ表示
    public function map(?int $year = null, ?int $month = null): array
    {
        $rows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kihon')
            ->orderByDesc('decision_date')
            ->get();

        $groups = [];
        foreach ($rows as $rowObj) {
            $row = (array) $rowObj;
            $staffId = trim((string) ($row['staff_id'] ?? ''));
            if ($staffId === '') {
                continue;
            }
            $groups[$staffId][] = $row;
        }
        $targetDate = $this->targetPaymentMonthStart($year, $month);

        $map = [];
        foreach ($groups as $staffId => $list) {
            $map[$staffId] = $this->pickByDecisionDate($list, $targetDate);
        }

        return $map;
    }

    /** @param list<array<string,mixed>> $rows */
    private function pickByDecisionDate(array $rows, ?\DateTimeImmutable $targetDate): array
    {
        if ($rows === []) {
            return [];
        }

        if ($targetDate === null) {
            return $rows[0];
        }

        foreach ($rows as $row) {
            $d = $this->toDate($row['decision_date'] ?? null);
            if ($d === null) {
                continue;
            }

            if ($d->format('Y-m-d') < $targetDate->format('Y-m-d')) {
                return $row;
            }
        }

        return $rows[0];
    }

    private function targetSeptember(?int $year, ?int $month): ?\DateTimeImmutable
    {
        if ($year === null || $month === null) {
            return null;
        }
        $y = $month >= 10 ? $year : ($year - 1);
        return new \DateTimeImmutable(sprintf('%04d-09-30', $y));
    }

    private function targetPaymentMonthStart(?int $year, ?int $month): ?\DateTimeImmutable
    {
        if ($year === null || $month === null) {
            return null;
        }

        return new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
    }
    private function toDate(mixed $v): ?\DateTimeImmutable
    {
        if ($v === null) {
            return null;
        }
        $s = trim((string) $v);
        if ($s === '') {
            return null;
        }
        $ts = strtotime($s);
        if ($ts === false) {
            return null;
        }
        return (new \DateTimeImmutable())->setTimestamp($ts);
    }
}

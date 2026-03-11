<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;

class PayrollV2ResidentService
{
    /** @return array<string, array<string, mixed>> */
    public function map(?int $year = null, ?int $month = null): array
    {
        $rows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_resident')
            ->orderByDesc('target_month')
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

        $targetMonth = $this->payrollMonthDate($year, $month);
        $monthIndex = $this->residentMonthIndex($month);

        $map = [];
        foreach ($groups as $staffId => $list) {
            $picked = $this->pickByTargetMonth($list, $targetMonth);
            $map[$staffId] = $this->injectResidentTax($picked, $monthIndex);
        }

        return $map;
    }

    /** @param list<array<string,mixed>> $rows */
    private function pickByTargetMonth(array $rows, ?\DateTimeImmutable $targetMonth): array
    {
        if ($rows === []) {
            return [];
        }
        if ($targetMonth === null) {
            return $rows[0];
        }

        foreach ($rows as $row) {
            $d = $this->toDate($row['target_month'] ?? null);
            if ($d !== null && $d <= $targetMonth) {
                return $row;
            }
        }

        return $rows[0];
    }

    private function injectResidentTax(array $row, ?int $monthIndex): array
    {
        if ($row === [] || $monthIndex === null) {
            return $row;
        }

        foreach ($this->residentMonthKeys($monthIndex) as $key) {
            if (!array_key_exists($key, $row)) {
                continue;
            }
            $row['resident_tax'] = $row[$key];
            return $row;
        }

        return $row;
    }

    /** @return list<string> */
    private function residentMonthKeys(int $index): array
    {
        return [
            'resident_tax' . $index,
            'resident_tax_' . $index,
            (string) $index,
            sprintf('%02d', $index),
            'm' . $index,
            'month_' . $index,
            'resident_' . $index,
            'tax_' . $index,
        ];
    }

    private function residentMonthIndex(?int $month): ?int
    {
        if ($month === null || $month < 1 || $month > 12) {
            return null;
        }
        return (($month + 6) % 12) + 1;
    }

    private function payrollMonthDate(?int $year, ?int $month): ?\DateTimeImmutable
    {
        if ($year === null || $month === null) {
            return null;
        }
        return new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month));
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


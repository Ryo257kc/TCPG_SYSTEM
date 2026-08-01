<?php
// 各等級データ取得
namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;

class PayrollV2ShahoService
{
    /** @return array<string, array<string, mixed>> */
    public function map(?int $year = null, ?int $month = null): array
    {
        $rows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_staff_shou')
            ->orderByDesc('raise_year')
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

        $targetSep = $this->targetSeptember($year, $month);

        $map = [];
        foreach ($groups as $staffId => $list) {
            $map[$staffId] = $this->pickByRaiseYear($list, $targetSep);
        }

        return $map;
    }

    /** @param list<array<string,mixed>> $rows */
    private function pickByRaiseYear(array $rows, ?\DateTimeImmutable $targetSep): array
    {
        if ($rows === []) {
            return [];
        }
        if ($targetSep === null) {
            return $rows[0];
        }

        $anchor = null;
        foreach ($rows as $row) {
            $d = $this->toDate($row['raise_year'] ?? null);
            if ($d === null) {
                continue;
            }
            if ((int)$d->format('Y') === (int)$targetSep->format('Y') && (int)$d->format('n') === 9) {
                return $row;
            }
            if ($d->format('Y-m-d') <= $targetSep->format('Y-m-d')) {
                $anchor = $row;
                break;
            }
        }

        return $anchor ?? $rows[0];
    }

    private function targetSeptember(?int $year, ?int $month): ?\DateTimeImmutable
    {
        if ($year === null || $month === null) {
            return null;
        }
        $y = $month >= 10 ? $year : ($year - 1);
        return new \DateTimeImmutable(sprintf('%04d-09-30', $y));
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

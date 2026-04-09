<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PayrollV2FuyoService
{
    public function resolveByPaymentDate(string $staffId, string $paymentDate): int
    {
        $staffId = trim($staffId);
        $paymentDate = trim($paymentDate);

        if ($staffId === '' || !$this->isValidPaymentDate($paymentDate)) {
            return 0;
        }

        $connection = $this->fuyoConnection();
        if ($connection === '') {
            return 0;
        }

        $targetYear = (int) substr($paymentDate, 0, 4);
        $columns = Schema::connection($connection)->getColumnListing('mx_fuyo');
        if ($columns === []) {
            return 0;
        }

        $birthdayColumn = $this->firstExistingColumn($columns, ['fuyo_birthday', 'birthday', 'birth_day']);
        $deductionTargetColumn = $this->firstExistingColumn($columns, ['deduction_target']);
        $registrationDateColumn = $this->firstExistingColumn($columns, ['registration_date']);

        $rows = DB::connection($connection)
            ->table('dbo.mx_fuyo')
            ->whereRaw('LTRIM(RTRIM(staff_id)) = ?', [$staffId])
            ->when(
                $registrationDateColumn !== null,
                fn ($query) => $query->whereRaw('YEAR([' . $registrationDateColumn . ']) = ?', [$targetYear])
            )
            ->get();

        $targetDate = new \DateTimeImmutable(sprintf('%04d-12-31', $targetYear));
        $count = 0;

        foreach ($rows as $row) {
            if ($deductionTargetColumn !== null && !$this->isTruthy($row->{$deductionTargetColumn} ?? null)) {
                continue;
            }

            if ($birthdayColumn !== null) {
                $birthday = $this->toDate($row->{$birthdayColumn} ?? null);
                if ($birthday === null || $this->ageAt($birthday, $targetDate) < 16) {
                    continue;
                }
            }

            $count++;
        }

        return $count;
    }

    private function fuyoConnection(): string
    {
        foreach (['sqlsrv_payroll', 'sqlsrv'] as $connection) {
            try {
                if (Schema::connection($connection)->hasTable('mx_fuyo') || Schema::connection($connection)->hasTable('dbo.mx_fuyo')) {
                    return $connection;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return '';
    }

    private function isValidPaymentDate(string $paymentDate): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $paymentDate) === 1;
    }

    /** @param list<string> $columns @param list<string> $candidates */
    private function firstExistingColumn(array $columns, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }

        return null;
    }

    private function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value !== 0.0;
        }

        $text = mb_strtolower(trim((string) $value));
        if ($text === '') {
            return false;
        }

        return in_array($text, ['1', 'true', 'yes', 'on'], true);
    }

    private function num(mixed $value): float
    {
        if ($value === null) {
            return 0.0;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return 0.0;
        }

        $text = str_replace([',', ' '], '', $text);
        return is_numeric($text) ? (float) $text : 0.0;
    }

    private function toDate(mixed $value): ?\DateTimeImmutable
    {
        if ($value instanceof \DateTimeInterface) {
            return new \DateTimeImmutable($value->format('Y-m-d'));
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable(substr($text, 0, 10));
        } catch (\Throwable) {
            return null;
        }
    }

    private function ageAt(\DateTimeImmutable $birthday, \DateTimeImmutable $targetDate): int
    {
        return (int) $birthday->diff($targetDate)->y;
    }
}

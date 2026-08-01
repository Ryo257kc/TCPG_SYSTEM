<?php

namespace App\Services\Admin\V2\Attendance;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

class AttendanceV2HolidayCategoryService
{
    // シフト作成時の日区分を決めるサービス。
    // カレンダー休日と基本シフトを見て、平日・休日・祝日などを解決する。
    public const CATEGORY_WEEKDAY = '平日';
    public const CATEGORY_HALF_DAY = '半日';
    public const CATEGORY_HOLIDAY = '休日';
    public const CATEGORY_PUBLIC_HOLIDAY = '祝日';

    /** @return array<string,string> */
    public function calendarHolidayMap(int $year, int $month): array
    {
        if ($year < 2000 || $month < 1 || $month > 12) {
            return [];
        }

        $fromDate = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $toDate = date('Y-m-01 00:00:00', strtotime(substr($fromDate, 0, 10) . ' +1 month'));

        return DB::connection('sqlsrv')
            ->table('dbo.mx_calendar')
            ->where('calendar_day', '>=', $fromDate)
            ->where('calendar_day', '<', $toDate)
            ->get()
            ->mapWithKeys(function ($row): array {
                $key = date('Y-m-d', strtotime((string) $row->calendar_day));
                $workHoliday = $this->normalizeCategory($row->work_holiday ?? null);
                $publicHoliday = trim((string) ($row->public_holiday ?? ''));
                $holiday = $workHoliday
                    ?? ($publicHoliday !== '' ? self::CATEGORY_PUBLIC_HOLIDAY : '');

                return [$key => $holiday];
            })
            ->all();
    }

    public function resolve(DateTimeImmutable $date, ?array $basicShift, array $calendarHolidayMap): string
    {
        $calendarValue = trim((string) ($calendarHolidayMap[$date->format('Y-m-d')] ?? ''));
        if ($calendarValue !== '') {
            return $calendarValue;
        }

        if ($basicShift === null) {
            return self::CATEGORY_HOLIDAY;
        }

        $source = $basicShift['source'] ?? null;
        $shiftCategory = $this->extractShiftCategory($source);
        if ($shiftCategory !== null) {
            return $shiftCategory;
        }

        if ($this->hasHalfDayFlag($source)) {
            return self::CATEGORY_HALF_DAY;
        }

        if (!$this->hasShiftTimes($basicShift)) {
            return self::CATEGORY_HOLIDAY;
        }

        return self::CATEGORY_WEEKDAY;
    }

    private function extractShiftCategory(mixed $source): ?string
    {
        if (!is_object($source) && !is_array($source)) {
            return null;
        }

        foreach (['holiday_category', 'day_category', 'holiday_kbn', 'day_kbn'] as $field) {
            $rawValue = $this->getFieldValue($source, $field);
            $normalized = $this->normalizeCategory($rawValue);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        return null;
    }

    private function hasHalfDayFlag(mixed $source): bool
    {
        if (!is_object($source) && !is_array($source)) {
            return false;
        }

        foreach (['half_day', 'half_day_flag', 'half_flag', 'is_half_day', 'is_half', 'half_check'] as $field) {
            $value = $this->getFieldValue($source, $field);
            if ($value === null) {
                continue;
            }

            $text = strtolower(trim((string) $value));
            if (in_array($text, ['1', 'true', 'on', 'yes'], true)) {
                return true;
            }
        }

        return false;
    }

    private function hasShiftTimes(array $basicShift): bool
    {
        foreach (['shift_in', 'shift_exit', 'shift_entry', 'shift_out'] as $field) {
            if (trim((string) ($basicShift[$field] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    private function getFieldValue(mixed $source, string $field): mixed
    {
        if (is_array($source)) {
            return $source[$field] ?? null;
        }

        return property_exists($source, $field) ? $source->{$field} : null;
    }

    private function normalizeCategory(mixed $value): ?string
    {
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        return match ($text) {
            '平日', 'weekday', '0' => self::CATEGORY_WEEKDAY,
            '半日', 'half_day', 'halfday', '1' => self::CATEGORY_HALF_DAY,
            '休日', 'holiday', 'off', '2' => self::CATEGORY_HOLIDAY,
            '祝日', 'public_holiday', 'public holiday', '3' => self::CATEGORY_PUBLIC_HOLIDAY,
            default => null,
        };
    }
}

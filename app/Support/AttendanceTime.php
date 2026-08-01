<?php

namespace App\Support;

class AttendanceTime
{
    public static function parseMinutes(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '' || self::isZeroPlaceholder($text)) {
            return null;
        }

        if (preg_match('/^\d+(\.\d+)?$/', $text) === 1) {
            $numeric = (float) $text;
            if ($numeric <= 24) {
                return (int) round($numeric * 60);
            }

            $digitsOnly = preg_replace('/\D+/', '', $text);
            if ($digitsOnly !== null && preg_match('/^\d{3,4}$/', $digitsOnly) === 1) {
                $hours = (int) substr($digitsOnly, 0, -2);
                $minutes = (int) substr($digitsOnly, -2);
                if ($hours < 24 && $minutes < 60) {
                    return ($hours * 60) + $minutes;
                }
            }

            return null;
        }

        $normalized = str_ireplace(['AM', 'PM'], [' AM ', ' PM '], $text);
        $timestamp = strtotime('2000-01-01 ' . $normalized);
        if ($timestamp === false) {
            $timestamp = strtotime($normalized);
        }
        if ($timestamp === false) {
            return null;
        }

        return ((int) date('G', $timestamp) * 60) + (int) date('i', $timestamp);
    }


    public static function scheduledHours(
        mixed $startValue,
        mixed $leaveValue,
        mixed $breakOutValue,
        mixed $endValue,
    ): float {
        $startMinutes = self::parseMinutes($startValue);
        $endMinutes = self::parseMinutes($endValue);
        if ($startMinutes === null || $endMinutes === null) {
            return 0.0;
        }

        $leaveMinutes = self::parseMinutes($leaveValue);
        $breakOutMinutes = self::parseMinutes($breakOutValue);

        if ($leaveMinutes !== null && $breakOutMinutes !== null) {
            return (
                self::minutesBetween($startMinutes, $leaveMinutes)
                + self::minutesBetween($breakOutMinutes, $endMinutes)
            ) / 60;
        }

        return self::minutesBetween($startMinutes, $endMinutes) / 60;
    }

    public static function hasAnyTimeValue(array $values): bool
    {
        foreach ($values as $value) {
            if (self::parseMinutes($value) !== null) {
                return true;
            }
        }

        return false;
    }

    public static function minutesBetween(int $startMinutes, int $endMinutes): int
    {
        if ($endMinutes < $startMinutes) {
            $endMinutes += 24 * 60;
        }

        return max(0, $endMinutes - $startMinutes);
    }

    public static function formatNumber(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $num = (float) $value;
        if (abs($num - round($num)) < 0.00001) {
            return (string) (int) round($num);
        }

        return rtrim(rtrim(number_format($num, 2, '.', ''), '0'), '.');
    }

    public static function isZeroPlaceholder(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        $text = trim(str_replace('　', ' ', (string) $value));
        if ($text === '') {
            return false;
        }

        $normalized = preg_replace('/\s+/', '', $text) ?? $text;
        if (in_array($normalized, ['0', '0.0', '0.00', '0:00', '00:00', '00:00:00'], true)) {
            return true;
        }

        $spaced = preg_replace('/\s+/', ' ', $text) ?? $text;

        return preg_match('/^\d{4}-\d{2}-\d{2}[ T]0?0:00(?::00(?:\.0+)?)?$/', $spaced) === 1;
    }
}

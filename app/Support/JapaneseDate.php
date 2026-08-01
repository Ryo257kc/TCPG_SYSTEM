<?php

namespace App\Support;

final class JapaneseDate
{
    private const WEEKDAYS = ['日', '月', '火', '水', '木', '金', '土'];

    public static function monthDayWithWeekday(mixed $value): string
    {
        $timestamp = self::timestamp($value);
        if ($timestamp === null) {
            return '';
        }

        return date('n/j', $timestamp) . '(' . self::WEEKDAYS[(int) date('w', $timestamp)] . ')';
    }

    private static function timestamp(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->getTimestamp();
        }

        $timestamp = strtotime((string) $value);

        return $timestamp === false ? null : $timestamp;
    }
}

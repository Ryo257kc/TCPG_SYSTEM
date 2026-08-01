<?php

if (!function_exists('formatNumber')) {
    function formatNumber($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $value = (float) $value;

        if (floor($value) != $value) {
            return rtrim(rtrim(number_format($value, 4), '0'), '.');
        }

        return number_format($value);
    }
}

<?php

namespace App\Support;

/**
 * 「文字列で受け取った日付・数値を、空/パース不能ならnull（またはdisplay系は空文字）で
 * 返す」という、複数のAdminコントローラー・サービスで一字一句同じ実装が個別に
 * 存在していたパース処理をまとめたもの。
 */
class ParsedInput
{
    /** 保存用。空・パース不能ならnull。 */
    public static function date(mixed $value, string $format = 'Y-m-d'): ?string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($text)->format($format);
        } catch (\Throwable) {
            return null;
        }
    }

    /** 画面表示用。空・パース不能なら空文字。 */
    public static function dateDisplay(mixed $value, string $format = 'Y/m/d'): string
    {
        return self::date($value, $format) ?? '';
    }

    /** 保存用。空・数値でなければnull。 */
    public static function number(mixed $value): ?float
    {
        $text = str_replace(',', '', trim((string) ($value ?? '')));

        return $text !== '' && is_numeric($text) ? (float) $text : null;
    }

    /** 保存用。空・数値でなければnull。 */
    public static function intOrNull(mixed $value): ?int
    {
        $number = self::number($value);

        return $number === null ? null : (int) $number;
    }
}

<?php

namespace App\Services\StaffPortal;

class DailySummaryBankDepositCalculator
{
    /** 通常日報の銀行預入金額。回収日の保険証忘れ分も明細に含めて渡す。 */
    public static function calculate(iterable $treatmentRows, iterable $registerRows): int
    {
        $money = static fn($value): int => (int) str_replace(',', '', (string) ($value ?? 0));
        $amount = 0;

        foreach ($treatmentRows as $row) {
            $amount += $money($row['保険負担計'] ?? 0);
            if ((int) ($row['計算外'] ?? 0) === 0 && (int) ($row['charge'] ?? 0) !== 1) {
                $amount += $money($row['自費計'] ?? 0);
            }
        }

        foreach ($registerRows as $row) {
            $item = trim((string) ($row['項目'] ?? ''));
            if ($item === 'チャージ金') {
                $amount += $money($row['金額の合計'] ?? 0);
            } elseif ($item !== '物品販売') {
                $amount -= $money($row['金額の合計'] ?? 0);
            }
        }

        return $amount;
    }
}

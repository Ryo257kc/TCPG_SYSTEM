<?php

namespace Tests\Unit;

use App\Services\StaffPortal\DailySummaryBankDepositCalculator;
use PHPUnit\Framework\TestCase;

class DailySummaryBankDepositCalculatorTest extends TestCase
{
    public function test_collection_is_included_without_counting_charge_use_as_cash(): void
    {
        $rows = [
            ['保険負担計' => '28,950', '自費計' => '3,500', '計算外' => 0, 'charge' => 0],
            ['保険負担計' => '12,050', '自費計' => '21,650', '計算外' => 0, 'charge' => 1],
            ['保険負担計' => '800', '自費計' => '0', '計算外' => 0, 'charge' => 0],
        ];
        $register = [['項目' => 'チャージ金', '金額の合計' => '28,000']];

        self::assertSame(73300, DailySummaryBankDepositCalculator::calculate($rows, $register));
    }

    public function test_deductions_refunds_and_excluded_private_fees(): void
    {
        $rows = [
            ['保険負担計' => '1,000', '自費計' => '2,000', '計算外' => 0],
            ['保険負担計' => '800', '自費計' => '5,000', '計算外' => 1],
        ];
        $register = [
            ['項目' => 'カード', '金額の合計' => '1,500'],
            ['項目' => '返金', '金額の合計' => '-500'],
            ['項目' => '物品販売', '金額の合計' => '900'],
            ['項目' => 'チャージ金', '金額の合計' => '1,000'],
        ];

        self::assertSame(3800, DailySummaryBankDepositCalculator::calculate($rows, $register));
        self::assertSame(0, DailySummaryBankDepositCalculator::calculate([], []));
    }
}

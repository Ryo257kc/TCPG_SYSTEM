<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PersonalWageLedgerDisplayTest extends TestCase
{
    private function renderLedger(?string $showBonus, array $rows): string
    {
        DB::shouldReceive('connection')->never();
        $this->app->instance('request', Request::create('/admin/payroll/personal-wage-ledger', 'GET',
            $showBonus === null ? [] : ['show_bonus' => $showBonus]));

        return view('admin_v2.work.wage_ledger.personal', [
            'ledgerRows' => $rows, 'allowanceEntries' => [], 'staffInfo' => [],
            'selectedStaffId' => 'TEST001', 'selectedPaymentDate' => '2026-01-25',
            'selectedStartMonthText' => '2026/01',
        ])->render();
    }

    public function test_default_includes_bonus_and_period_total(): void
    {
        $html = $this->renderLedger(null, [
            ['payment_type' => '給与', 'column_label' => 'salary-marker', 'supply_sum' => 1234],
            ['payment_type' => '賞与', 'column_label' => 'bonus-marker', 'supply_sum' => 5678],
        ]);
        self::assertStringContainsString('bonus-marker', $html);
        self::assertStringContainsString('賞与額', $html);
        self::assertStringContainsString('>6,912</td>', $html);
        self::assertMatchesRegularExpression('/name="show_bonus" value="1"\s+checked/', $html);
    }

    public function test_hiding_bonus_rebuilds_pages_and_total_from_salary_only(): void
    {
        $rows = array_fill(0, 6, ['payment_type' => '給与', 'column_label' => 'salary-marker', 'supply_sum' => 1234]);
        $rows[] = ['payment_type' => '賞与', 'column_label' => 'bonus-marker', 'supply_sum' => 5678];
        $html = $this->renderLedger('0', $rows);
        self::assertStringNotContainsString('bonus-marker', $html);
        self::assertStringNotContainsString('賞与額', $html);
        self::assertStringContainsString('>7,404</td>', $html);
        self::assertStringContainsString('1 / 1 ページ', $html);
        self::assertSame(1, substr_count($html, '<section class="print-page">'));
        self::assertStringContainsString('name="staff_id" value="TEST001"', $html);
        self::assertStringContainsString('name="payment_date" value="2026-01-25"', $html);
        self::assertStringContainsString('.print-toolbar { display: none; }', $html);
        self::assertDoesNotMatchRegularExpression('/name="show_bonus" value="1"\s+checked/', $html);
    }

    public function test_hiding_bonus_when_only_bonus_exists_shows_empty_state(): void
    {
        $html = $this->renderLedger('0', [['payment_type' => '賞与', 'column_label' => 'bonus-marker']]);
        self::assertStringContainsString('対象データがありません。', $html);
        self::assertStringNotContainsString('bonus-marker', $html);
    }
}

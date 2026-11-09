<?php

namespace Tests\Feature;

use App\Http\Controllers\StaffPortal\office\EntryController;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class ReceiptMonthlyReturnAmountTest extends TestCase
{
    public function test_monthly_insurance_sales_exclude_later_returns_and_keep_scope(): void
    {
        // 集計クエリのみモックで検証し、実DBや保存済みの仕訳には接続しない。
        $connection = Mockery::mock(ConnectionInterface::class);
        $query = Mockery::mock(Builder::class);
        DB::shouldReceive('connection')->once()->with('sqlsrv')->andReturn($connection);
        $connection->shouldReceive('table')->once()->with('dbo.mx_insurance_claim_details as detail')->andReturn($query);
        $query->shouldReceive('leftJoin')->twice()->andReturnSelf();
        $query->shouldReceive('selectRaw')->once()->with(Mockery::on(function ($sql) {
            self::assertStringContainsString('SUM(ISNULL(detail.claim_amount, 0) + ISNULL(detail.adjustment_amount, 0)) as amount', $sql);
            self::assertStringNotContainsString('returned_amount', $sql);
            return true;
        }))->andReturnSelf();
        $query->shouldReceive('where')->once()->with('detail.treatment_month', '>=', '2026-03-01')->andReturnSelf();
        $query->shouldReceive('where')->once()->with('detail.treatment_month', '<', '2026-04-01')->andReturnSelf();
        $query->shouldReceive('where')->once()->with('detail.insurer_number', '<>', '99999999')->andReturnSelf();
        $query->shouldReceive('groupByRaw')->once()->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn(collect());
        $method = new \ReflectionMethod(EntryController::class, 'createReceiptMonthlyJournalEntries');
        self::assertSame(0, $method->invoke(new EntryController(),
            Carbon::parse('2026-03-01'), Carbon::parse('2026-04-01'), Carbon::parse('2026-03-31')));
    }
}

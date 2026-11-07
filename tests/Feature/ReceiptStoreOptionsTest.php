<?php

namespace Tests\Feature;

use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use App\Services\Admin\V2\BillingListV2Service;
use App\Services\Shared\ReceiptStoreOptionsService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class ReceiptStoreOptionsTest extends TestCase
{
    public function test_existing_receipt_filters_and_names_are_preserved(): void
    {
        $connection = Mockery::mock(ConnectionInterface::class);
        $query = Mockery::mock(Builder::class);
        DB::shouldReceive('connection')->once()->with('sqlsrv')->andReturn($connection);
        $connection->shouldReceive('table')->once()->with('dbo.mx_departments')->andReturn($query);
        $query->shouldReceive('join')->once()->with('dbo.mx_stores', 'dbo.mx_stores.store_code', '=', 'dbo.mx_departments.official_store_no')->andReturnSelf();
        $query->shouldReceive('select')->once()->with(['store_category'])->andReturnSelf();
        $query->shouldReceive('whereIn')->once()->with('dbo.mx_stores.business_type', ['（往診）', '（店舗）'])->andReturnSelf();
        $query->shouldReceive('where')->once()->with(Mockery::type('Closure'))->andReturnUsing(function ($callback) use ($query) {
            $condition = Mockery::mock(Builder::class);
            $condition->shouldReceive('where')->once()->with('has_receipt', true)->andReturnSelf();
            $condition->shouldReceive('orWhere')->once()->with('has_receipt', 1)->andReturnSelf();
            $callback($condition);
            return $query;
        });
        $query->shouldReceive('whereNull')->once()->with('closed_on')->andReturnSelf();
        $query->shouldReceive('whereNotNull')->once()->with('store_category')->andReturnSelf();
        $query->shouldReceive('orderBy')->once()->with('store_category')->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn(collect([
            (object) ['store_category' => ' さくら店舗 '],
            (object) ['store_category' => 'さくら店舗'],
            (object) ['store_category' => ''],
            (object) ['store_category' => '織田マッサージ'],
        ]));

        self::assertSame(['さくら店舗', '織田マッサージ'], app(ReceiptStoreOptionsService::class)->names());
    }

    public function test_billing_and_staff_use_the_same_shared_candidates(): void
    {
        $shared = Mockery::mock(ReceiptStoreOptionsService::class);
        $shared->shouldReceive('names')->twice()->andReturn(['さくら店舗', '織田マッサージ']);
        $this->app->instance(ReceiptStoreOptionsService::class, $shared);
        DB::shouldReceive('connection')->never();
        $staff = new class {
            use HandlesStaffPortalContext;
            public function options(): array { return $this->receiptStoreOptions(); }
        };
        $names = $staff->options();
        $method = new \ReflectionMethod(BillingListV2Service::class, 'storeOptions');
        $options = $method->invoke(app(BillingListV2Service::class));
        self::assertSame($names, array_column($options, 'label'));
        self::assertSame($names, array_column($options, 'value'));
    }
}

<?php

namespace Tests\Feature;

use App\Services\Admin\V2\BillingListV2Service;
use App\Services\Shared\ReceiptStoreOptionsService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class BillingListDuplicateTest extends TestCase
{
    private function query(): Builder
    {
        return Mockery::mock(Builder::class);
    }

    public function test_top_save_passes_header_existing_rows_and_new_row_to_atomic_save(): void
    {
        $this->withoutMiddleware();
        DB::shouldReceive('connection')->never();
        $service = Mockery::mock(BillingListV2Service::class);
        $this->app->instance(BillingListV2Service::class, $service);
        $service->shouldReceive('updateInvoiceWithDetails')->once()->with(
            4000,
            Mockery::on(fn ($values) => $values['subject'] === '変更'),
            [['invoice_detail_id' => 80, 'quantity' => '3', 'unit_price' => '1200']],
            ['item_name' => '新規', 'quantity' => '1', 'unit_price' => '500'],
        )->andReturn(1);
        $this->post(route('admin.work.billing_list.update'), [
            'invoice_id' => 4000, 'subject' => '変更',
            'detail_changes' => json_encode([
                'rows' => [['invoice_detail_id' => 80, 'quantity' => '3', 'unit_price' => '1200']],
                'new_detail' => ['item_name' => '新規', 'quantity' => '1', 'unit_price' => '500'],
            ]),
        ])->assertRedirect(route('admin.work.billing_list', ['invoice_id' => 4000]));
    }

    public function test_invalid_row_prevents_header_and_detail_writes(): void
    {
        $this->withoutMiddleware();
        DB::shouldReceive('connection')->never();
        $service = Mockery::mock(BillingListV2Service::class);
        $this->app->instance(BillingListV2Service::class, $service);
        $service->shouldNotReceive('updateInvoiceWithDetails');
        $this->post(route('admin.work.billing_list.update'), [
            'invoice_id' => 4000,
            'detail_changes' => json_encode(['rows' => [['invoice_detail_id' => 80, 'quantity' => '不正']]]),
        ])->assertSessionHasErrors('rows.0.quantity');
    }

    public function test_duplicate_preserves_items_uses_new_ids_and_clears_prices_and_payment(): void
    {
        $connection = Mockery::mock(ConnectionInterface::class);
        DB::shouldReceive('connection')->with('sqlsrv')->andReturn($connection);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn ($callback) => $callback());
        $source = $this->query();
        $create = $this->query();
        $total = $this->query();
        $connection->shouldReceive('table')->with('dbo.mx_invoices')->andReturn($source, $create, $total);
        $invoice = [
            'invoice_id' => 3090, 'invoice_date' => '2026-09-30', 'recipient_name' => '宛先',
            'subject' => '請求', 'billing_source' => '会社', 'paid_amount' => 1200,
            'total_amount' => 9999, 'remarks' => '備考', 'accounting_remarks' => '経理備考',
        ];
        $source->shouldReceive('where')->once()->with('invoice_id', 3090)->andReturnSelf();
        $source->shouldReceive('first')->once()->andReturn((object) $invoice);
        $create->shouldReceive('insertGetId')->once()->with(Mockery::on(function ($values) use ($invoice) {
            self::assertArrayNotHasKey('invoice_id', $values);
            self::assertSame(0.0, $values['paid_amount']);
            self::assertSame(0, $values['total_amount']);
            foreach (['invoice_date', 'recipient_name', 'subject', 'billing_source', 'remarks', 'accounting_remarks'] as $key) {
                self::assertSame($invoice[$key], $values[$key]);
            }
            return true;
        }), 'invoice_id')->andReturn(4000);

        $read = $this->query();
        $insert = $this->query();
        $reread = $this->query();
        $connection->shouldReceive('table')->with('dbo.mx_invoice_details')->andReturn($read, $insert, $reread);
        $detail = ['invoice_detail_id' => 51, 'invoice_id' => 3090, 'item_name' => '品目',
            'category' => '区分', 'quantity' => 2, 'unit' => '件', 'unit_price' => 500,
            'total_amount' => 1000, 'tax_category' => '10%', 'store_name' => '店舗', 'remarks' => '明細備考'];
        $read->shouldReceive('where')->once()->with('invoice_id', 3090)->andReturnSelf();
        $read->shouldReceive('orderBy')->once()->with('invoice_detail_id')->andReturnSelf();
        $read->shouldReceive('get')->once()->andReturn(collect([(object) $detail]));
        $insert->shouldReceive('insert')->once()->with(Mockery::on(function ($values) use ($detail) {
            unset($detail['invoice_detail_id']);
            $detail['invoice_id'] = 4000;
            $detail['unit_price'] = null;
            $detail['total_amount'] = null;
            self::assertEquals($detail, $values);
            return true;
        }))->andReturnTrue();
        $reread->shouldReceive('where')->once()->with('invoice_id', 4000)->andReturnSelf();
        $reread->shouldReceive('orderBy')->once()->with('invoice_detail_id')->andReturnSelf();
        $reread->shouldReceive('get')->once()->andReturn(collect([(object) array_replace($detail, ['unit_price' => null, 'total_amount' => null])]));
        $total->shouldReceive('where')->once()->with('invoice_id', 4000)->andReturnSelf();
        $total->shouldReceive('update')->once()->with(['total_amount' => 0.0])->andReturn(1);

        self::assertSame(4000, app(BillingListV2Service::class)->duplicateInvoice(3090));
    }

    public function test_saving_detail_ignores_old_posted_total_and_recalculates_invoice_tax(): void
    {
        $connection = Mockery::mock(ConnectionInterface::class);
        DB::shouldReceive('connection')->with('sqlsrv')->andReturn($connection);
        $update = $this->query();
        $read = $this->query();
        $header = $this->query();
        $connection->shouldReceive('table')->with('dbo.mx_invoice_details')->andReturn($update, $read);
        $connection->shouldReceive('table')->once()->with('dbo.mx_invoices')->andReturn($header);
        $update->shouldReceive('where')->once()->with('invoice_id', 4000)->andReturnSelf();
        $update->shouldReceive('where')->once()->with('invoice_detail_id', 80)->andReturnSelf();
        $update->shouldReceive('update')->once()->with(Mockery::on(function ($values) {
            self::assertSame(3, $values['quantity']);
            self::assertSame(1200, $values['unit_price']);
            self::assertSame(3600.0, $values['total_amount']);
            return true;
        }))->andReturn(1);
        $read->shouldReceive('where')->once()->with('invoice_id', 4000)->andReturnSelf();
        $read->shouldReceive('orderBy')->once()->with('invoice_detail_id')->andReturnSelf();
        $read->shouldReceive('get')->once()->andReturn(collect([(object) [
            'quantity' => 3, 'unit_price' => 1200, 'total_amount' => 3600, 'tax_category' => '10%',
        ]]));
        $header->shouldReceive('where')->once()->with('invoice_id', 4000)->andReturnSelf();
        $header->shouldReceive('update')->once()->with(['total_amount' => 3960.0])->andReturn(1);

        self::assertSame(1, app(BillingListV2Service::class)->updateDetail(4000, 80, [
            'quantity' => '3', 'unit_price' => '1,200', 'total_amount' => '999', 'tax_category' => '10%',
        ]));
    }

    public function test_header_and_all_details_and_new_row_are_saved_in_one_transaction(): void
    {
        $active = false;
        $connection = Mockery::mock(ConnectionInterface::class);
        DB::shouldReceive('connection')->once()->with('sqlsrv')->andReturn($connection);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(function ($callback) use (&$active) {
            $active = true;
            try { return $callback(); } finally { $active = false; }
        });
        $service = Mockery::mock(BillingListV2Service::class, [app(ReceiptStoreOptionsService::class)])->makePartial();
        $service->shouldReceive('updateInvoice')->once()->with(4000, ['subject' => '変更'])->andReturnUsing(function () use (&$active) {
            self::assertTrue($active);
            return 1;
        });
        foreach ([80, 81] as $id) {
            $service->shouldReceive('updateDetail')->once()->with(4000, $id, ['invoice_detail_id' => $id])->andReturnUsing(function () use (&$active) {
                self::assertTrue($active);
                return 1;
            });
        }
        $service->shouldReceive('createDetail')->once()->with(4000, ['item_name' => '新規'])->andReturnUsing(function () use (&$active) {
            self::assertTrue($active);
        });
        self::assertSame(1, $service->updateInvoiceWithDetails(4000, ['subject' => '変更'], [
            ['invoice_detail_id' => 80], ['invoice_detail_id' => 81],
        ], ['item_name' => '新規']));
        self::assertFalse($active);
    }

    public function test_missing_detail_propagates_failure_out_of_transaction_and_stops_remaining_writes(): void
    {
        $connection = Mockery::mock(ConnectionInterface::class);
        DB::shouldReceive('connection')->once()->with('sqlsrv')->andReturn($connection);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(function ($callback) {
            try {
                $callback();
                self::fail('The transaction must receive the failure to roll back.');
            } catch (HttpException $e) {
                self::assertSame(409, $e->getStatusCode());
                throw $e;
            }
        });
        $service = Mockery::mock(BillingListV2Service::class, [app(ReceiptStoreOptionsService::class)])->makePartial();
        $service->shouldReceive('updateInvoice')->once()->with(4000, [])->andReturn(1);
        $service->shouldReceive('updateDetail')->once()->with(4000, 80, ['invoice_detail_id' => 80])->andReturn(0);
        $service->shouldNotReceive('createDetail');
        $this->expectException(HttpException::class);
        $service->updateInvoiceWithDetails(4000, [], [['invoice_detail_id' => 80]], ['item_name' => '新規']);
    }

    public function test_missing_source_aborts_inside_transaction_without_inserting(): void
    {
        $connection = Mockery::mock(ConnectionInterface::class);
        DB::shouldReceive('connection')->with('sqlsrv')->andReturn($connection);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn ($callback) => $callback());
        $query = $this->query();
        $connection->shouldReceive('table')->once()->with('dbo.mx_invoices')->andReturn($query);
        $query->shouldReceive('where')->once()->with('invoice_id', 3090)->andReturnSelf();
        $query->shouldReceive('first')->once()->andReturnNull();
        $this->expectException(HttpException::class);
        app(BillingListV2Service::class)->duplicateInvoice(3090);
    }
}

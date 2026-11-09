<?php

namespace Tests\Feature;

use App\Http\Controllers\StaffPortal\OfficeController;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ReturnProcessingDateSaveTest extends TestCase
{
    private function controller(bool $admin): OfficeController
    {
        $controller = Mockery::mock(OfficeController::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $controller->shouldReceive('staffPortalStaffId')->andReturn('TEST');
        $controller->shouldReceive('staffPortalStaffRow')->with('TEST')->andReturn(['is_admin' => $admin ? 1 : 0]);
        return $controller;
    }

    private function payload(string $month): array
    {
        return ['detail_ids' => [10, 20], 'payment_month' => $month,
            'return_status' => 'unprocessed', 'company_id' => '2', 'returned_amount' => 999, 'claim_amount' => 999];
    }

    private function query(int $matching, string $month = '2025-09'): Builder
    {
        $connection = Mockery::mock(ConnectionInterface::class);
        $query = Mockery::mock(Builder::class);
        DB::shouldReceive('connection')->twice()->with('sqlsrv')->andReturn($connection);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn($callback) => $callback());
        $connection->shouldReceive('table')->once()->with('dbo.mx_insurance_claim_details')->andReturn($query);
        $query->shouldReceive('whereIn')->once()->with('insurance_claim_detail_id', [10, 20])->andReturnSelf();
        $query->shouldReceive('where')->once()->with('insurer_number', '<>', '99999999')->andReturnSelf();
        $query->shouldReceive('where')->once()->with('treatment_month', '<', \Illuminate\Support\Carbon::createFromFormat('!Y-m', $month)->addMonth()->format('Y-m-d'))->andReturnSelf();
        $query->shouldReceive('whereRaw')->once()->with('COALESCE(returned_amount, 0) <> 0')->andReturnSelf();
        $query->shouldReceive('lockForUpdate')->once()->andReturnSelf();
        $query->shouldReceive('get')->once()->with(['insurance_claim_detail_id'])->andReturn(collect(array_fill(0, $matching, (object) [])));
        return $query;
    }

    public static function months(): array
    {
        return [['2025-09', '2025-09-30'], ['2024-02', '2024-02-29'], ['2026-02', '2026-02-28']];
    }

    #[DataProvider('months')]
    public function test_admin_saves_only_month_end_processing_date_to_selected_return_rows(string $month, string $expected): void
    {
        $query = $this->query(2, $month);
        $query->shouldReceive('update')->once()->with(['returned_journal_processed_at' => $expected])->andReturn(2);
        $response = $this->controller(true)->saveReturnProcessingDate(Request::create('/', 'POST', $this->payload($month)));
        self::assertStringContainsString('tab=returns', $response->getTargetUrl());
        self::assertStringContainsString('payment_month=' . $month, $response->getTargetUrl());
        self::assertStringContainsString('company_id=2', $response->getTargetUrl());
    }

    public function test_non_admin_cannot_save_by_direct_post(): void
    {
        DB::shouldReceive('connection')->never();
        try {
            $this->controller(false)->saveReturnProcessingDate(Request::create('/', 'POST', $this->payload('2025-09')));
            self::fail('Non-admin must be rejected.');
        } catch (HttpException $e) {
            self::assertSame(403, $e->getStatusCode());
        }
    }

    public function test_changed_or_missing_selected_row_prevents_the_whole_save(): void
    {
        $query = $this->query(1);
        $query->shouldNotReceive('update');
        try {
            $this->controller(true)->saveReturnProcessingDate(Request::create('/', 'POST', $this->payload('2025-09')));
            self::fail('All selected rows must still be eligible.');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('detail_ids', $e->errors());
        }
    }

    public function test_empty_selection_does_not_write(): void
    {
        DB::shouldReceive('connection')->never();
        $values = $this->payload('2025-09');
        $values['detail_ids'] = [];
        $this->expectException(ValidationException::class);
        $this->controller(true)->saveReturnProcessingDate(Request::create('/', 'POST', $values));
    }
}

<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\V2\YearEndAdjustmentV2Controller;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Mockery;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class YearEndPublishDateTest extends TestCase
{
    public static function dates(): array
    {
        return [
            'insert datetime-local' => [false, '2026-11-07T00:00', '2026-11-07 00:00:00'],
            'update datetime-local' => [true, '2026-11-07T09:30', '2026-11-07 09:30:00'],
            'insert empty' => [false, '', null],
            'clear existing date' => [true, '', null],
        ];
    }

    #[DataProvider('dates')]
    public function test_publish_date_is_normalized_before_insert_or_update(bool $exists, string $input, ?string $expected): void
    {
        $connection = Mockery::mock(ConnectionInterface::class);
        $query = Mockery::mock(Builder::class);
        DB::shouldReceive('connection')->twice()->with('sqlsrv_payroll')->andReturn($connection);
        $connection->shouldReceive('table')->twice()->with('dbo.year_end_adjustment_settings')->andReturn($query);
        $query->shouldReceive('where')->times($exists ? 2 : 1)->with('target_year', 2026)->andReturnSelf();
        $query->shouldReceive('exists')->once()->andReturn($exists);
        $query->shouldReceive($exists ? 'update' : 'insert')->once()->with(Mockery::on(function ($values) use ($exists, $expected) {
            self::assertSame($expected, $values['publish_date']);
            self::assertInstanceOf(\DateTimeInterface::class, $values['updated_at']);
            if ($exists) {
                self::assertArrayNotHasKey('created_at', $values);
                self::assertArrayNotHasKey('target_year', $values);
            } else {
                self::assertSame(2026, $values['target_year']);
                self::assertInstanceOf(\DateTimeInterface::class, $values['created_at']);
            }
            return true;
        }))->andReturn($exists ? 1 : true);
        $request = Request::create('/', 'POST', ['target_year' => 2026, 'publish_date' => $input]);
        $controller = Mockery::mock(YearEndAdjustmentV2Controller::class)->makePartial();
        $response = $controller->savePublishDate($request);
        self::assertSame(route('admin.work.year_end_adjustments', ['target_year' => 2026]), $response->getTargetUrl());
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class InformationDeleteTest extends TestCase
{
    private function expectDelete(int $affected): void
    {
        $connection = Mockery::mock(ConnectionInterface::class);
        $query = Mockery::mock(Builder::class);
        DB::shouldReceive('connection')->once()->with('sqlsrv')->andReturn($connection);
        $connection->shouldReceive('table')->once()->with('dbo.mx_message')->andReturn($query);
        $query->shouldReceive('where')->once()->with('mesa_no', 123)->andReturnSelf();
        $query->shouldReceive('delete')->once()->andReturn($affected);
    }

    public function test_deletes_only_selected_message_without_validating_unsaved_edit_fields(): void
    {
        $this->withoutMiddleware();
        $this->expectDelete(1);
        $this->post(route('admin.work.information.delete'), ['mesa_no' => 123, 'title' => '', 'destination' => ''])
            ->assertRedirect(route('admin.work.information'))
            ->assertSessionHas('status', 'お知らせを削除しました。');
    }

    public function test_missing_id_does_not_delete_anything(): void
    {
        $this->withoutMiddleware();
        DB::shouldReceive('connection')->never();
        $this->post(route('admin.work.information.delete'), [])->assertSessionHasErrors('mesa_no');
    }

    public function test_missing_message_does_not_report_success(): void
    {
        $this->withoutMiddleware();
        $this->expectDelete(0);
        $this->post(route('admin.work.information.delete'), ['mesa_no' => 123])
            ->assertRedirect(route('admin.work.information'))
            ->assertSessionHas('status', '削除対象のお知らせが見つかりません。');
    }
}

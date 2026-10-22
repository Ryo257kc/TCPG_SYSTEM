<?php

namespace Tests\Feature;

use App\Services\Admin\V2\AdminTwoFactorCredentialStore;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class AdminTwoFactorCredentialStoreTest extends TestCase
{
    private function lockedQuery(?object $row): Builder
    {
        config(['admin_two_factor.database_approved' => true]);
        $connection = Mockery::mock(ConnectionInterface::class);
        $query = Mockery::mock(Builder::class);
        // 実PDOは作らず、接続・トランザクション・対象ID限定・行ロックの境界を確認する。
        DB::shouldReceive('connection')->with('sqlsrv')->andReturn($connection);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn($callback) => $callback());
        $connection->shouldReceive('table')->once()->with('dbo.admin_two_factor_credentials')->andReturn($query);
        $query->shouldReceive('where')->once()->with('staff_id', '001')->andReturnSelf();
        $query->shouldReceive('lockForUpdate')->once()->andReturnSelf();
        $query->shouldReceive('first')->once()->andReturn($row);

        return $query;
    }

    public function test_reset_locks_and_updates_only_the_selected_credential(): void
    {
        $version = str_repeat('a', 64);
        $query = $this->lockedQuery((object) ['staff_id' => '001', 'secret' => 'encrypted-test-secret', 'auth_version' => $version]);
        $query->shouldReceive('update')->once()->with(Mockery::on(function (array $changes) use ($version): bool {
            self::assertSame('', $changes['secret']);
            self::assertSame('[]', $changes['recovery_code_hashes']);
            self::assertSame(0, $changes['last_used_step']);
            self::assertNotSame($version, $changes['auth_version']);
            self::assertSame(64, strlen($changes['auth_version']));
            self::assertCount(5, $changes);
            self::assertArrayHasKey('updated_at', $changes);

            return true;
        }))->andReturn(1);
        self::assertTrue((new AdminTwoFactorCredentialStore())->reset('001', $version));
    }

    public function test_reset_refuses_a_generation_changed_after_confirmation(): void
    {
        $query = $this->lockedQuery((object) ['staff_id' => '001', 'secret' => 'encrypted-test-secret', 'auth_version' => str_repeat('b', 64)]);
        $query->shouldNotReceive('update');
        self::assertFalse((new AdminTwoFactorCredentialStore())->reset('001', str_repeat('a', 64)));
    }
}

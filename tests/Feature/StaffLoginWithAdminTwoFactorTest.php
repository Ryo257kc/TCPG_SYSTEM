<?php

namespace Tests\Feature;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StaffLoginWithAdminTwoFactorTest extends TestCase
{
    public static function staffLogins(): array
    {
        return [
            'ordinary staff' => [0, 'test-staff-password', 'dashboard'],
            'admin using staff entry' => [1, 'test-staff-password', 'dashboard'],
            'initial password' => [0, '123', 'staff_portal.password_change'],
        ];
    }

    #[DataProvider('staffLogins')]
    public function test_staff_entry_keeps_existing_behavior(int $isAdmin, string $password, string $destination): void
    {
        config([
            'admin_two_factor.enabled' => true,
            'admin_two_factor.trusted_devices_approved' => true,
            'session.driver' => 'array',
        ]);
        $this->travelTo(Carbon::parse('2026-10-23 12:00:00', 'Asia/Tokyo'));
        // 実DB接続を作らず、既存スタッフ認証への問い合わせだけを置き換える。
        $connection = Mockery::mock(ConnectionInterface::class);
        $query = Mockery::mock(Builder::class);
        DB::shouldReceive('connection')->times($isAdmin ? 1 : 2)->with('sqlsrv')->andReturn($connection);
        $connection->shouldReceive('table')->once()->with('dbo.mx_staffs')->andReturn($query);
        $query->shouldReceive('where')->once()->with(Mockery::type(\Closure::class))->andReturnSelf();
        $query->shouldReceive('first')->once()->andReturn((object) [
            'staff_id' => 'test-staff', 'staff_name' => 'テストスタッフ',
            'password' => $password, 'is_admin' => $isAdmin, 'employment' => '在職',
        ]);
        if (!$isAdmin) {
            $maintenance = Mockery::mock(Builder::class);
            $connection->shouldReceive('table')->once()->with('dbo.mx_maintenance_windows')->andReturn($maintenance);
            $maintenance->shouldReceive('where')->twice()->andReturnSelf();
            $maintenance->shouldReceive('exists')->once()->andReturn(false);
        }
        $response = $this->post('/staff/login', ['staff_id' => 'test-staff', 'password' => $password]);
        $response->assertRedirect(route($destination))->assertSessionMissing('admin_2fa_pending')->assertSessionMissing('admin_logged_in');
        $response->assertSessionHas($password === '123' ? 'password_change_staff_id' : 'staff_id', 'test-staff');
    }
}

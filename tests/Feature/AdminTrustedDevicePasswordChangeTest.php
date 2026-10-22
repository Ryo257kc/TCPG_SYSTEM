<?php

namespace Tests\Feature;

use App\Services\Admin\V2\AdminTrustedDeviceService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\Support\InMemoryAdminTrustedDeviceStore;
use Tests\Support\InMemoryAdminTwoFactorCredentialStore;
use Tests\TestCase;

class AdminTrustedDevicePasswordChangeTest extends TestCase
{
    public function test_password_change_revokes_every_target_device_and_rotates_generation(): void
    {
        config(['admin_two_factor.database_approved' => true, 'admin_two_factor.trusted_devices_approved' => true]);
        $credentials = new InMemoryAdminTwoFactorCredentialStore();
        $credentials->credentials['001'] = ['staff_id' => '001', 'secret' => 'encrypted-secret', 'auth_version' => str_repeat('a', 64)];
        $devices = new InMemoryAdminTrustedDeviceStore();
        $devices->devices = [
            'one' => ['staff_id' => '001'],
            'two' => ['staff_id' => '001'],
            'other' => ['staff_id' => '002'],
        ];
        $connection = Mockery::mock(ConnectionInterface::class);
        DB::shouldReceive('connection')->once()->with('sqlsrv')->andReturn($connection);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn($write) => $write());
        $service = new AdminTrustedDeviceService($devices, $credentials);
        $result = $service->changePassword('001', function () use ($devices, $credentials): int {
            self::assertSame(['other' => ['staff_id' => '002']], $devices->devices);
            self::assertNotSame(str_repeat('a', 64), $credentials->find('001')['auth_version']);
            self::assertSame('encrypted-secret', $credentials->find('001')['secret']);

            return 1;
        });
        self::assertSame(1, $result);
        $request = \Illuminate\Http\Request::create('http://localhost/admin/login', 'POST', [], [
            config('admin_two_factor.trusted_cookie') => str_repeat('a', 32) . ':' . str_repeat('b', 64),
        ]);
        config(['admin_two_factor.enabled' => true]);
        self::assertFalse($service->authenticate($request, (object) ['staff_id' => '001', 'password' => 'old-password'], $credentials->find('001')));
    }

    public function test_password_change_preserves_original_behavior_before_table_approval(): void
    {
        config(['admin_two_factor.trusted_devices_approved' => false]);
        DB::shouldReceive('connection')->never();
        $service = new AdminTrustedDeviceService(new InMemoryAdminTrustedDeviceStore(), new InMemoryAdminTwoFactorCredentialStore());
        self::assertSame(7, $service->changePassword('001', fn() => 7));
    }

    public function test_existing_password_change_endpoint_uses_the_same_revocation_transaction(): void
    {
        config(['admin_two_factor.database_approved' => true, 'admin_two_factor.trusted_devices_approved' => true, 'session.driver' => 'array']);
        $credentials = new InMemoryAdminTwoFactorCredentialStore();
        $credentials->credentials['001'] = ['secret' => 'encrypted', 'auth_version' => str_repeat('a', 64)];
        $devices = new InMemoryAdminTrustedDeviceStore();
        $devices->devices['old-device'] = ['staff_id' => '001'];
        $this->app->instance(AdminTrustedDeviceService::class, new AdminTrustedDeviceService($devices, $credentials));
        $connection = Mockery::mock(ConnectionInterface::class);
        $query = Mockery::mock(Builder::class);
        DB::shouldReceive('connection')->with('sqlsrv')->andReturn($connection);
        $connection->shouldReceive('transaction')->once()->andReturnUsing(fn($write) => $write());
        $connection->shouldReceive('table')->twice()->with('dbo.mx_staffs')->andReturn($query);
        $query->shouldReceive('whereRaw')->twice()->with(Mockery::type('string'), ['001'])->andReturnSelf();
        $query->shouldReceive('update')->once()->with(['password' => 'new-password'])->andReturn(1);
        $query->shouldReceive('first')->once()->andReturn((object) ['employment' => '在職']);
        $this->withSession(['password_change_staff_id' => '001'])->post('/staff/password/change', [
            'password' => 'new-password', 'password_confirm' => 'new-password',
        ])->assertRedirect(route('dashboard'));
        self::assertSame([], $devices->devices);
        self::assertNotSame(str_repeat('a', 64), $credentials->find('001')['auth_version']);
    }
}

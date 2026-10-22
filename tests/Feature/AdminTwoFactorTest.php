<?php

namespace Tests\Feature;

use App\Services\Admin\V2\AdminTwoFactorCredentialStore;
use App\Services\Admin\V2\LoginV2Service;
use App\Services\Admin\V2\AdminTrustedDeviceStore;
use App\Services\Admin\V2\AdminTrustedDeviceService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Mockery;
use PragmaRX\Google2FA\Google2FA;
use Tests\Support\InMemoryAdminTwoFactorCredentialStore;
use Tests\Support\InMemoryAdminTrustedDeviceStore;
use Tests\TestCase;
use Psr\Log\LoggerInterface;

class AdminTwoFactorTest extends TestCase
{
    private InMemoryAdminTwoFactorCredentialStore $store;
    private object $staff;
    private Google2FA $totp;
    private InMemoryAdminTrustedDeviceStore $devices;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'admin_two_factor.enabled' => true,
            'admin_two_factor.database_approved' => false,
            'admin_two_factor.trusted_devices_approved' => false,
            'cache.default' => 'array',
            'session.driver' => 'array',
            'session.block_store' => 'array',
            'app.key' => 'base64:' . base64_encode(str_repeat('t', 32)),
        ]);
        // 意図せず既存DBを呼ぶテストは、その時点で失敗させる。
        DB::shouldReceive('connection')->never();
        $this->store = new InMemoryAdminTwoFactorCredentialStore();
        $this->app->instance(AdminTwoFactorCredentialStore::class, $this->store);
        $this->devices = new InMemoryAdminTrustedDeviceStore();
        $this->app->instance(AdminTrustedDeviceStore::class, $this->devices);
        $this->totp = new Google2FA();
        $this->staff = (object) ['staff_id' => 'test-admin', 'staff_name' => 'テスト管理者', 'password' => 'test-password', 'employment' => '在職', 'is_admin' => 1];
        $login = Mockery::mock(LoginV2Service::class)->makePartial();
        $login->shouldReceive('findStaffForLogin')->andReturnUsing(fn(string $id) => $id === $this->staff->staff_id ? $this->staff : null);
        $this->app->instance(LoginV2Service::class, $login);
        Route::middleware(['web', 'admin.auth'])->get('/_test/admin-protected', fn() => response('ok'));
    }

    private function passwordLogin(): \Illuminate\Testing\TestResponse
    {
        return $this->post('/admin/login', ['staff_id' => $this->staff->staff_id, 'password' => 'test-password']);
    }

    private function otp(string $secret, ?int $step = null): string
    {
        return $this->totp->oathTotp($secret, $step ?? intdiv(now()->timestamp, 30));
    }

    private function enroll(): array
    {
        $this->passwordLogin()->assertRedirect(route('admin.two_factor.challenge'));
        $this->get('/admin/two-factor')->assertOk()->assertSee('<svg', false);
        $secret = Crypt::decryptString(session('admin_2fa_setup_secret'));
        $this->post('/admin/two-factor/enroll', ['code' => $this->otp($secret)])
            ->assertRedirect(route('admin.two_factor.recovery'));
        $codes = json_decode(Crypt::decryptString(session('admin_2fa_recovery_codes')), true);

        return [$secret, $codes];
    }

    private function resetThroughTestCommand(): void
    {
        config(['admin_two_factor.database_approved' => true]);
        $audit = Mockery::mock(LoggerInterface::class);
        $audit->shouldReceive('notice')->once()->with('admin_2fa_reset_requested', Mockery::type('array'));
        $audit->shouldReceive('notice')->once()->with('admin_2fa_reset_completed', Mockery::on(function (array $context): bool {
            self::assertSame('test-admin', $context['staff_id']);
            self::assertSame('IN_MEMORY_TEST_ONLY', $context['database']);
            self::assertSame('端末とコードを紛失', $context['reason']);
            self::assertArrayNotHasKey('secret', $context);
            self::assertArrayNotHasKey('recovery_code_hashes', $context);

            return true;
        }));
        Log::shouldReceive('build')->once()->andReturn($audit);
        $this->artisan('admin:2fa-reset', ['staff_id' => 'test-admin', '--reason' => '端末とコードを紛失'])
            ->expectsQuestion('本人確認済みの対象スタッフIDを再入力してください', 'test-admin')
            ->assertExitCode(0);
    }

    public function test_password_success_does_not_create_an_admin_session(): void
    {
        $this->passwordLogin()->assertRedirect(route('admin.two_factor.challenge'))
            ->assertSessionHas('admin_2fa_pending.staff_id', 'test-admin')
            ->assertSessionMissing('admin_logged_in')
            ->assertSessionMissing('admin_staff_id');
        $this->get('/_test/admin-protected')->assertRedirect(route('admin.login'));
        $this->get('/staff/admin-login')->assertRedirect(route('admin.login'));
    }

    public function test_wrong_password_retired_and_non_admin_users_are_rejected(): void
    {
        $this->withSession(['staff_id' => 'unchanged-staff'])->post('/admin/login', ['staff_id' => 'test-admin', 'password' => 'wrong'])
            ->assertSessionHasErrors('staff_id')->assertSessionMissing('admin_2fa_pending')
            ->assertSessionHas('staff_id', 'unchanged-staff');
        $this->staff->is_admin = 0;
        $this->passwordLogin()->assertSessionHasErrors('staff_id')->assertSessionMissing('admin_2fa_pending');
        $this->staff->is_admin = 1;
        $this->staff->employment = '退職';
        $this->passwordLogin()->assertSessionHasErrors('staff_id')->assertSessionMissing('admin_2fa_pending');
    }

    public function test_registration_confirms_totp_and_requires_recovery_code_acknowledgement(): void
    {
        [$secret, $codes] = $this->enroll();
        self::assertCount(10, $codes);
        self::assertCount(10, array_unique($codes));
        self::assertNotSame($secret, $this->store->find('test-admin')['secret']);
        self::assertSame($secret, Crypt::decryptString($this->store->find('test-admin')['secret']));
        foreach ($codes as $code) {
            self::assertStringNotContainsString($code, $this->store->find('test-admin')['recovery_code_hashes']);
        }
        $this->get('/_test/admin-protected')->assertRedirect(route('admin.two_factor.recovery'));
        $this->get('/staff/admin-login')->assertRedirect(route('admin.two_factor.recovery'));
        $this->get('/admin/two-factor/recovery-codes')->assertOk()->assertSee($codes[0])
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->post('/admin/two-factor/recovery-codes/continue')->assertSessionHasErrors('saved');
        $this->post('/admin/two-factor/recovery-codes/continue', ['saved' => '1'])
            ->assertRedirect(route('admin.dashboard'))->assertSessionMissing('admin_2fa_recovery_codes');
        $this->get('/_test/admin-protected')->assertOk();
        $this->get('/admin/two-factor/recovery-codes')->assertRedirect(route('admin.dashboard'));
    }

    public function test_registered_totp_login_and_used_code_rejection(): void
    {
        [$secret] = $this->enroll();
        $this->post('/admin/logout');
        $this->passwordLogin();
        $this->get('/admin/two-factor')->assertOk()->assertDontSee('<svg', false);
        $this->post('/admin/two-factor/verify', ['code' => $this->otp($secret)])
            ->assertSessionHasErrors('code')->assertSessionMissing('admin_logged_in');
        $this->travel(31)->seconds();
        $this->post('/admin/two-factor/verify', ['code' => $this->otp($secret)])
            ->assertRedirect(route('admin.dashboard'))->assertSessionHas('admin_2fa_staff_id', 'test-admin');
        $this->get('/_test/admin-protected')->assertOk();
    }

    public function test_recovery_code_can_be_used_only_once(): void
    {
        [, $codes] = $this->enroll();
        $this->post('/admin/logout');
        $this->passwordLogin();
        $this->post('/admin/two-factor/verify', ['recovery_code' => $codes[0]])
            ->assertRedirect(route('admin.dashboard'))->assertSessionHas('admin_logged_in', true);
        self::assertCount(9, json_decode($this->store->find('test-admin')['recovery_code_hashes'], true));
        $this->post('/admin/logout');
        $this->passwordLogin();
        $this->post('/admin/two-factor/verify', ['recovery_code' => $codes[0]])
            ->assertSessionHasErrors('code')->assertSessionMissing('admin_logged_in');
    }

    public function test_expired_missing_or_revoked_pending_login_cannot_enroll(): void
    {
        $this->get('/admin/two-factor')->assertRedirect(route('admin.login'));
        $this->post('/admin/two-factor/enroll', ['code' => '123456'])->assertRedirect(route('admin.login'));
        $this->get('/admin/two-factor/recovery-codes')->assertRedirect(route('admin.login'));
        $this->passwordLogin();
        $this->travel(601)->seconds();
        $this->get('/admin/two-factor')->assertRedirect(route('admin.login'))->assertSessionMissing('admin_2fa_pending');
        $this->travelBack();
        $this->passwordLogin();
        $this->staff->is_admin = 0;
        $this->get('/admin/two-factor')->assertRedirect(route('admin.login'))->assertSessionMissing('admin_2fa_pending');
        self::assertSame([], $this->store->credentials);
    }

    public function test_existing_admin_flag_or_wrong_identity_does_not_bypass_two_factor(): void
    {
        $this->withSession(['admin_logged_in' => true, 'admin_staff_id' => 'test-admin'])
            ->get('/staff/admin-login')->assertRedirect(route('admin.login'))->assertSessionMissing('admin_logged_in');
        $this->withSession(['admin_logged_in' => true, 'admin_staff_id' => 'test-admin', 'admin_2fa_staff_id' => 'other'])
            ->get('/_test/admin-protected')->assertRedirect(route('admin.login'));
    }

    public function test_rate_limit_is_shared_by_enrollment_and_login_and_survives_password_retry(): void
    {
        $this->passwordLogin();
        $this->get('/admin/two-factor');
        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/two-factor/enroll', ['code' => 'bad'])->assertRedirect();
        }
        $this->passwordLogin();
        $this->post('/admin/two-factor/verify', ['code' => '123456'])->assertStatus(429)
            ->assertSessionMissing('admin_logged_in');
        self::assertSame([], $this->store->credentials);
    }

    public function test_invalid_input_does_not_persist_credentials_or_echo_codes(): void
    {
        $this->passwordLogin();
        $this->get('/admin/two-factor')->assertHeader('Cache-Control', 'no-store, private');
        $this->post('/admin/two-factor/enroll', ['code' => ['123456']])
            ->assertSessionHasErrors('code')->assertSessionMissing('_old_input.code');
        $this->post('/admin/two-factor/enroll', ['code' => '12345'])->assertSessionHasErrors('code');
        self::assertSame([], $this->store->credentials);
    }

    public function test_completed_session_can_be_revoked_and_logout_clears_two_factor(): void
    {
        $this->enroll();
        $this->post('/admin/two-factor/recovery-codes/continue', ['saved' => '1']);
        $this->store->credentials['test-admin']['auth_version'] = str_repeat('0', 64);
        $this->get('/_test/admin-protected')->assertRedirect(route('admin.login'))->assertSessionMissing('admin_logged_in');
        $this->post('/admin/logout')->assertRedirect(route('admin.login'))
            ->assertSessionMissing('admin_2fa_staff_id')->assertSessionMissing('admin_2fa_pending');
    }

    public function test_disabled_feature_keeps_existing_admin_login_behavior_and_blocks_new_pages(): void
    {
        config(['admin_two_factor.enabled' => false]);
        $this->passwordLogin()->assertRedirect(route('admin.dashboard'))->assertSessionHas('admin_logged_in', true);
        $this->get('/_test/admin-protected')->assertOk();
        $this->get('/admin/two-factor')->assertNotFound();
    }

    public function test_real_store_is_blocked_before_database_access_without_approval(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        (new AdminTwoFactorCredentialStore())->find('test-admin');
    }

    public function test_real_store_enrollment_is_also_blocked_before_opening_a_transaction(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        (new AdminTwoFactorCredentialStore())->enroll('test-admin', []);
    }

    public function test_real_store_verification_is_also_blocked_before_opening_a_transaction(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        (new AdminTwoFactorCredentialStore())->consume('test-admin', fn() => []);
    }

    public function test_enrollment_does_not_replace_an_existing_authenticator(): void
    {
        $this->enroll();
        $original = $this->store->find('test-admin');
        $this->post('/admin/logout');
        $this->passwordLogin();
        // 古い登録画面を別タブで保持していた場合も、秘密鍵を置き換えない。
        $differentSecret = $this->totp->generateSecretKey();
        $this->withSession(['admin_2fa_setup_secret' => Crypt::encryptString($differentSecret)])
            ->post('/admin/two-factor/enroll', ['code' => $this->otp($differentSecret)])
            ->assertSessionHasErrors('code')->assertSessionMissing('admin_logged_in');
        self::assertSame($original, $this->store->find('test-admin'));
    }

    public function test_codes_outside_time_window_are_rejected(): void
    {
        $this->passwordLogin();
        $this->get('/admin/two-factor');
        $secret = Crypt::decryptString(session('admin_2fa_setup_secret'));
        $oldCode = $this->otp($secret, intdiv(now()->timestamp, 30) - 2);
        $this->post('/admin/two-factor/enroll', ['code' => $oldCode])
            ->assertSessionHasErrors('code')->assertSessionMissing('admin_logged_in');
        self::assertSame([], $this->store->credentials);
    }

    public function test_reset_command_changes_only_target_and_revokes_completed_admin_session(): void
    {
        [$secret, $codes] = $this->enroll();
        $this->post('/admin/two-factor/recovery-codes/continue', ['saved' => '1']);
        $before = $this->store->find('test-admin');
        $this->store->credentials['other-admin'] = ['staff_id' => 'other-admin'] + $before;
        $other = $this->store->find('other-admin');
        $this->resetThroughTestCommand();
        $after = $this->store->find('test-admin');
        self::assertSame('', $after['secret']);
        self::assertSame('[]', $after['recovery_code_hashes']);
        self::assertSame(0, $after['last_used_step']);
        self::assertNotSame($before['auth_version'], $after['auth_version']);
        self::assertSame($other, $this->store->find('other-admin'));
        $this->get('/_test/admin-protected')->assertRedirect(route('admin.login'))->assertSessionMissing('admin_logged_in');
        $this->get('/staff/admin-login')->assertRedirect(route('admin.login'));
        $this->passwordLogin();
        $this->get('/admin/two-factor')->assertOk()->assertSee('<svg', false);
        self::assertNotSame($secret, Crypt::decryptString(session('admin_2fa_setup_secret')));
        $this->post('/admin/two-factor/verify', ['recovery_code' => $codes[0]])->assertSessionHasErrors('code')->assertSessionMissing('admin_logged_in');
    }

    public function test_reset_command_revokes_pending_totp_and_pending_qr_registration(): void
    {
        $this->enroll();
        $this->post('/admin/logout');
        $this->passwordLogin();
        $this->resetThroughTestCommand();
        $this->get('/admin/two-factor')->assertRedirect(route('admin.login'))->assertSessionMissing('admin_2fa_pending');
        $this->passwordLogin();
        $this->get('/admin/two-factor')->assertOk();
        $secret = Crypt::decryptString(session('admin_2fa_setup_secret'));
        $row = $this->store->find('test-admin');
        // 再登録途中に再び初期化しても、保持していたQRは確定できない。
        self::assertTrue($this->store->reset('test-admin', $row['auth_version']));
        $this->post('/admin/two-factor/enroll', ['code' => $this->otp($secret)])
            ->assertRedirect(route('admin.login'))->assertSessionMissing('admin_2fa_pending');
        self::assertSame('', $this->store->find('test-admin')['secret']);
    }

    public function test_reenrollment_requires_new_totp_and_new_recovery_codes(): void
    {
        [$oldSecret, $oldCodes] = $this->enroll();
        $this->resetThroughTestCommand();
        $this->post('/admin/logout');
        [$newSecret, $newCodes] = $this->enroll();
        self::assertNotSame($oldSecret, $newSecret);
        self::assertSame([], array_intersect($oldCodes, $newCodes));
        $this->post('/admin/two-factor/recovery-codes/continue', ['saved' => '1']);
        $this->get('/_test/admin-protected')->assertOk();
        $this->post('/admin/logout');
        $this->passwordLogin();
        $this->post('/admin/two-factor/verify', ['recovery_code' => $oldCodes[0]])->assertSessionHasErrors('code');
        $this->post('/admin/two-factor/verify', ['recovery_code' => $newCodes[0]])->assertRedirect(route('admin.dashboard'));
    }

    public function test_confirmation_mismatch_and_dry_run_do_not_reset(): void
    {
        $this->enroll();
        config(['admin_two_factor.database_approved' => true]);
        $original = $this->store->find('test-admin');
        Log::shouldReceive('build')->never();
        $this->artisan('admin:2fa-reset', ['staff_id' => 'test-admin', '--reason' => '確認', '--dry-run' => true])->assertExitCode(0);
        self::assertSame($original, $this->store->find('test-admin'));
        $this->artisan('admin:2fa-reset', ['staff_id' => 'test-admin', '--reason' => '確認'])
            ->expectsQuestion('本人確認済みの対象スタッフIDを再入力してください', 'wrong-id')->assertExitCode(1);
        self::assertSame($original, $this->store->find('test-admin'));
    }

    public function test_reset_command_rejects_missing_reason_unknown_and_non_admin_targets(): void
    {
        $this->enroll();
        config(['admin_two_factor.database_approved' => true]);
        $original = $this->store->find('test-admin');
        $this->artisan('admin:2fa-reset', ['staff_id' => 'test-admin'])->assertExitCode(1);
        $this->artisan('admin:2fa-reset', ['staff_id' => 'missing', '--reason' => '確認'])->assertExitCode(1);
        $this->staff->is_admin = 0;
        $this->artisan('admin:2fa-reset', ['staff_id' => 'test-admin', '--reason' => '確認'])->assertExitCode(1);
        self::assertSame($original, $this->store->find('test-admin'));
    }

    public function test_reset_requires_approved_store_and_non_interactive_execution_is_rejected(): void
    {
        $this->enroll();
        $original = $this->store->find('test-admin');
        $this->artisan('admin:2fa-reset', ['staff_id' => 'test-admin', '--reason' => '確認'])->assertExitCode(1);
        config(['admin_two_factor.database_approved' => true]);
        $this->artisan('admin:2fa-reset', ['staff_id' => 'test-admin', '--reason' => '確認', '--no-interaction' => true])->assertExitCode(1);
        self::assertSame($original, $this->store->find('test-admin'));
    }

    public function test_reset_aborts_if_audit_log_cannot_be_written(): void
    {
        $this->enroll();
        config(['admin_two_factor.database_approved' => true]);
        $original = $this->store->find('test-admin');
        Log::shouldReceive('build')->once()->andThrow(new \RuntimeException('test log failure'));
        $this->artisan('admin:2fa-reset', ['staff_id' => 'test-admin', '--reason' => '確認'])
            ->expectsQuestion('本人確認済みの対象スタッフIDを再入力してください', 'test-admin')->assertExitCode(1);
        self::assertSame($original, $this->store->find('test-admin'));
    }

    public function test_reset_and_enrollment_recheck_generation_under_the_store_lock(): void
    {
        $this->enroll();
        $version = $this->store->find('test-admin')['auth_version'];
        self::assertTrue($this->store->reset('test-admin', $version));
        $reset = $this->store->find('test-admin');
        self::assertFalse($this->store->reset('test-admin', $version));
        $service = $this->app->make(\App\Services\Admin\V2\AdminTwoFactorService::class);
        $secret = $this->totp->generateSecretKey();
        self::assertNull($service->enroll('test-admin', $secret, $this->otp($secret), $version));
        self::assertSame($reset, $this->store->find('test-admin'));
    }

    public function test_inherited_staff_session_is_revoked_without_changing_staff_login(): void
    {
        $this->enroll();
        $this->post('/admin/two-factor/recovery-codes/continue', ['saved' => '1']);
        $this->withSession(['staff_id' => 'test-admin']);
        $this->resetThroughTestCommand();
        // StaffPortalAuthenticateは業務DBへ到達する前に失効を確認する。
        $this->get('/staff/dashboard')->assertRedirect(route('login.portal'))
            ->assertSessionMissing('staff_id')->assertSessionMissing('admin_logged_in');
    }

    public function test_old_pending_session_without_generation_must_reenter_password(): void
    {
        $this->withSession(['admin_2fa_pending' => ['staff_id' => 'test-admin', 'expires_at' => now()->timestamp + 600]])
            ->get('/admin/two-factor')->assertRedirect(route('admin.login'))->assertSessionMissing('admin_2fa_pending');
    }

    public function test_old_browser_sessions_remain_revoked_after_successful_reenrollment(): void
    {
        $this->enroll();
        $this->post('/admin/two-factor/recovery-codes/continue', ['saved' => '1']);
        $oldLoggedInSession = session()->all();
        $this->post('/admin/logout');
        $this->passwordLogin();
        $oldPendingSession = session()->all();
        $this->resetThroughTestCommand();
        $this->post('/admin/logout');
        $this->enroll();
        $this->post('/admin/two-factor/recovery-codes/continue', ['saved' => '1']);
        $this->get('/_test/admin-protected')->assertOk();
        session()->flush();
        $this->withSession($oldLoggedInSession)->get('/_test/admin-protected')->assertRedirect(route('admin.login'));
        session()->flush();
        $this->withSession($oldPendingSession)->get('/admin/two-factor')->assertRedirect(route('admin.login'));
    }

    private function trustCurrentDevice(): array
    {
        config(['admin_two_factor.database_approved' => true, 'admin_two_factor.trusted_devices_approved' => true]);
        $this->enroll();
        $this->withSession(['admin_2fa_trust_requested' => $this->app->make(AdminTrustedDeviceService::class)->passwordFingerprint($this->staff->password)]);
        $response = $this->post('/admin/two-factor/recovery-codes/continue', ['saved' => '1']);
        $cookie = $response->getCookie(config('admin_two_factor.trusted_cookie'));
        self::assertNotNull($cookie);

        return [$cookie->getValue(), array_values($this->devices->devices)[0], $cookie];
    }

    public function test_trusted_device_skips_totp_only_after_correct_password_and_rotates_token(): void
    {
        [$cookie, $device] = $this->trustCurrentDevice();
        $this->post('/admin/logout');
        $this->withCookie(config('admin_two_factor.trusted_cookie'), $cookie);
        $this->post('/admin/login', ['staff_id' => 'test-admin', 'password' => 'wrong'])
            ->assertSessionHasErrors('staff_id')->assertSessionMissing('admin_logged_in');
        $response = $this->passwordLogin()->assertRedirect(route('admin.dashboard'))->assertSessionHas('admin_logged_in', true);
        $rotated = $response->getCookie(config('admin_two_factor.trusted_cookie'));
        self::assertNotSame($cookie, $rotated->getValue());
        self::assertNotSame($device['token_hash'], $this->devices->devices[$device['id']]['token_hash']);
        self::assertEquals($device['expires_at'], $this->devices->devices[$device['id']]['expires_at']);
        $this->post('/admin/logout');
        // 古いトークンのコピーでは再ログインできない。
        $this->withCookie(config('admin_two_factor.trusted_cookie'), $cookie);
        $this->passwordLogin()->assertRedirect(route('admin.two_factor.challenge'))->assertSessionMissing('admin_logged_in');
    }

    public function test_trusted_token_is_hashed_and_cookie_security_flags_are_set(): void
    {
        [$cookie, $device, $attributes] = $this->trustCurrentDevice();
        [, $token] = explode(':', $cookie);
        self::assertSame(hash('sha256', $token), $device['token_hash']);
        self::assertStringNotContainsString($token, json_encode($device));
        self::assertNotSame($this->staff->password, $device['password_fingerprint']);
        self::assertTrue($attributes->isHttpOnly());
        self::assertSame('strict', $attributes->getSameSite());
        self::assertSame('/admin', $attributes->getPath());
        self::assertEqualsWithDelta(now()->addDays(30)->timestamp, $attributes->getExpiresTime(), 60);
    }

    public function test_trusted_device_expires_at_30_days_and_is_not_sliding(): void
    {
        [$cookie] = $this->trustCurrentDevice();
        $this->post('/admin/logout');
        $this->travel(30)->days();
        $this->withCookie(config('admin_two_factor.trusted_cookie'), $cookie);
        $this->passwordLogin()->assertRedirect(route('admin.two_factor.challenge'))->assertSessionMissing('admin_logged_in');
    }

    public function test_reset_revokes_all_trusted_devices_without_deleting_other_staff_devices(): void
    {
        [$cookie, $device] = $this->trustCurrentDevice();
        $other = $device;
        $other['id'] = str_repeat('b', 32);
        $other['staff_id'] = 'other-admin';
        $this->devices->create($other);
        $this->resetThroughTestCommand();
        self::assertSame($other, $this->devices->devices[$other['id']]);
        $this->post('/admin/logout');
        $this->withCookie(config('admin_two_factor.trusted_cookie'), $cookie);
        $this->passwordLogin()->assertRedirect(route('admin.two_factor.challenge'))->assertSessionMissing('admin_logged_in');
        $this->get('/admin/two-factor')->assertSee('<svg', false);
    }

    public function test_changed_password_and_other_account_cannot_use_old_trusted_token(): void
    {
        [$cookie] = $this->trustCurrentDevice();
        $this->post('/admin/logout');
        $this->staff->password = 'changed-password';
        $this->withCookie(config('admin_two_factor.trusted_cookie'), $cookie);
        $this->post('/admin/login', ['staff_id' => 'test-admin', 'password' => 'changed-password'])
            ->assertRedirect(route('admin.two_factor.challenge'))->assertSessionMissing('admin_logged_in');
        $this->staff->password = 'test-password';
        $this->staff->staff_id = 'other-admin';
        $this->store->credentials['other-admin'] = $this->store->credentials['test-admin'];
        $this->passwordLogin()->assertRedirect(route('admin.two_factor.challenge'))->assertSessionMissing('admin_logged_in');
    }

    public function test_trust_is_not_issued_without_checkbox_or_after_recovery_code_login(): void
    {
        config(['admin_two_factor.database_approved' => true, 'admin_two_factor.trusted_devices_approved' => true]);
        [, $codes] = $this->enroll();
        $this->post('/admin/two-factor/recovery-codes/continue', ['saved' => '1']);
        self::assertSame([], $this->devices->devices);
        $this->post('/admin/logout');
        $this->passwordLogin();
        $this->post('/admin/two-factor/verify', ['recovery_code' => $codes[0], 'trust_device' => '1'])->assertRedirect(route('admin.dashboard'));
        self::assertSame([], $this->devices->devices);
    }

    public function test_trust_checkbox_registration_waits_for_recovery_acknowledgement(): void
    {
        config(['admin_two_factor.database_approved' => true, 'admin_two_factor.trusted_devices_approved' => true]);
        $this->passwordLogin();
        $this->get('/admin/two-factor')->assertSee('trust_device');
        $secret = Crypt::decryptString(session('admin_2fa_setup_secret'));
        $this->post('/admin/two-factor/enroll', ['code' => $this->otp($secret), 'trust_device' => '1'])
            ->assertRedirect(route('admin.two_factor.recovery'));
        self::assertSame([], $this->devices->devices);
        $this->post('/admin/two-factor/recovery-codes/continue', ['saved' => '1'])
            ->assertCookie(config('admin_two_factor.trusted_cookie'));
        self::assertCount(1, $this->devices->devices);
    }

    public function test_unapproved_trusted_device_feature_never_uses_the_new_table(): void
    {
        $this->enroll();
        $this->get('/admin/two-factor/recovery-codes')->assertOk();
        self::assertSame([], $this->devices->devices);
        $this->post('/admin/logout');
        $this->withCookie(config('admin_two_factor.trusted_cookie'), str_repeat('a', 32) . ':' . str_repeat('a', 64));
        $this->passwordLogin()->assertRedirect(route('admin.two_factor.challenge'));
        $this->get('/admin/two-factor')->assertDontSee('trust_device');
    }

    public function test_totp_login_issues_trust_cookie_only_with_checkbox(): void
    {
        config(['admin_two_factor.database_approved' => true, 'admin_two_factor.trusted_devices_approved' => true]);
        [$secret] = $this->enroll();
        $this->post('/admin/logout');
        $this->passwordLogin();
        $this->travel(31)->seconds();
        $this->post('https://localhost/admin/two-factor/verify', ['code' => $this->otp($secret), 'trust_device' => '1'])
            ->assertRedirect(route('admin.dashboard'))->assertCookie(config('admin_two_factor.trusted_cookie'));
        self::assertCount(1, $this->devices->devices);
        self::assertTrue(\Illuminate\Support\Facades\Cookie::queued(config('admin_two_factor.trusted_cookie'))->isSecure());
    }

    public function test_tampered_token_and_non_loopback_plain_http_cannot_be_trusted(): void
    {
        [$cookie] = $this->trustCurrentDevice();
        $this->post('/admin/logout');
        [$selector] = explode(':', $cookie);
        $this->withCookie(config('admin_two_factor.trusted_cookie'), $selector . ':' . str_repeat('0', 64));
        $this->passwordLogin()->assertRedirect(route('admin.two_factor.challenge'))->assertSessionMissing('admin_logged_in');
        $service = $this->app->make(AdminTrustedDeviceService::class);
        self::assertFalse($service->available(\Illuminate\Http\Request::create('http://example.test/admin/login')));
    }

    public function test_password_change_during_two_factor_requires_password_again(): void
    {
        $this->passwordLogin();
        $this->get('/admin/two-factor');
        $secret = Crypt::decryptString(session('admin_2fa_setup_secret'));
        $this->staff->password = 'new-password';
        $this->post('/admin/two-factor/enroll', ['code' => $this->otp($secret), 'trust_device' => '1'])
            ->assertRedirect(route('admin.login'))->assertSessionMissing('admin_2fa_pending');
        self::assertSame([], $this->devices->devices);
    }
}

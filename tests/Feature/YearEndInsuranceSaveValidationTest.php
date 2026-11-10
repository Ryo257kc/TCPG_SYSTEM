<?php

namespace Tests\Feature;

use App\Http\Controllers\StaffPortal\YearEnd\YearEndApplicationController;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class YearEndInsuranceSaveValidationTest extends TestCase
{
    private function controllerWithExistingDraft(): array
    {
        $controller = Mockery::mock(YearEndApplicationController::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $controller->shouldReceive('staffPortalStaffId')->andReturn('TEST');
        $controller->shouldReceive('staffPortalStaffRow')->with('TEST')->andReturn([]);
        $controller->shouldReceive('isAdmin')->andReturnTrue();
        $connection = Mockery::mock(ConnectionInterface::class);
        DB::shouldReceive('connection')->with('sqlsrv_payroll')->andReturn($connection);
        $draft = Mockery::mock(Builder::class);
        $connection->shouldReceive('table')->once()->with('dbo.mx_nen_tyo')->andReturn($draft);
        $draft->shouldReceive('whereRaw')->once()->with('LTRIM(RTRIM(staff_id)) = ?', ['TEST'])->andReturnSelf();
        $draft->shouldReceive('whereYear')->once()->with('year_end', (int) date('Y'))->andReturnSelf();
        $draft->shouldReceive('orderBy')->once()->with('nen_tyo_no')->andReturnSelf();
        $draft->shouldReceive('first')->once()->andReturn((object) ['nen_tyo_no' => 1, 'application_status' => 'draft']);

        return [$controller, $connection];
    }

    public function test_no_with_new_insurance_is_rejected_instead_of_silently_reporting_success(): void
    {
        [$controller] = $this->controllerWithExistingDraft();
        $request = Request::create('/', 'POST', [
            'insurance_engaged' => '0',
            'hoken_add' => [1 => ['enabled' => '1', 'insurance_company' => 'テスト保険']],
        ]);
        $request->headers->set('Accept', 'application/json');
        try {
            $controller->updateInsurance($request);
            self::fail('The new row must not be silently ignored.');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('insurance_engaged', $e->errors());
        }
    }

    public function test_previous_job_validation_errors_are_japanese_without_database_writes(): void
    {
        [$controller] = $this->controllerWithExistingDraft();
        $request = Request::create('/', 'POST', [
            'previous_job_withholding_changed' => '1',
            'zen_syamei' => '', 'zen_tai_date' => 'invalid', 'zen_shotoku' => -1,
        ]);
        $request->headers->set('Accept', 'application/json');
        try {
            $controller->updatePreviousJob($request);
            self::fail('Invalid previous job fields must be rejected.');
        } catch (ValidationException $e) {
            self::assertSame('前職の会社名を入力してください。', $e->errors()['zen_syamei'][0]);
            self::assertSame('退職日は正しい日付で入力してください。', $e->errors()['zen_tai_date'][0]);
            self::assertSame('前職の給与収入額は0以上で入力してください。', $e->errors()['zen_shotoku'][0]);
        }
    }

    public function test_yes_with_new_insurance_without_attachment_returns_file_error_before_writing(): void
    {
        [$controller, $connection] = $this->controllerWithExistingDraft();
        $existing = Mockery::mock(Builder::class);
        $connection->shouldReceive('table')->once()->with('dbo.mx_hoken')->andReturn($existing);
        $existing->shouldReceive('where')->once()->with('insurance_staff_no', 'TEST')->andReturnSelf();
        $existing->shouldReceive('whereYear')->once()->with('insurance_year', (int) date('Y'))->andReturnSelf();
        $existing->shouldReceive('get')->once()->andReturn(collect());
        $request = Request::create('/', 'POST', [
            'insurance_engaged' => '1',
            'hoken_add' => [1 => ['enabled' => '1', 'insurance_company' => 'テスト保険']],
        ]);
        $request->headers->set('Accept', 'application/json');
        try {
            $controller->updateInsurance($request);
            self::fail('An attachment error must keep the AJAX form on screen.');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('hoken_add.1.certificate_file', $e->errors());
        }
    }

    public function test_draft_saves_new_insurance_without_attachment_but_keeps_section_incomplete(): void
    {
        [$controller, $connection] = $this->controllerWithExistingDraft();
        $existing = Mockery::mock(Builder::class);
        $insert = Mockery::mock(Builder::class);
        $connection->shouldReceive('table')->with('dbo.mx_hoken')->andReturn($existing, $insert);
        $existing->shouldReceive('where')->once()->with('insurance_staff_no', 'TEST')->andReturnSelf();
        $existing->shouldReceive('whereYear')->once()->with('insurance_year', (int) date('Y'))->andReturnSelf();
        $existing->shouldReceive('get')->once()->andReturn(collect());
        $insert->shouldReceive('insert')->once()->with(Mockery::on(function ($values) {
            self::assertSame('テスト保険', $values['insurance_company']);
            self::assertSame('12000', $values['declared_amount']);
            self::assertNull($values['certificate_file_path']);
            self::assertNull($values['certificate_original_name']);
            self::assertSame('TEST', $values['insurance_staff_no']);
            return true;
        }))->andReturnTrue();
        $header = Mockery::mock(Builder::class);
        $connection->shouldReceive('table')->once()->with('dbo.mx_nen_tyo')->andReturn($header);
        $header->shouldReceive('where')->once()->with('nen_tyo_no', 1)->andReturnSelf();
        $header->shouldReceive('update')->once()->with(['insurance_deduction_changed' => null])->andReturn(1);
        $request = Request::create('/', 'POST', [
            'insurance_engaged' => '1', 'insurance_save_mode' => 'draft',
            'hoken_add' => [1 => ['enabled' => '1', 'insurance_company' => 'テスト保険', 'declared_amount' => '12000']],
        ]);
        $request->headers->set('Accept', 'application/json');
        $response = $controller->updateInsurance($request);
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('下書き保存', $response->getData(true)['message']);
    }
}

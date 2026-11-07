<?php

namespace Tests\Feature;

use App\Http\Controllers\StaffPortal\YearEnd\YearEndApplicationController;
use App\Services\YearEnd\CertificateFileService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mockery;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class YearEndAttachmentAccessTest extends TestCase
{
    private function controller(CertificateFileService $files): YearEndApplicationController
    {
        $controller = Mockery::mock(YearEndApplicationController::class)->makePartial()->shouldAllowMockingProtectedMethods();
        (new \ReflectionProperty(YearEndApplicationController::class, 'certificateFileService'))->setValue($controller, $files);
        $controller->shouldReceive('staffPortalStaffId')->andReturn('TEST');
        $controller->shouldReceive('staffPortalStaffRow')->with('TEST')->andReturn([]);
        $controller->shouldReceive('isAdmin')->andReturnTrue();
        return $controller;
    }

    public function test_upload_size_failure_is_reported_before_any_database_or_file_writes(): void
    {
        DB::shouldReceive('connection')->never();
        $files = Mockery::mock(CertificateFileService::class)->makePartial();
        $files->shouldNotReceive('store');
        $files->shouldNotReceive('delete');
        $request = Request::create('/', 'POST', [], [], ['hoken_add' => [1 => [
            'certificate_file' => new UploadedFile('', 'camera.jpg', 'image/jpeg', UPLOAD_ERR_INI_SIZE, true),
        ]]]);
        try {
            $this->controller($files)->updateInsurance($request);
            self::fail('Failed upload must not be silently discarded.');
        } catch (ValidationException $e) {
            self::assertStringContainsString('サイズ', $e->errors()['hoken_add.1.certificate_file'][0]);
        }
    }

    public function test_heic_is_reported_explicitly_without_saving_or_renaming(): void
    {
        DB::shouldReceive('connection')->never();
        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('isValid')->once()->andReturnTrue();
        $file->shouldReceive('getMimeType')->once()->andReturn('image/heic');
        $file->shouldNotReceive('storeAs');
        try {
            (new CertificateFileService())->store($file, 'test', 'camera');
            self::fail('HEIC must not be renamed to jpg.');
        } catch (ValidationException $e) {
            self::assertStringContainsString('HEIC', $e->errors()['certificate_file'][0]);
        }
    }

    private function previewQuery(?object $row): void
    {
        $connection = Mockery::mock(ConnectionInterface::class);
        $query = Mockery::mock(Builder::class);
        DB::shouldReceive('connection')->once()->with('sqlsrv_payroll')->andReturn($connection);
        $connection->shouldReceive('table')->once()->with('dbo.mx_hoken')->andReturn($query);
        $query->shouldReceive('where')->once()->with('hoken_no', 10)->andReturnSelf();
        $query->shouldReceive('where')->once()->with('insurance_staff_no', 'TEST')->andReturnSelf();
        $query->shouldReceive('whereYear')->once()->with('insurance_year', (int) date('Y'))->andReturnSelf();
        $query->shouldReceive('first')->once()->andReturn($row);
    }

    public function test_supported_jpeg_is_saved_with_its_actual_format_without_real_storage_writes(): void
    {
        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('isValid')->once()->andReturnTrue();
        $file->shouldReceive('getMimeType')->andReturn('image/jpeg');
        $file->shouldReceive('getClientOriginalExtension')->andReturn('jpg');
        $file->shouldReceive('getRealPath')->andReturn(__DIR__ . '/nonexistent-upload');
        $file->shouldReceive('storeAs')->once()->with('test', 'camera.jpg', 'local')->andReturn('test/camera.jpg');
        $file->shouldReceive('getClientOriginalName')->once()->andReturn('camera.jpg');
        $saved = (new CertificateFileService())->store($file, 'test', 'camera');
        self::assertSame('test/camera.jpg', $saved['path']);
    }

    public function test_storage_failure_is_reported_instead_of_returning_an_empty_attachment_path(): void
    {
        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('isValid')->once()->andReturnTrue();
        $file->shouldReceive('getMimeType')->andReturn('application/pdf');
        $file->shouldReceive('getClientOriginalExtension')->andReturn('pdf');
        $file->shouldReceive('storeAs')->once()->with('test', 'certificate.pdf', 'local')->andReturnFalse();
        try {
            (new CertificateFileService())->store($file, 'test', 'certificate');
            self::fail('Storage failure must be reported.');
        } catch (ValidationException $e) {
            self::assertStringContainsString('保存できませんでした', $e->errors()['certificate_file'][0]);
        }
    }

    public function test_own_current_year_attachment_is_viewable_without_submission(): void
    {
        $this->previewQuery((object) ['certificate_file_path' => 'year_end/test.jpg']);
        $files = Mockery::mock(CertificateFileService::class);
        $files->shouldReceive('exists')->once()->with('year_end/test.jpg')->andReturnTrue();
        $files->shouldReceive('mimeType')->once()->with('year_end/test.jpg')->andReturn('image/jpeg');
        $files->shouldReceive('response')->once()->with('year_end/test.jpg', 'test.jpg')->andReturn(new StreamedResponse());
        $response = $this->controller($files)->certificatePreview(Request::create('/'), 'insurance', 10);
        self::assertSame('image/jpeg', $response->headers->get('Content-Type'));
        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        self::assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_other_staff_or_other_year_attachment_is_not_accessible(): void
    {
        $this->previewQuery(null);
        $files = Mockery::mock(CertificateFileService::class);
        $files->shouldNotReceive('exists');
        $files->shouldNotReceive('response');
        try {
            $this->controller($files)->certificatePreview(Request::create('/'), 'insurance', 10);
            self::fail('Unowned attachment must be rejected.');
        } catch (HttpException $e) {
            self::assertSame(404, $e->getStatusCode());
        }
    }

    public function test_unsupported_stored_file_is_not_served_as_an_image(): void
    {
        $this->previewQuery((object) ['certificate_file_path' => 'old-heic.jpg']);
        $files = Mockery::mock(CertificateFileService::class);
        $files->shouldReceive('exists')->once()->with('old-heic.jpg')->andReturnTrue();
        $files->shouldReceive('mimeType')->once()->with('old-heic.jpg')->andReturn('image/heic');
        $files->shouldNotReceive('response');
        try {
            $this->controller($files)->certificatePreview(Request::create('/'), 'insurance', 10);
            self::fail('Unsupported stored format must be identified.');
        } catch (HttpException $e) {
            self::assertSame(422, $e->getStatusCode());
        }
    }
}

<?php

namespace Tests\Feature;

use App\Http\Controllers\StaffPortal\OfficeController;
use App\Services\Admin\V2\Master\CompanyV2Service;
use App\Services\Admin\V2\Sales\SalesV2Service;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ReceivableReturnsTabTest extends TestCase
{
    private function controller(bool $admin): OfficeController
    {
        DB::shouldReceive('connection')->never();
        $companies = Mockery::mock(CompanyV2Service::class);
        $companies->shouldReceive('list')->with('')->andReturn(['rows' => [['company_id' => '2', 'company_name' => '株式会社トータルケア']]]);
        $controller = Mockery::mock(OfficeController::class, [$companies, Mockery::mock(SalesV2Service::class)])
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $controller->shouldReceive('staffPortalStaffRow')->with('TEST')->andReturn(['is_admin' => $admin ? 1 : 0, 'is_payment_check_user' => 1]);
        $controller->shouldReceive('receiptStoreOptions')->andReturn([]);
        $controller->shouldReceive('commonViewData')->andReturnUsing(fn($request, $data) => $data + ['displayName' => 'テスト', 'errors' => new \Illuminate\Support\ViewErrorBag()]);
        return $controller;
    }

    private function request(array $parameters): Request
    {
        $request = Request::create('/', 'GET', $parameters);
        $session = app('session')->driver('array');
        $session->put('staff_id', 'TEST');
        $request->setLaravelSession($session);
        return $request;
    }

    private function query(OfficeController $controller, array $records = [], ?string $cutoff = 'current'): Builder
    {
        $query = Mockery::mock(Builder::class);
        $controller->shouldReceive('receiptDetailBaseQuery')->once()->andReturn($query);
        $query->shouldReceive('select')->once()->andReturnSelf();
        $query->shouldReceive('selectRaw')->once()->andReturnSelf();
        $query->shouldReceive('where')->once()->with('detail.insurer_number', '<>', '99999999')->andReturnSelf();
        if ($cutoff !== null) {
            $query->shouldReceive('where')->once()->with('detail.treatment_month', '<', $cutoff === 'current' ? now()->startOfMonth()->addMonth()->format('Y-m-d') : $cutoff)->andReturnSelf();
        }
        $query->shouldReceive('orderBy')->times(3)->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn(collect($records));
        return $query;
    }

    public function test_non_admin_cannot_open_return_tab_even_with_payment_check_permission(): void
    {
        $controller = $this->controller(false);
        $controller->shouldNotReceive('receiptDetailBaseQuery');
        try {
            $controller->uncollectedReceivables($this->request(['tab' => 'returns']));
            self::fail('Non-admin must be denied.');
        } catch (HttpException $e) {
            self::assertSame(403, $e->getStatusCode());
        }
    }

    public function test_return_tab_keeps_zero_remaining_and_uncollectible_rows(): void
    {
        $controller = $this->controller(true);
        $row = (object) [
            'insurance_claim_detail_id' => 1, 'insurer_number' => '12345678', 'insurer_name' => '保険者',
            'insurance_type' => '一般', 'treatment_month' => '2025-03-31', 'claim_amount' => 1000,
            'adjustment_amount' => 0, 'cash_collected_amount' => 0, 'returned_amount' => -1000,
            'is_uncollectible' => 1, 'returned_journal_processed_at' => null, 'remarks' => '',
            'payment_date_text' => '', 'payment_amount' => 0, 'remaining_amount' => 0, 'deposit_name' => '',
            'store_category' => 'さくら店舗', 'company_id' => '2', 'company_name' => '株式会社トータルケア', 'bank_account_selection' => '',
        ];
        $query = $this->query($controller, [$row]);
        $query->shouldReceive('addSelect')->once()->andReturnSelf();
        $query->shouldReceive('whereRaw')->once()->with('COALESCE(detail.returned_amount, 0) <> 0')->andReturnSelf();
        $query->shouldReceive('whereNull')->once()->with('detail.returned_journal_processed_at')->andReturnSelf();
        $view = $controller->uncollectedReceivables($this->request(['tab' => 'returns']));
        $data = $view->getData();
        self::assertCount(1, $data['uncollectedRows']);
        self::assertTrue($data['uncollectedRows'][0]['is_uncollectible']);
        self::assertSame('-1,000', $data['returnTotals']['returned_amount']);
        $html = $view->render();
        self::assertStringContainsString('返戻仕訳処理日', $html);
        self::assertStringContainsString(route('office.sales.uncollected.returns.print'), $html);
        self::assertStringContainsString('選択した明細の処理日を保存', $html);
        self::assertStringNotContainsString('name="returned_amount"', $html);
        self::assertSame(1, substr_count($html, 'type="month"'));
        self::assertStringNotContainsString('name="return_month"', $html);
        self::assertStringNotContainsString('name="processing_month"', $html);
        $print = view('staff_portal.office.sales.uncollected.returns_print', $data)->render();
        self::assertStringContainsString('返戻・回収不可', $print);
        self::assertStringContainsString('-1,000', $print);
        self::assertStringContainsString('☑', $print);
        self::assertStringContainsString('12345678', $print);
        self::assertStringNotContainsString('method="post"', $print);
    }

    public function test_processed_month_and_company_filters_use_processing_date_not_treatment_month(): void
    {
        $controller = $this->controller(true);
        $query = $this->query($controller, [], '2025-10-01');
        $query->shouldReceive('addSelect')->once()->andReturnSelf();
        $query->shouldReceive('whereRaw')->once()->with('COALESCE(detail.returned_amount, 0) <> 0')->andReturnSelf();
        $query->shouldReceive('whereNotNull')->once()->with('detail.returned_journal_processed_at')->andReturnSelf();
        $query->shouldReceive('where')->once()->with('detail.returned_journal_processed_at', '>=', '2025-09-30')->andReturnSelf();
        $query->shouldReceive('where')->once()->with('detail.returned_journal_processed_at', '<', '2025-10-01')->andReturnSelf();
        $query->shouldReceive('where')->once()->with('company.company_id', '2')->andReturnSelf();
        $printView = $controller->returnsPrint($this->request([
            'tab' => 'returns', 'return_status' => 'processed', 'payment_month' => '2025-09', 'company_id' => '2',
        ]));
        self::assertSame('staff_portal.office.sales.uncollected.returns_print', $printView->name());
        $data = $printView->getData();
        self::assertTrue($data['isReturnTab']);
        self::assertSame('2025-09', $data['paymentMonth']);
        self::assertSame('processed', $data['returnStatus']);
        self::assertStringContainsString('決算月：2025/09', $printView->render());
    }

    public function test_print_url_is_admin_only_even_without_return_tab_parameter(): void
    {
        $controller = $this->controller(false);
        $controller->shouldNotReceive('receiptDetailBaseQuery');
        try {
            $controller->returnsPrint($this->request([]));
            self::fail('Print route must enforce admin permission.');
        } catch (HttpException $e) {
            self::assertSame(403, $e->getStatusCode());
        }
    }

    public function test_existing_uncollected_tab_keeps_original_filters_and_hides_admin_tab(): void
    {
        $controller = $this->controller(false);
        $query = $this->query($controller, [], null);
        $query->shouldReceive('whereRaw')->once()->with(Mockery::on(fn($sql) => str_ends_with($sql, ' <> 0') && str_contains($sql, 'payment_amount')))->andReturnSelf();
        $query->shouldReceive('where')->once()->with(Mockery::type('Closure'))->andReturnUsing(function ($callback) use ($query) {
            $nested = Mockery::mock(Builder::class);
            $nested->shouldReceive('whereNull')->once()->with('detail.is_uncollectible')->andReturnSelf();
            $nested->shouldReceive('orWhere')->once()->with('detail.is_uncollectible', false)->andReturnSelf();
            $nested->shouldReceive('orWhere')->once()->with('detail.is_uncollectible', 0)->andReturnSelf();
            $callback($nested);
            return $query;
        });
        $view = $controller->uncollectedReceivables($this->request([]));
        self::assertFalse($view->getData()['isReturnTab']);
        self::assertStringNotContainsString('未収入金の表示切替', $view->render());
    }

    public function test_csv_is_admin_only_even_with_payment_check_permission(): void
    {
        try {
            $this->controller(false)->returnsCsv($this->request(['payment_month' => '2025-09', 'return_status' => 'processed', 'company_id' => '2']));
            self::fail('CSV export must be admin-only.');
        } catch (HttpException $e) {
            self::assertSame(403, $e->getStatusCode());
        }
    }

    public function test_csv_rejects_export_of_unprocessed_rows_or_multiple_companies(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->controller(true)->returnsCsv($this->request(['payment_month' => '2025-09', 'return_status' => 'unprocessed', 'company_id' => '']));
    }

    public function test_csv_signs_tax_columns_and_fractional_amounts_use_saved_values(): void
    {
        $controller = $this->controller(true);
        $base = ['insurance_claim_detail_id' => 1, 'insurer_number' => '12345678', 'insurer_name' => '保険者',
            'insurance_type' => '一般', 'treatment_month' => '2024-06-30', 'claim_amount' => 0,
            'adjustment_amount' => 0, 'cash_collected_amount' => 0, 'is_uncollectible' => 1,
            'returned_journal_processed_at' => '2025-09-30', 'remarks' => '', 'payment_date_text' => '',
            'payment_amount' => 0, 'remaining_amount' => 0, 'deposit_name' => '', 'store_category' => 'さくら店舗',
            'company_id' => '2', 'company_name' => '株式会社トータルケア', 'bank_account_selection' => ''];
        $query = $this->query($controller, [
            (object) ($base + ['returned_amount' => '-1427.0000']),
            (object) ($base + ['returned_amount' => '516.2500']),
        ], '2025-10-01');
        $query->shouldReceive('addSelect')->once()->andReturnSelf();
        $query->shouldReceive('whereRaw')->once()->with('COALESCE(detail.returned_amount, 0) <> 0')->andReturnSelf();
        $query->shouldReceive('whereNotNull')->once()->with('detail.returned_journal_processed_at')->andReturnSelf();
        $query->shouldReceive('where')->once()->with('detail.returned_journal_processed_at', '>=', '2025-09-30')->andReturnSelf();
        $query->shouldReceive('where')->once()->with('detail.returned_journal_processed_at', '<', '2025-10-01')->andReturnSelf();
        $query->shouldReceive('where')->once()->with('company.company_id', '2')->andReturnSelf();
        $response = $controller->returnsCsv($this->request(['payment_month' => '2025-09', 'return_status' => 'processed', 'company_id' => '2']));
        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();
        self::assertStringStartsWith("\xEF\xBB\xBF日付,伝票番号,借方勘定科目", $csv);
        $lines = preg_split('/\r\n/', trim($csv));
        self::assertCount(3, $lines);
        $negative = str_getcsv($lines[1], ',', '"', '');
        $positive = str_getcsv($lines[2], ',', '"', '');
        self::assertSame(['2025/9/30', '1', '保険収入', 'さくら店舗', '返戻', '1427', '非課売上', '0', '医療未収入金', 'さくら店舗', '', '1427', '対象外', '0', '6月売上'], $negative);
        self::assertSame(['2025/9/30', '1', '医療未収入金', 'さくら店舗', '', '516.25', '対象外', '0', '保険収入', 'さくら店舗', '返戻', '516.25', '非課売上', '0', '6月売上'], $positive);
    }
}

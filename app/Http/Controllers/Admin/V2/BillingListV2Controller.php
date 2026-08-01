<?php

namespace App\Http\Controllers\Admin\V2;

use App\Http\Controllers\Controller;
use App\Services\Admin\V2\BillingListV2Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingListV2Controller extends Controller
{
    public function __construct(
        private readonly BillingListV2Service $service,
    ) {}

    public function index(Request $request): View
    {
        return view('admin_v2.work.billing_list.index', $this->service->pageData([
            'status' => $request->query('status', 'all'),
            'q' => $request->query('q', ''),
            'billing_source' => $request->query('billing_source', ''),
            'invoice_id' => $request->query('invoice_id', 0),
        ]));
    }

    public function print(Request $request, int $invoiceId): View
    {
        return view('admin_v2.work.billing_list.print', $this->service->printData($invoiceId));
    }

    public function storeInvoice(Request $request): RedirectResponse
    {
        $values = $this->validateInvoice($request);
        $invoiceId = $this->service->createInvoice($values);

        return redirect()
            ->route('admin.work.billing_list', ['invoice_id' => $invoiceId])
            ->with('status', '請求書を登録しました。');
    }

    public function updateInvoice(Request $request): RedirectResponse
    {
        $values = $this->validateInvoice($request);
        $invoiceId = (int) $request->input('invoice_id');
        $this->service->updateInvoice($invoiceId, $values);

        return redirect()
            ->route('admin.work.billing_list', ['invoice_id' => $invoiceId])
            ->with('status', '請求書を保存しました。');
    }

    public function deleteInvoice(Request $request): RedirectResponse
    {
        $values = $request->validate([
            'invoice_id' => ['required', 'integer'],
        ]);

        $this->service->deleteInvoice((int) $values['invoice_id']);

        return redirect()
            ->route('admin.work.billing_list')
            ->with('status', '請求書を削除しました。');
    }

    public function storeDetail(Request $request): RedirectResponse
    {
        $invoiceId = (int) $request->input('invoice_id');
        $values = $this->validateDetail($request);
        $this->service->createDetail($invoiceId, $values);

        return redirect()
            ->route('admin.work.billing_list', ['invoice_id' => $invoiceId])
            ->with('status', '明細を追加しました。');
    }

    public function updateDetail(Request $request): RedirectResponse
    {
        $invoiceId = (int) $request->input('invoice_id');
        $detailId = (int) $request->input('invoice_detail_id');
        $values = $this->validateDetail($request);
        $this->service->updateDetail($invoiceId, $detailId, $values);

        return redirect()
            ->route('admin.work.billing_list', ['invoice_id' => $invoiceId])
            ->with('status', '明細を保存しました。');
    }

    public function deleteDetail(Request $request): RedirectResponse
    {
        $values = $request->validate([
            'invoice_id' => ['required', 'integer'],
            'invoice_detail_id' => ['required', 'integer'],
        ]);

        $invoiceId = (int) $values['invoice_id'];
        $this->service->deleteDetail($invoiceId, (int) $values['invoice_detail_id']);

        return redirect()
            ->route('admin.work.billing_list', ['invoice_id' => $invoiceId])
            ->with('status', '明細を削除しました。');
    }

    /** @return array<string, mixed> */
    private function validateInvoice(Request $request): array
    {
        return $request->validate([
            'invoice_id' => ['nullable', 'integer'],
            'invoice_date' => ['nullable', 'date'],
            'recipient_name' => ['nullable', 'string', 'max:50'],
            'subject' => ['nullable', 'string', 'max:30'],
            'billing_source' => ['nullable', 'string', 'max:15'],
            'paid_amount' => ['nullable', 'numeric'],
            'remarks' => ['nullable', 'string'],
            'accounting_remarks' => ['nullable', 'string'],
        ]);
    }

    /** @return array<string, mixed> */
    private function validateDetail(Request $request): array
    {
        return $request->validate([
            'invoice_id' => ['required', 'integer'],
            'invoice_detail_id' => ['nullable', 'integer'],
            'item_name' => ['nullable', 'string', 'max:50'],
            'category' => ['nullable', 'string', 'max:30'],
            'quantity' => ['nullable', 'integer'],
            'unit' => ['nullable', 'string', 'max:10'],
            'unit_price' => ['nullable', 'string', 'max:20'],
            'total_amount' => ['nullable', 'string', 'max:20'],
            'tax_category' => ['nullable', 'string', 'max:50'],
            'store_name' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string', 'max:100'],
        ]);
    }
}

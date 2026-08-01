<?php

namespace App\Services\Admin\V2;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BillingListV2Service
{
    /** @return array<string, mixed> */
    public function pageData(array $filters): array
    {
        $companyOptions = $this->companyOptions();
        $companyMap = $this->companyLookupMap($companyOptions);

        $status = (string) ($filters['status'] ?? 'all');
        $keyword = trim((string) ($filters['q'] ?? ''));
        $billingSource = trim((string) ($filters['billing_source'] ?? ''));
        $selectedInvoiceId = (int) ($filters['invoice_id'] ?? 0);

        $query = DB::connection('sqlsrv')
            ->table('dbo.mx_invoices')
            ->select([
                'invoice_id',
                'invoice_date',
                'recipient_name',
                'subject',
                'billing_source',
                'total_amount',
                'paid_amount',
                'remarks',
                'accounting_remarks',
            ])
            ->when($keyword !== '', function ($q) use ($keyword): void {
                $q->where(function ($sub) use ($keyword): void {
                    $sub->where('recipient_name', 'like', '%' . $keyword . '%')
                        ->orWhere('subject', 'like', '%' . $keyword . '%')
                        ->orWhere('remarks', 'like', '%' . $keyword . '%')
                        ->orWhere('accounting_remarks', 'like', '%' . $keyword . '%');
                });
            })
            ->when($status === 'paid', function ($q): void {
                $q->whereRaw('COALESCE(total_amount, 0) - COALESCE(paid_amount, 0) <= 0');
            })
            ->when($status === 'unpaid', function ($q): void {
                $q->whereRaw('COALESCE(total_amount, 0) - COALESCE(paid_amount, 0) > 0');
            })
            ->when($billingSource !== '', function ($q) use ($billingSource): void {
                $q->whereRaw('LTRIM(RTRIM(CAST(billing_source AS nvarchar(50)))) = ?', [$billingSource]);
            })
            ->orderByDesc('invoice_date')
            ->orderByDesc('invoice_id');

        $invoices = $query->get()
            ->map(fn($row): array => $this->formatInvoiceRow($row, $companyMap))
            ->all();

        if ($selectedInvoiceId === 0 && $invoices !== []) {
            $selectedInvoiceId = (int) ($invoices[0]['invoice_id'] ?? 0);
        }

        $selectedInvoice = $selectedInvoiceId > 0
            ? $this->findInvoice($selectedInvoiceId, $companyMap)
            : null;

        return [
            'filters' => [
                'status' => in_array($status, ['all', 'paid', 'unpaid'], true) ? $status : 'all',
                'q' => $keyword,
                'billing_source' => $billingSource,
                'invoice_id' => $selectedInvoiceId,
            ],
            'companyOptions' => $companyOptions,
            'storeOptions' => $this->storeOptions(),
            'invoices' => $invoices,
            'selectedInvoice' => $selectedInvoice,
            'details' => $selectedInvoiceId > 0 ? $this->details($selectedInvoiceId) : [],
            'taxCategories' => ['10%', '8%', '税込', '非課税'],
        ];
    }

    /** @return array<string, mixed> */
    public function printData(int $invoiceId): array
    {
        $companyOptions = $this->companyOptions();
        $companyMap = $this->companyLookupMap($companyOptions);
        $invoice = $this->findInvoice($invoiceId, $companyMap);
        $details = $invoiceId > 0 ? $this->details($invoiceId) : [];

        return [
            'invoice' => $invoice,
            'details' => $details,
            'totals' => $this->calculateTotals($details),
        ];
    }

    /** @return list<array<string, string>> */
    public function companyOptions(): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_companies')
            ->select(['company_id', 'company_name', 'bank_name', 'bank_account', 'postal_code', 'company_address', 'tel', 'fax', 'seal_image_path'])
            ->whereNotNull('company_id')
            ->orderBy('company_id')
            ->get()
            ->map(fn($row): array => [
                'company_id' => trim((string) ($row->company_id ?? '')),
                'company_name' => trim((string) ($row->company_name ?? '')),
                'bank_name' => trim((string) ($row->bank_name ?? '')),
                'bank_account' => trim((string) ($row->bank_account ?? '')),
                'postal_code' => trim((string) ($row->postal_code ?? '')),
                'company_address' => trim((string) ($row->company_address ?? '')),
                'tel' => trim((string) ($row->tel ?? '')),
                'fax' => trim((string) ($row->fax ?? '')),
                'seal_image_path' => trim((string) ($row->seal_image_path ?? '')),
            ])
            ->filter(fn(array $row): bool => $row['company_id'] !== '')
            ->values()
            ->all();
    }

    /** @return list<array{value:string,label:string}> */
    private function storeOptions(): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_stores')
            ->select(['store_name'])
            ->where(function ($query): void {
                $query->where('is_closed', false)
                    ->orWhere('is_closed', 0)
                    ->orWhereNull('is_closed');
            })
            ->whereNotNull('store_name')
            ->where('store_name', '<>', '')
            ->orderBy('store_code')
            ->get()
            ->map(fn($row): array => [
                'value' => trim((string) ($row->store_name ?? '')),
                'label' => trim((string) ($row->store_name ?? '')),
            ])
            ->filter(fn(array $row): bool => $row['value'] !== '')
            ->values()
            ->all();
    }

    /** @param list<array<string, string>> $companyOptions */
    private function companyLookupMap(array $companyOptions)
    {
        $map = collect();

        foreach ($companyOptions as $company) {
            foreach (['company_id', 'company_name'] as $key) {
                $value = trim((string) ($company[$key] ?? ''));
                if ($value !== '') {
                    $map->put($value, $company);
                }
            }
        }

        return $map;
    }

    public function createInvoice(array $values): int
    {
        $id = DB::connection('sqlsrv')->table('dbo.mx_invoices')->insertGetId([
            'invoice_date' => $this->dateOrNull($values['invoice_date'] ?? null),
            'recipient_name' => $this->nullableText($values['recipient_name'] ?? null),
            'subject' => $this->nullableText($values['subject'] ?? null),
            'billing_source' => $this->nullableText($values['billing_source'] ?? null),
            'total_amount' => 0,
            'paid_amount' => $this->moneyOrZero($values['paid_amount'] ?? null),
            'remarks' => $this->nullableText($values['remarks'] ?? null),
            'accounting_remarks' => $this->nullableText($values['accounting_remarks'] ?? null),
        ], 'invoice_id');

        return (int) $id;
    }

    public function updateInvoice(int $invoiceId, array $values): void
    {
        DB::connection('sqlsrv')
            ->table('dbo.mx_invoices')
            ->where('invoice_id', $invoiceId)
            ->update([
                'invoice_date' => $this->dateOrNull($values['invoice_date'] ?? null),
                'recipient_name' => $this->nullableText($values['recipient_name'] ?? null),
                'subject' => $this->nullableText($values['subject'] ?? null),
                'billing_source' => $this->nullableText($values['billing_source'] ?? null),
                'paid_amount' => $this->moneyOrZero($values['paid_amount'] ?? null),
                'remarks' => $this->nullableText($values['remarks'] ?? null),
                'accounting_remarks' => $this->nullableText($values['accounting_remarks'] ?? null),
            ]);
    }

    public function deleteInvoice(int $invoiceId): void
    {
        DB::connection('sqlsrv')->transaction(function () use ($invoiceId): void {
            DB::connection('sqlsrv')->table('dbo.mx_invoice_details')->where('invoice_id', $invoiceId)->delete();
            DB::connection('sqlsrv')->table('dbo.mx_invoices')->where('invoice_id', $invoiceId)->delete();
        });
    }

    public function createDetail(int $invoiceId, array $values): void
    {
        DB::connection('sqlsrv')->table('dbo.mx_invoice_details')->insert([
            'invoice_id' => $invoiceId,
            'item_name' => $this->nullableText($values['item_name'] ?? null),
            'category' => $this->nullableText($values['category'] ?? null),
            'quantity' => $this->intOrNull($values['quantity'] ?? null),
            'unit' => $this->nullableText($values['unit'] ?? null),
            'unit_price' => $this->intOrNull($values['unit_price'] ?? null),
            'total_amount' => $this->detailTotal($values),
            'tax_category' => $this->nullableText($values['tax_category'] ?? null),
            'store_name' => $this->nullableText($values['store_name'] ?? null),
            'remarks' => $this->nullableText($values['remarks'] ?? null),
        ]);

        $this->refreshInvoiceTotal($invoiceId);
    }

    public function updateDetail(int $invoiceId, int $detailId, array $values): void
    {
        DB::connection('sqlsrv')
            ->table('dbo.mx_invoice_details')
            ->where('invoice_id', $invoiceId)
            ->where('invoice_detail_id', $detailId)
            ->update([
                'item_name' => $this->nullableText($values['item_name'] ?? null),
                'category' => $this->nullableText($values['category'] ?? null),
                'quantity' => $this->intOrNull($values['quantity'] ?? null),
                'unit' => $this->nullableText($values['unit'] ?? null),
                'unit_price' => $this->intOrNull($values['unit_price'] ?? null),
                'total_amount' => $this->detailTotal($values),
                'tax_category' => $this->nullableText($values['tax_category'] ?? null),
                'store_name' => $this->nullableText($values['store_name'] ?? null),
                'remarks' => $this->nullableText($values['remarks'] ?? null),
            ]);

        $this->refreshInvoiceTotal($invoiceId);
    }

    public function deleteDetail(int $invoiceId, int $detailId): void
    {
        DB::connection('sqlsrv')
            ->table('dbo.mx_invoice_details')
            ->where('invoice_id', $invoiceId)
            ->where('invoice_detail_id', $detailId)
            ->delete();

        $this->refreshInvoiceTotal($invoiceId);
    }

    /** @return array<string, mixed>|null */
    private function findInvoice(int $invoiceId, $companyMap): ?array
    {
        $row = DB::connection('sqlsrv')
            ->table('dbo.mx_invoices')
            ->where('invoice_id', $invoiceId)
            ->first([
                'invoice_id',
                'invoice_date',
                'recipient_name',
                'subject',
                'billing_source',
                'total_amount',
                'paid_amount',
                'remarks',
                'accounting_remarks',
            ]);

        return $row ? $this->formatInvoiceRow($row, $companyMap) : null;
    }

    /** @return list<array<string, mixed>> */
    private function details(int $invoiceId): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_invoice_details')
            ->where('invoice_id', $invoiceId)
            ->orderBy('invoice_detail_id')
            ->get()
            ->map(fn($row): array => [
                'invoice_detail_id' => (int) ($row->invoice_detail_id ?? 0),
                'invoice_id' => (int) ($row->invoice_id ?? 0),
                'item_name' => trim((string) ($row->item_name ?? '')),
                'category' => trim((string) ($row->category ?? '')),
                'quantity' => $this->formatNumber($row->quantity ?? null),
                'unit' => trim((string) ($row->unit ?? '')),
                'unit_price_value' => (float) ($row->unit_price ?? 0),
                'unit_price' => $this->formatNumber($row->unit_price ?? null),
                'total_amount_value' => (float) ($row->total_amount ?? 0),
                'total_amount' => $this->formatNumber($row->total_amount ?? null),
                'tax_category' => trim((string) ($row->tax_category ?? '')),
                'store_name' => trim((string) ($row->store_name ?? '')),
                'remarks' => trim((string) ($row->remarks ?? '')),
            ])
            ->all();
    }

    private function refreshInvoiceTotal(int $invoiceId): void
    {
        $totals = $this->calculateTotals($this->details($invoiceId));

        DB::connection('sqlsrv')
            ->table('dbo.mx_invoices')
            ->where('invoice_id', $invoiceId)
            ->update(['total_amount' => $totals['grand_total_value']]);
    }

    private function formatInvoiceRow(object $row, $companyMap): array
    {
        $billingSource = trim((string) ($row->billing_source ?? ''));
        $company = $companyMap->get($billingSource);
        $total = (float) ($row->total_amount ?? 0);
        $paid = (float) ($row->paid_amount ?? 0);

        return [
            'invoice_id' => (int) ($row->invoice_id ?? 0),
            'invoice_date' => $this->formatDate($row->invoice_date ?? null, 'Y-m-d'),
            'invoice_date_label' => $this->formatDate($row->invoice_date ?? null, 'Y/m/d'),
            'recipient_name' => trim((string) ($row->recipient_name ?? '')),
            'subject' => trim((string) ($row->subject ?? '')),
            'billing_source' => $billingSource,
            'billing_source_label' => $company['company_name'] ?? $billingSource,
            'billing_bank_name' => $company['bank_name'] ?? '',
            'billing_bank_account' => $company['bank_account'] ?? '',
            'billing_company_name' => $company['company_name'] ?? $billingSource,
            'billing_postal_code' => $company['postal_code'] ?? '',
            'billing_company_address' => $company['company_address'] ?? '',
            'billing_tel' => $company['tel'] ?? '',
            'billing_fax' => $company['fax'] ?? '',
            'billing_seal_image_path' => $company['seal_image_path'] ?? '',
            'total_amount' => $this->formatNumber($total),
            'paid_amount' => $this->formatNumber($paid),
            'remaining_amount' => $this->formatNumber($total - $paid),
            'remarks' => trim((string) ($row->remarks ?? '')),
            'accounting_remarks' => trim((string) ($row->accounting_remarks ?? '')),
        ];
    }

    private function detailTotal(array $values): float
    {
        $total = $this->moneyOrNull($values['total_amount'] ?? null);
        if ($total !== null) {
            return $total;
        }

        $quantity = $this->intOrNull($values['quantity'] ?? null);
        $unitPrice = $this->intOrNull($values['unit_price'] ?? null);

        return (float) (($quantity ?? 0) * ($unitPrice ?? 0));
    }

    /** @param list<array<string, mixed>> $details */
    private function calculateTotals(array $details): array
    {
        $subtotal = 0.0;
        $tax = 0.0;

        foreach ($details as $detail) {
            $amount = (float) ($detail['total_amount_value'] ?? 0);
            $subtotal += $amount;

            $taxCategory = trim((string) ($detail['tax_category'] ?? ''));
            if ($taxCategory === '10%') {
                $tax += floor($amount * 0.1);
            } elseif ($taxCategory === '8%') {
                $tax += floor($amount * 0.08);
            }
        }

        $grandTotal = $subtotal + $tax;

        return [
            'subtotal_value' => $subtotal,
            'tax_value' => $tax,
            'grand_total_value' => $grandTotal,
            'subtotal' => $this->formatNumber($subtotal),
            'tax' => $this->formatNumber($tax),
            'grand_total' => $this->formatNumber($grandTotal),
        ];
    }

    private function nullableText(mixed $value): ?string
    {
        $text = trim((string) $value);
        return $text === '' ? null : $text;
    }

    private function dateOrNull(mixed $value): ?string
    {
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        try {
            return Carbon::parse($text)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function intOrNull(mixed $value): ?int
    {
        $text = str_replace(',', '', trim((string) $value));
        return $text === '' ? null : (int) $text;
    }

    private function moneyOrNull(mixed $value): ?float
    {
        $text = str_replace(',', '', trim((string) $value));
        return $text === '' ? null : (float) $text;
    }

    private function moneyOrZero(mixed $value): float
    {
        return $this->moneyOrNull($value) ?? 0.0;
    }

    private function formatDate(mixed $value, string $format): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->format($format);
        } catch (\Throwable) {
            return '';
        }
    }

    private function formatNumber(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format((float) $value, 0);
    }
}

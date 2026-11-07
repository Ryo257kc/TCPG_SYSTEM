<?php

namespace App\Services\Shared;

use Illuminate\Support\Facades\DB;

/** レセ入力・入金確認・請求一覧で共用する、営業中のレセ対象店舗候補。 */
class ReceiptStoreOptionsService
{
    /** @return list<string> */
    public function names(): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_departments')
            ->join('dbo.mx_stores', 'dbo.mx_stores.store_code', '=', 'dbo.mx_departments.official_store_no')
            ->select(['store_category'])
            ->whereIn('dbo.mx_stores.business_type', ['（往診）', '（店舗）'])
            ->where(function ($query): void {
                $query->where('has_receipt', true)
                    ->orWhere('has_receipt', 1);
            })
            ->whereNull('closed_on')
            ->whereNotNull('store_category')
            ->orderBy('store_category')
            ->get()
            ->map(fn($row): string => trim((string) ($row->store_category ?? '')))
            ->filter(fn(string $name): bool => $name !== '')
            ->unique()
            ->values()
            ->all();
    }
}

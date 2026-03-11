<?php

namespace App\Services\Admin\V2\Master;

use Illuminate\Support\Facades\DB;

class StoreV2Service
{
    public function list(string $keyword): array
    {
        $query = DB::connection('sqlsrv')->table('dbo.mx_stores')->select([
            'store_code','store_name','company_id','company_name','store_short_name','phone','is_closed',DB::raw('[No] as legacy_no'),
        ])->orderBy('store_code');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword): void {
                $q->where('store_code', 'like', '%' . $keyword . '%')
                    ->orWhere('store_name', 'like', '%' . $keyword . '%')
                    ->orWhere('company_name', 'like', '%' . $keyword . '%');
            });
        }

        $rows = $query->get()->map(fn ($r) => [
            'store_code' => (string) ($r->store_code ?? ''),
            'store_name' => (string) ($r->store_name ?? ''),
            'company_id' => trim((string) ($r->company_id ?? '')),
            'company_name' => (string) ($r->company_name ?? ''),
            'store_short_name' => (string) ($r->store_short_name ?? ''),
            'phone' => (string) ($r->phone ?? ''),
            'is_closed' => (int) ($r->is_closed ?? 0),
            'legacy_no' => (string) ($r->legacy_no ?? ''),
        ])->all();

        $companyOptions = DB::connection('sqlsrv')->table('dbo.mx_companies')
            ->select(['company_id','company_name'])
            ->whereNotNull('company_id')->whereNotNull('company_name')
            ->whereRaw("LTRIM(RTRIM(company_name)) <> ''")
            ->orderBy('company_id')->get()
            ->map(fn ($r) => ['company_id' => trim((string) ($r->company_id ?? '')), 'company_name' => trim((string) ($r->company_name ?? ''))])
            ->filter(fn ($r) => $r['company_id'] !== '' && $r['company_name'] !== '')
            ->values()->all();

        return ['rows' => $rows, 'companyOptions' => $companyOptions];
    }

    public function update(array $v): int
    {
        $companyId = trim((string) ($v['company_id'] ?? ''));
        $companyName = '';
        if ($companyId !== '') {
            $companyName = trim((string) (DB::connection('sqlsrv')->table('dbo.mx_companies')
                ->whereRaw('LTRIM(RTRIM(CAST(company_id AS nvarchar(255)))) = ?', [$companyId])
                ->value('company_name') ?? ''));
        }

        return DB::connection('sqlsrv')->table('dbo.mx_stores')
            ->whereRaw('LTRIM(RTRIM(store_code)) = ?', [trim((string) ($v['store_code'] ?? ''))])
            ->update([
                'store_name' => trim((string) ($v['store_name'] ?? '')),
                'company_id' => $companyId === '' ? null : $companyId,
                'company_name' => $companyName,
                'store_short_name' => trim((string) ($v['store_short_name'] ?? '')),
                'phone' => trim((string) ($v['phone'] ?? '')),
                'is_closed' => ((string) ($v['is_closed'] ?? '0')) === '1' ? 1 : 0,
            ]);
    }
}
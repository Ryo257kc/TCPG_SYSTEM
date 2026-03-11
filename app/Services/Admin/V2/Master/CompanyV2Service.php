<?php

namespace App\Services\Admin\V2\Master;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompanyV2Service
{
    public function hasColumn(string $column): bool
    {
        try {
            return Schema::connection('sqlsrv')->hasColumn('mx_companies', $column)
                || Schema::connection('sqlsrv')->hasColumn('dbo.mx_companies', $column);
        } catch (\Throwable) {
            return false;
        }
    }

    public function list(string $keyword): array
    {
        $selects = ['company_id', 'company_name'];
        foreach (['legacy_store_no', 'company_code', 'company_name_kana', 'company_address', 'office_number', 'phone', 'fax'] as $col) {
            if ($this->hasColumn($col)) {
                $selects[] = $col;
            }
        }

        $query = DB::connection('sqlsrv')->table('dbo.mx_companies')->select($selects)->orderBy('company_id');
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword): void {
                $q->where('company_name', 'like', '%' . $keyword . '%')
                    ->orWhere('company_code', 'like', '%' . $keyword . '%')
                    ->orWhere('office_number', 'like', '%' . $keyword . '%');
            });
        }

        $rows = $query->get()->map(function ($r): array {
            return [
                'company_id' => (string) ($r->company_id ?? ''),
                'legacy_store_no' => (string) ($r->legacy_store_no ?? ''),
                'company_code' => (string) ($r->company_code ?? ''),
                'company_name' => (string) ($r->company_name ?? ''),
                'company_name_kana' => (string) ($r->company_name_kana ?? ''),
                'company_address' => (string) ($r->company_address ?? ''),
                'office_number' => (string) ($r->office_number ?? ''),
                'phone' => (string) ($r->phone ?? ''),
                'fax' => (string) ($r->fax ?? ''),
            ];
        })->all();

        return ['rows' => $rows];
    }

    public function update(array $v): void
    {
        $payload = ['company_name' => trim((string) ($v['company_name'] ?? ''))];
        foreach (['company_code', 'company_name_kana', 'company_address', 'office_number', 'phone', 'fax'] as $col) {
            if ($this->hasColumn($col)) {
                $payload[$col] = trim((string) ($v[$col] ?? ''));
            }
        }

        DB::connection('sqlsrv')->table('dbo.mx_companies')
            ->whereRaw('LTRIM(RTRIM(CAST(company_id AS nvarchar(255)))) = ?', [trim((string) ($v['company_id'] ?? ''))])
            ->update($payload);
    }
}
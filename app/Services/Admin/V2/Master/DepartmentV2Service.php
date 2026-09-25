<?php

namespace App\Services\Admin\V2\Master;

use Illuminate\Support\Facades\DB;

class DepartmentV2Service
{
    public function list(string $keyword): array
    {
        $query = DB::connection('sqlsrv')
            ->table('dbo.mx_departments as d')
            ->leftJoin('dbo.mx_stores as st', 'st.store_code', '=', 'd.official_store_no')
            ->leftJoin('dbo.mx_companies as c', 'c.company_id', '=', 'st.company_id')
            ->select([
                'd.department_no',
                'd.store_short_name',
                'd.store_category',
                'd.official_store_no',
                'd.receipt_store_no',
                'd.has_receipt',
                'd.bank_account_selection',
                'd.closed_on',
                'st.store_name',
                'c.company_name',
            ])
            ->orderBy('d.department_no');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword): void {
                $q->where('d.department_no', 'like', '%' . $keyword . '%')
                    ->orWhere('d.store_short_name', 'like', '%' . $keyword . '%')
                    ->orWhere('d.store_category', 'like', '%' . $keyword . '%')
                    ->orWhere('d.official_store_no', 'like', '%' . $keyword . '%');
            });
        }

        $rows = $query->get()->map(fn ($r) => [
            'department_no' => trim((string) ($r->department_no ?? '')),
            'store_short_name' => trim((string) ($r->store_short_name ?? '')),
            'store_category' => trim((string) ($r->store_category ?? '')),
            'official_store_no' => trim((string) ($r->official_store_no ?? '')),
            'receipt_store_no' => trim((string) ($r->receipt_store_no ?? '')),
            'has_receipt' => (int) ($r->has_receipt ?? 0),
            'bank_account_selection' => trim((string) ($r->bank_account_selection ?? '')),
            'closed_on' => $r->closed_on !== null ? substr((string) $r->closed_on, 0, 10) : '',
            'store_name' => trim((string) ($r->store_name ?? '')),
            'company_name' => trim((string) ($r->company_name ?? '')),
        ])->all();

        return ['rows' => $rows, 'source' => 'mx_departments'];
    }

    public function update(array $v): int
    {
        $departmentNo = trim((string) ($v['department_no'] ?? ''));

        $closedOn = trim((string) ($v['closed_on'] ?? ''));

        return DB::connection('sqlsrv')->table('dbo.mx_departments')
            ->whereRaw('LTRIM(RTRIM(department_no)) = ?', [$departmentNo])
            ->update([
                'store_category' => trim((string) ($v['store_category'] ?? '')),
                'official_store_no' => trim((string) ($v['official_store_no'] ?? '')),
                'receipt_store_no' => trim((string) ($v['receipt_store_no'] ?? '')),
                'has_receipt' => ((string) ($v['has_receipt'] ?? '0')) === '1' ? 1 : 0,
                'bank_account_selection' => trim((string) ($v['bank_account_selection'] ?? '')),
                'closed_on' => $closedOn === '' ? null : $closedOn,
            ]);
    }
}

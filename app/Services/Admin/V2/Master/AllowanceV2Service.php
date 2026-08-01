<?php

namespace App\Services\Admin\V2\Master;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AllowanceV2Service
{
    private function hasPayrollColumn(string $column): bool
    {
        try {
            return Schema::connection('sqlsrv_payroll')->hasColumn('mx_allowance', $column)
                || Schema::connection('sqlsrv_payroll')->hasColumn('dbo.mx_allowance', $column);
        } catch (\Throwable) {
            return false;
        }
    }

    public function list(string $officeName): array
    {
        $companyOptions = DB::connection('sqlsrv')->table('dbo.mx_companies')
            ->select('company_id', 'company_name')
            ->whereNotNull('company_id')
            ->whereRaw("LTRIM(RTRIM(company_id)) <> ''")
            ->orderBy('company_id')
            ->get()
            ->map(fn($r) => [
                'company_id' => (string) ($r->company_id ?? ''),
                'company_name' => (string) ($r->company_name ?? ''),
            ])
            ->values()
            ->all();

        $query = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_allowance')
            ->orderBy('office_name')
            ->orderBy('display_order');

        if ($officeName !== '') {
            $query->where('office_name', $officeName);
        }

        $rows = $query->get()->map(fn($r) => [
            'allowance_no' => (int) ($r->allowance_no ?? 0),
            'allowance_name' => (string) ($r->allowance_name ?? ''),
            'office_name' => (string) ($r->office_name ?? ''),
            'rou_target' => (int) ($r->rou_target ?? 0),
            'syaho_target' => (int) ($r->syaho_target ?? 0),
            'tax_target' => (int) ($r->tax_target ?? 0),
            'kotei_wage' => (int) ($r->kotei_wage ?? 0),
            'warimasi_kiso' => (int) ($r->warimasi_kiso ?? 0),
            'koujyo_kiso' => (int) ($r->koujyo_kiso ?? 0),
            'display_order' => (int) ($r->display_order ?? 0),
            'amount_column_key' => (string) ($r->amount_column_key ?? ''),
        ])->all();

        return [
            'rows' => $rows,
            'companyOptions' => $companyOptions,
            'source' => 'mx_allowance',
        ];
    }

    public function update(array $v): string
    {
        $allowanceNo = (int) ($v['allowance_no'] ?? 0);
        $payload = [
            'allowance_name' => trim((string) ($v['allowance_name'] ?? '')),
            'rou_target' => ((string) ($v['rou_target'] ?? '0')) === '1' ? 1 : 0,
            'syaho_target' => ((string) ($v['syaho_target'] ?? '0')) === '1' ? 1 : 0,
            'tax_target' => ((string) ($v['tax_target'] ?? '0')) === '1' ? 1 : 0,
            'kotei_wage' => ((string) ($v['kotei_wage'] ?? '0')) === '1' ? 1 : 0,
            'warimasi_kiso' => ((string) ($v['warimasi_kiso'] ?? '0')) === '1' ? 1 : 0,
            'koujyo_kiso' => ((string) ($v['koujyo_kiso'] ?? '0')) === '1' ? 1 : 0,
        ];

        if ($this->hasPayrollColumn('display_order')) {
            $payload['display_order'] = (int) ($v['display_order'] ?? 0);
        }
        if ($this->hasPayrollColumn('amount_column_key')) {
            $payload['amount_column_key'] = trim((string) ($v['amount_column_key'] ?? ''));
        }

        DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_allowance')
            ->where('allowance_no', $allowanceNo)
            ->update($payload);

        return '更新しました。';
    }
}

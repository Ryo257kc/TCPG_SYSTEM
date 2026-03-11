<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;

class PayrollV2CompanyService
{
    /** @return list<string> */
    public function companies(): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_stores')
            ->whereNotNull('company_name')
            ->whereRaw('LTRIM(RTRIM(company_name)) <> ?', [''])
            ->distinct()
            ->orderBy('company_name')
            ->pluck('company_name')
            ->map(fn ($v) => (string) $v)
            ->values()
            ->all();
    }
}

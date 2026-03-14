<?php

namespace App\Services\Admin\V2\Attendance;

use Illuminate\Support\Facades\DB;

class AttendanceV2CompanyService
{
    /** @return list<string> */
    public function companies(): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_companies')
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

<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;

class PayrollV2StaffService
{
    /**
     * @return list<array{staff_id:string,staff_name:string,division:string,store_code:string,store_name:string,company_name:string}>
     */
    public function staffs(string $company): array
    {
        $query = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs as s')
            ->leftJoin('dbo.mx_stores as st', 'st.store_code', '=', 's.section')
            ->leftJoin('dbo.mx_companies as c', 'c.company_id', '=', 'st.company_id')
            ->select([
                's.staff_id',
                's.staff_name',
                's.staff_division',
                'st.store_code',
                'st.store_name',
                'c.company_name',
            ])
            ->whereNotNull('s.staff_id')
            ->whereRaw('LTRIM(RTRIM(s.staff_id)) <> ?', ['']);

        if ($company !== '') {
            $query->where('c.company_name', $company);
        }

        return $query
            ->orderBy('s.staff_id')
            ->get()
            ->map(fn ($r) => [
                'staff_id' => trim((string) ($r->staff_id ?? '')),
                'staff_name' => trim((string) ($r->staff_name ?? '')),
                'division' => trim((string) ($r->staff_division ?? '')),
                'store_code' => trim((string) ($r->store_code ?? '')),
                'store_name' => trim((string) ($r->store_name ?? '')),
                'company_name' => trim((string) ($r->company_name ?? '')),
            ])
            ->filter(fn ($row) => $row['staff_id'] !== '')
            ->values()
            ->all();
    }
}

<?php

namespace App\Services\Admin\V2\Attendance;

use Illuminate\Support\Facades\DB;

class AttendanceV2StaffService
{
    /**
     * @return list<array{staff_id:string,staff_name:string,division:string,store_name:string,company_name:string}>
     */
    public function staffs(string $fromDate, string $toDate, string $company): array
    {
        $query = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs as s')
            ->leftJoin('dbo.mx_stores as st', 'st.store_code', '=', 's.section')
            ->leftJoin('dbo.mx_companies as c', 'c.company_id', '=', 'st.company_id')
            ->select([
                's.staff_id',
                's.staff_name',
                's.staff_division',
                'st.store_name',
                'c.company_name',
                's.nyu_date',
                's.tai_date',
            ])
            ->whereNotNull('s.staff_id')
            ->whereRaw('LTRIM(RTRIM(s.staff_id)) <> ?', [''])
            ->where(function ($q) use ($toDate) {
                $q->whereNull('s.nyu_date')->orWhereRaw('CONVERT(date, s.nyu_date) <= ?', [$toDate]);
            })
            ->where(function ($q) use ($fromDate) {
                $q->whereNull('s.tai_date')->orWhereRaw('CONVERT(date, s.tai_date) >= ?', [$fromDate]);
            });

        if ($company !== '') {
            $query->where('c.company_name', $company);
        }

        return $query
            ->orderBy('s.staff_id')
            ->get()
            ->map(function ($r): array {
                return [
                    'staff_id' => trim((string) ($r->staff_id ?? '')),
                    'staff_name' => trim((string) ($r->staff_name ?? '')),
                    'division' => trim((string) ($r->staff_division ?? '')),
                    'store_name' => trim((string) ($r->store_name ?? '')),
                    'company_name' => trim((string) ($r->company_name ?? '')),
                ];
            })
            ->filter(fn ($row) => $row['staff_id'] !== '')
            ->values()
            ->all();
    }
}

<?php

namespace App\Services\Admin\V2\Master;

use Illuminate\Support\Facades\DB;

class CompanyV2MayorService
{
    public function listByCompanyId(string $companyId): array
    {
        $rows = DB::connection('sqlsrv_payroll')
            ->table('mx_mayor')
            ->select([
                'mayor_no',
                'mayor',
                'specified_num',
                'city_cord',
                'city_hall_post',
                'city_hall_add',
                'city_hall',
                'city_hall_department',
                'city_hall_tel',
                'office_no',
                'tax_office',
            ])
            ->whereRaw('LTRIM(RTRIM(CAST(office_no AS nvarchar(50)))) = ?', [trim($companyId)])
            ->orderByDesc('mayor_no')
            ->get()
            ->map(function ($row): array {
                return [
                    'mayor_no' => (string) ($row->mayor_no ?? ''),
                    'mayor' => (string) ($row->mayor ?? ''),
                    'specified_num' => (string) ($row->specified_num ?? ''),
                    'city_cord' => (string) ($row->city_cord ?? ''),
                    'city_hall_post' => (string) ($row->city_hall_post ?? ''),
                    'city_hall_add' => (string) ($row->city_hall_add ?? ''),
                    'city_hall' => (string) ($row->city_hall ?? ''),
                    'city_hall_department' => (string) ($row->city_hall_department ?? ''),
                    'city_hall_tel' => (string) ($row->city_hall_tel ?? ''),
                    'office_no' => (string) ($row->office_no ?? ''),
                    'tax_office' => (string) ($row->tax_office ?? ''),
                ];
            })
            ->all();

        return [
            'latest' => $rows[0] ?? null,
            'rows' => $rows,
        ];
    }

    public function create(array $values): void
    {
        DB::connection('sqlsrv_payroll')->table('mx_mayor')->insert($this->payload($values));
    }

    public function update(array $values): void
    {
        DB::connection('sqlsrv_payroll')
            ->table('mx_mayor')
            ->where('mayor_no', (int) ($values['mayor_no'] ?? 0))
            ->whereRaw('LTRIM(RTRIM(CAST(office_no AS nvarchar(50)))) = ?', [trim((string) ($values['company_id'] ?? ''))])
            ->update($this->payload($values));
    }

    public function delete(string $companyId, string $mayorNo): void
    {
        DB::connection('sqlsrv_payroll')
            ->table('mx_mayor')
            ->where('mayor_no', (int) $mayorNo)
            ->whereRaw('LTRIM(RTRIM(CAST(office_no AS nvarchar(50)))) = ?', [trim($companyId)])
            ->delete();
    }

    private function payload(array $values): array
    {
        return [
            'mayor' => trim((string) ($values['mayor'] ?? '')),
            'specified_num' => trim((string) ($values['specified_num'] ?? '')),
            'city_cord' => trim((string) ($values['city_cord'] ?? '')),
            'city_hall_post' => trim((string) ($values['city_hall_post'] ?? '')),
            'city_hall_add' => trim((string) ($values['city_hall_add'] ?? '')),
            'city_hall' => trim((string) ($values['city_hall'] ?? '')),
            'city_hall_department' => trim((string) ($values['city_hall_department'] ?? '')),
            'city_hall_tel' => trim((string) ($values['city_hall_tel'] ?? '')),
            'office_no' => trim((string) ($values['company_id'] ?? '')),
            'tax_office' => trim((string) ($values['tax_office'] ?? '')),
        ];
    }
}

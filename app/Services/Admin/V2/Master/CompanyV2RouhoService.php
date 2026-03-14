<?php

namespace App\Services\Admin\V2\Master;

use Illuminate\Support\Facades\DB;

class CompanyV2RouhoService
{
    /** @return array{latest:?array<string,string>,rows:list<array<string,string>>} */
    public function listByCompanyId(string $companyId): array
    {
        $rows = DB::connection('sqlsrv_payroll')
            ->table('mx_rouho')
            ->select([
                'rou_no',
                'office_no',
                'rou_industry',
                'aggregate_period',
                'rou_apply_date',
                'general_st',
                'general_of',
                'nourin_st',
                'nourin_of',
                'kensetu_st',
                'kensetu_of',
                'rousai',
            ])
            ->whereRaw('LTRIM(RTRIM(CAST(office_no AS nvarchar(50)))) = ?', [trim($companyId)])
            ->orderByDesc('rou_apply_date')
            ->orderByDesc('rou_no')
            ->get()
            ->map(function ($row): array {
                return [
                    'rou_no' => (string) ($row->rou_no ?? ''),
                    'office_no' => (string) ($row->office_no ?? ''),
                    'rou_industry' => trim((string) ($row->rou_industry ?? '')),
                    'aggregate_period' => trim((string) ($row->aggregate_period ?? '')),
                    'rou_apply_date' => $this->normalizeDate($row->rou_apply_date ?? null),
                    'general_st' => trim((string) ($row->general_st ?? '')),
                    'general_of' => trim((string) ($row->general_of ?? '')),
                    'nourin_st' => trim((string) ($row->nourin_st ?? '')),
                    'nourin_of' => trim((string) ($row->nourin_of ?? '')),
                    'kensetu_st' => trim((string) ($row->kensetu_st ?? '')),
                    'kensetu_of' => trim((string) ($row->kensetu_of ?? '')),
                    'rousai' => trim((string) ($row->rousai ?? '')),
                ];
            })
            ->values()
            ->all();

        return [
            'latest' => $rows[0] ?? null,
            'rows' => $rows,
        ];
    }

    public function create(array $values): void
    {
        DB::connection('sqlsrv_payroll')->table('mx_rouho')->insert([
            'office_no' => trim((string) ($values['company_id'] ?? '')),
            'rou_industry' => $this->nullable($values['rou_industry'] ?? null),
            'aggregate_period' => $this->nullable($values['aggregate_period'] ?? null),
            'rou_apply_date' => $this->nullable($values['rou_apply_date'] ?? null),
            'general_st' => $this->nullable($values['general_st'] ?? null),
            'general_of' => $this->nullable($values['general_of'] ?? null),
            'nourin_st' => $this->nullable($values['nourin_st'] ?? null),
            'nourin_of' => $this->nullable($values['nourin_of'] ?? null),
            'kensetu_st' => $this->nullable($values['kensetu_st'] ?? null),
            'kensetu_of' => $this->nullable($values['kensetu_of'] ?? null),
            'rousai' => $this->nullable($values['rousai'] ?? null),
        ]);
    }

    public function update(array $values): void
    {
        DB::connection('sqlsrv_payroll')
            ->table('mx_rouho')
            ->where('rou_no', $values['rou_no'])
            ->whereRaw('LTRIM(RTRIM(CAST(office_no AS nvarchar(50)))) = ?', [trim((string) ($values['company_id'] ?? ''))])
            ->update([
                'rou_industry' => $this->nullable($values['rou_industry'] ?? null),
                'aggregate_period' => $this->nullable($values['aggregate_period'] ?? null),
                'rou_apply_date' => $this->nullable($values['rou_apply_date'] ?? null),
                'general_st' => $this->nullable($values['general_st'] ?? null),
                'general_of' => $this->nullable($values['general_of'] ?? null),
                'nourin_st' => $this->nullable($values['nourin_st'] ?? null),
                'nourin_of' => $this->nullable($values['nourin_of'] ?? null),
                'kensetu_st' => $this->nullable($values['kensetu_st'] ?? null),
                'kensetu_of' => $this->nullable($values['kensetu_of'] ?? null),
                'rousai' => $this->nullable($values['rousai'] ?? null),
            ]);
    }

    public function delete(string $companyId, string $rouNo): void
    {
        DB::connection('sqlsrv_payroll')
            ->table('mx_rouho')
            ->where('rou_no', $rouNo)
            ->whereRaw('LTRIM(RTRIM(CAST(office_no AS nvarchar(50)))) = ?', [trim($companyId)])
            ->delete();
    }

    private function nullable(mixed $value): ?string
    {
        $trimmed = trim((string) $value);
        return $trimmed === '' ? null : $trimmed;
    }

    private function normalizeDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return substr((string) $value, 0, 10);
        } catch (\Throwable) {
            return '';
        }
    }
}

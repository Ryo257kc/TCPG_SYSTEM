<?php

namespace App\Services\Admin\V2\Master;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompanyV2Service
{
    /** @var array<string,bool> */
    private array $columnExistsCache = [];

    /** @return list<string> */
    private function companyInfoColumns(): array
    {
        return [
            'postal_code',
            'company_address',
            'established_date',
            'closing_day',
            'tel',
            'fax',
            'bank_name',
            'bank_account',
            'ceo_title',
            'ceo_name_kana',
            'ceo_name',
            'corporate_number',
        ];
    }

    /** @return list<string> */
    private function socialInsuranceColumns(): array
    {
        return [
            'health_office_code',
            'pension_office_code',
            'office_number',
            'insurer_number',
            'insurer_name',
            'insurer_address',
        ];
    }

    /** @return list<string> */
    private function laborInsuranceColumns(): array
    {
        return [
            'labor_insurance_number',
            'labor_install_category',
            'labor_install_date',
            'labor_office_category',
            'labor_industry_category',
            'employment_office_number',
        ];
    }

    /** @return list<string> */
    private function optionalColumns(): array
    {
        return [
            'postal_code',
            'company_address',
            'established_date',
            'closing_day',
            'office_number',
            'tel',
            'fax',
            'bank_name',
            'bank_account',
            'ceo_title',
            'ceo_name_kana',
            'ceo_name',
            'health_office_code',
            'pension_office_code',
            'corporate_number',
            'insurer_number',
            'insurer_name',
            'insurer_address',
            'employment_office_number',
            'labor_insurance_number',
            'labor_install_category',
            'labor_install_date',
            'labor_office_category',
            'labor_industry_category',
        ];
    }

    /** @return list<string> */
    private function columnsForTab(string $tab): array
    {
        return match ($tab) {
            'syaho' => $this->socialInsuranceColumns(),
            'rouho' => $this->laborInsuranceColumns(),
            default => $this->companyInfoColumns(),
        };
    }

    public function hasColumn(string $column): bool
    {
        if (array_key_exists($column, $this->columnExistsCache)) {
            return $this->columnExistsCache[$column];
        }

        $exists = Schema::connection('sqlsrv')->hasColumn('mx_companies', $column)
            || Schema::connection('sqlsrv')->hasColumn('dbo.mx_companies', $column);
        $this->columnExistsCache[$column] = $exists;

        return $exists;
    }

    public function list(string $keyword): array
    {
        $query = DB::connection('sqlsrv')->table('dbo.mx_companies')->select(['company_id', 'company_name'])->orderBy('company_id');
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword): void {
                $q->where('company_name', 'like', '%' . $keyword . '%');
            });
        }

        $rows = $query->get()->map(function ($r): array {
            return [
                'company_id' => (string) ($r->company_id ?? ''),
                'company_name' => (string) ($r->company_name ?? ''),
            ];
        })->all();

        return ['rows' => $rows];
    }

    public function detail(string $companyId): ?array
    {
        $selects = ['company_id', 'company_name'];
        $detailColumns = [];
        foreach ($this->optionalColumns() as $col) {
            if ($this->hasColumn($col)) {
                $detailColumns[] = $col;
                $selects[] = $col;
            }
        }

        $row = DB::connection('sqlsrv')
            ->table('dbo.mx_companies')
            ->select($selects)
            ->whereRaw('LTRIM(RTRIM(CAST(company_id AS nvarchar(255)))) = ?', [trim($companyId)])
            ->first();

        if ($row === null) {
            return null;
        }

        $detail = [
            'company_id' => (string) ($row->company_id ?? ''),
            'company_name' => (string) ($row->company_name ?? ''),
        ];

        foreach ($detailColumns as $col) {
            $detail[$col] = (string) ($row->{$col} ?? '');
        }

        $detail['phone'] = $detail['tel'] !== '' ? $detail['tel'] : '';

        return $detail;
    }

    public function update(array $v, string $tab = 'company'): void
    {
        $payload = ['company_name' => trim((string) ($v['company_name'] ?? ''))];

        if ($tab !== 'company') {
            unset($payload['company_name']);
        }

        foreach ($this->columnsForTab($tab) as $col) {
            if (!$this->hasColumn($col)) {
                continue;
            }

            if ($col === 'tel') {
                if (array_key_exists('phone', $v)) {
                    $payload[$col] = trim((string) ($v['phone'] ?? ''));
                }
                continue;
            }

            if (array_key_exists($col, $v)) {
                $payload[$col] = trim((string) ($v[$col] ?? ''));
            }
        }

        DB::connection('sqlsrv')->table('dbo.mx_companies')
            ->whereRaw('LTRIM(RTRIM(CAST(company_id AS nvarchar(255)))) = ?', [trim((string) ($v['company_id'] ?? ''))])
            ->update($payload);
    }
}

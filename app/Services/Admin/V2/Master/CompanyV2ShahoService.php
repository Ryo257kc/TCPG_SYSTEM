<?php

namespace App\Services\Admin\V2\Master;

use Illuminate\Support\Facades\DB;

class CompanyV2ShahoService
{
    /** @return array{latest:?array<string,string>,rows:list<array<string,string>>} */
    public function listByCompanyId(string $companyId): array
    {
        $rows = DB::connection('sqlsrv_payroll')
            ->table('mx_syaho')
            ->select([
                'syaho_no',
                'office_no',
                'kenpo_apply_date',
                'kenpo_kyuyo',
                'kenpo_shoyo',
                'kaigo_apply_date',
                'kaigo_kyuyo',
                'kaigo_shoyo',
                'kou_apply_date',
                'kou_kyuyo',
                'kou_shoyo',
                'jidou_apply_date',
                'jidou_kyuyo',
                'jidou_shoyo',
                'basic_payment_days',
            ])
            ->whereRaw('LTRIM(RTRIM(CAST(office_no AS nvarchar(50)))) = ?', [trim($companyId)])
            ->orderByDesc('jidou_apply_date')
            ->orderByDesc('kou_apply_date')
            ->orderByDesc('kaigo_apply_date')
            ->orderByDesc('kenpo_apply_date')
            ->orderByDesc('syaho_no')
            ->get()
            ->map(function ($row): array {
                return [
                    'syaho_no' => (string) ($row->syaho_no ?? ''),
                    'office_no' => (string) ($row->office_no ?? ''),
                    'kenpo_apply_date' => $this->normalizeDate($row->kenpo_apply_date ?? null),
                    'kenpo_kyuyo' => trim((string) ($row->kenpo_kyuyo ?? '')),
                    'kenpo_shoyo' => trim((string) ($row->kenpo_shoyo ?? '')),
                    'kaigo_apply_date' => $this->normalizeDate($row->kaigo_apply_date ?? null),
                    'kaigo_kyuyo' => trim((string) ($row->kaigo_kyuyo ?? '')),
                    'kaigo_shoyo' => trim((string) ($row->kaigo_shoyo ?? '')),
                    'kou_apply_date' => $this->normalizeDate($row->kou_apply_date ?? null),
                    'kou_kyuyo' => trim((string) ($row->kou_kyuyo ?? '')),
                    'kou_shoyo' => trim((string) ($row->kou_shoyo ?? '')),
                    'jidou_apply_date' => $this->normalizeDate($row->jidou_apply_date ?? null),
                    'jidou_kyuyo' => trim((string) ($row->jidou_kyuyo ?? '')),
                    'jidou_shoyo' => trim((string) ($row->jidou_shoyo ?? '')),
                    'basic_payment_days' => trim((string) ($row->basic_payment_days ?? '')),
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
        DB::connection('sqlsrv_payroll')->table('mx_syaho')->insert([
            'office_no' => trim((string) ($values['company_id'] ?? '')),
            'kenpo_apply_date' => $this->nullable($values['kenpo_apply_date'] ?? null),
            'kenpo_kyuyo' => $this->nullable($values['kenpo_kyuyo'] ?? null),
            'kenpo_shoyo' => $this->nullable($values['kenpo_shoyo'] ?? null),
            'kaigo_apply_date' => $this->nullable($values['kaigo_apply_date'] ?? null),
            'kaigo_kyuyo' => $this->nullable($values['kaigo_kyuyo'] ?? null),
            'kaigo_shoyo' => $this->nullable($values['kaigo_shoyo'] ?? null),
            'kou_apply_date' => $this->nullable($values['kou_apply_date'] ?? null),
            'kou_kyuyo' => $this->nullable($values['kou_kyuyo'] ?? null),
            'kou_shoyo' => $this->nullable($values['kou_shoyo'] ?? null),
            'jidou_apply_date' => $this->nullable($values['jidou_apply_date'] ?? null),
            'jidou_kyuyo' => $this->nullable($values['jidou_kyuyo'] ?? null),
            'jidou_shoyo' => $this->nullable($values['jidou_shoyo'] ?? null),
            'basic_payment_days' => $this->nullable($values['basic_payment_days'] ?? null),
        ]);
    }

    public function update(array $values): void
    {
        DB::connection('sqlsrv_payroll')
            ->table('mx_syaho')
            ->where('syaho_no', $values['syaho_no'])
            ->whereRaw('LTRIM(RTRIM(CAST(office_no AS nvarchar(50)))) = ?', [trim((string) ($values['company_id'] ?? ''))])
            ->update([
                'kenpo_apply_date' => $this->nullable($values['kenpo_apply_date'] ?? null),
                'kenpo_kyuyo' => $this->nullable($values['kenpo_kyuyo'] ?? null),
                'kenpo_shoyo' => $this->nullable($values['kenpo_shoyo'] ?? null),
                'kaigo_apply_date' => $this->nullable($values['kaigo_apply_date'] ?? null),
                'kaigo_kyuyo' => $this->nullable($values['kaigo_kyuyo'] ?? null),
                'kaigo_shoyo' => $this->nullable($values['kaigo_shoyo'] ?? null),
                'kou_apply_date' => $this->nullable($values['kou_apply_date'] ?? null),
                'kou_kyuyo' => $this->nullable($values['kou_kyuyo'] ?? null),
                'kou_shoyo' => $this->nullable($values['kou_shoyo'] ?? null),
                'jidou_apply_date' => $this->nullable($values['jidou_apply_date'] ?? null),
                'jidou_kyuyo' => $this->nullable($values['jidou_kyuyo'] ?? null),
                'jidou_shoyo' => $this->nullable($values['jidou_shoyo'] ?? null),
                'basic_payment_days' => $this->nullable($values['basic_payment_days'] ?? null),
            ]);
    }

    public function delete(string $companyId, string $syahoNo): void
    {
        DB::connection('sqlsrv_payroll')
            ->table('mx_syaho')
            ->where('syaho_no', $syahoNo)
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

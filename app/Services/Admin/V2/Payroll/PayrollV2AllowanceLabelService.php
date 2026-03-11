<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;

class PayrollV2AllowanceLabelService
{
    /** @return list<array{key:string,name:string}> */
    public function entries(): array
    {
        $rows = DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_allowance')
            ->select('amount_column_key', 'allowance_name', 'display_order', 'allowance_no')
            ->whereNotNull('amount_column_key')
            ->orderByRaw('CASE WHEN display_order IS NULL THEN 1 ELSE 0 END')
            ->orderBy('display_order')
            ->orderBy('allowance_no')
            ->get();

        $entries = [];
        $seen = [];
        foreach ($rows as $row) {
            $key = trim((string) ($row->amount_column_key ?? ''));
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $name = trim((string) ($row->allowance_name ?? ''));
            $entries[] = [
                'key' => $key,
                'name' => $name,
            ];
        }

        return $entries;
    }

    /** @return array<string, string> */
    public function labelMap(): array
    {
        $map = [];
        foreach ($this->entries() as $entry) {
            if ($entry['name'] === '') {
                continue;
            }
            $map[$entry['key']] = e($entry['name']);
        }

        return $map;
    }
}
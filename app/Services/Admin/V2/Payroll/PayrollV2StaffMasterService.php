<?php

namespace App\Services\Admin\V2\Payroll;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PayrollV2StaffMasterService
{
    /** @return array<string, array<string, mixed>> */
    public function map(): array
    {
        $idColumn = $this->staffIdColumn();
        if ($idColumn === null) {
            return [];
        }

        $rows = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->orderBy($idColumn)
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $staffId = trim((string) ($row->{$idColumn} ?? ''));
            if ($staffId === '' || isset($map[$staffId])) {
                continue;
            }
            $map[$staffId] = (array) $row;
        }

        return $map;
    }

    private function staffIdColumn(): ?string
    {
        foreach (['staff_id', 'kyuyo_staff_id'] as $candidate) {
            try {
                if (Schema::connection('sqlsrv')->hasColumn('mx_staffs', $candidate)
                    || Schema::connection('sqlsrv')->hasColumn('dbo.mx_staffs', $candidate)) {
                    return $candidate;
                }
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}
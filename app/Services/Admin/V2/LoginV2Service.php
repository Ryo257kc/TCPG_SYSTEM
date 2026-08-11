<?php

namespace App\Services\Admin\V2;

use Illuminate\Support\Facades\DB;

class LoginV2Service
{
    public function findStaffForLogin(string $staffId): ?object
    {
        $normalizedStaffId = mb_convert_kana(trim($staffId), 'as', 'UTF-8');

        return DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->select(['staff_id', 'staff_name', 'password', 'employment', 'is_admin'])
            ->whereRaw('LTRIM(RTRIM(CAST(staff_id as nvarchar(50)))) = ?', [$normalizedStaffId])
            ->first();
    }

    public function isRetired(?string $employment): bool
    {
        $normalized = str_replace([' ', '　'], '', trim((string) $employment));

        return $normalized !== '' && mb_strpos($normalized, '退職') !== false;
    }

    public function isAdminLogin(object $staff): bool
    {
        return (int) ($staff->is_admin ?? 0) === 1;
    }

    public function verifyPassword(object $staff, string $password): bool
    {
        return trim((string) ($staff->password ?? '')) === trim($password);
    }
}

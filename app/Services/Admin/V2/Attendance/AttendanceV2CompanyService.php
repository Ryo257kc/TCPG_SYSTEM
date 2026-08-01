<?php

namespace App\Services\Admin\V2\Attendance;

use Illuminate\Support\Facades\DB;

class AttendanceV2CompanyService
{
    // 勤怠画面の会社選択肢を取得するサービス。
    // 計算式は持たず、表示・絞り込み用のマスタ取得だけを担当する。
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

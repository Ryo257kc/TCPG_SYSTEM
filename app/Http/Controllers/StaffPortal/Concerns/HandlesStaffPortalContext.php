<?php

namespace App\Http\Controllers\StaffPortal\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait HandlesStaffPortalContext
{
    protected function staffPortalStaffId(Request $request): string
    {
        return (string) $request->session()->get('staff_id', '');
    }

    protected function redirectToStaffPortalLogin(): RedirectResponse
    {
        return redirect()->route('login.portal')->with('errorMessage', 'ログインしてください');
    }

    private function resolveDisplayName(?array $staffRow, string $fallback): string
    {
        $name = trim((string) ($staffRow['staff_name'] ?? ''));

        return $name !== '' ? $name : $fallback;
    }

    protected function staffPortalStaffRow(string $staffId): ?array
    {
        $row = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->whereRaw('LTRIM(RTRIM(staff_id)) = ?', [$staffId])
            ->first();

        return $row === null ? null : (array) $row;
    }

    // 権限設定
    // システムマスタ
    protected function isAdmin(?array $staffRow): bool
    {
        return (int) ($staffRow['is_admin'] ?? 0) === 1;
    }
    // 店舗管理
    protected function isStoreManager(?array $staffRow): bool
    {
        return (int) ($staffRow['is_admin'] ?? 0) === 1
            || (int) ($staffRow['is_store_management_user'] ?? 0) === 1;
    }
    // 往診事務
    protected function isAccounting(?array $staffRow): bool
    {
        return (int) ($staffRow['is_admin'] ?? 0) === 1
            || (int) ($staffRow['is_accounting_user'] ?? 0) === 1;
    }
    // 往診スタッフ
    protected function isOushinStaff(?array $staffRow): bool
    {
        return (int) ($staffRow['is_admin'] ?? 0) === 1
            || (int) ($staffRow['oushin_staff'] ?? 0) === 1;
    }
    // 事務所
    protected function isPaymentCheck(?array $staffRow): bool
    {
        return (int) ($staffRow['is_admin'] ?? 0) === 1
            || (int) ($staffRow['is_payment_check_user'] ?? 0) === 1;
    }
    // 往診管理
    protected function isVisitManagement(?array $staffRow): bool
    {
        return (int) ($staffRow['is_admin'] ?? 0) === 1
            || (int) ($staffRow['is_visit_management_user'] ?? 0) === 1;
    }
    // 往診売上
    protected function isViewOnly(?array $staffRow): bool
    {
        return (int) ($staffRow['is_admin'] ?? 0) === 1
            || (int) ($staffRow['is_view_only_user'] ?? 0) === 1;
    }
    // 日報管理
    protected function isDailyReport(?array $staffRow): bool
    {
        return (int) ($staffRow['is_admin'] ?? 0) === 1
            || (int) ($staffRow['is_daily_report_user'] ?? 0) === 1;
    }
    // 年末調整スタッフ申請の公開判定。公開日が未設定・未到来の間はシステムマスタのみ閲覧可。
    protected function isYearEndAdjustmentPublished(): bool
    {
        $publishDate = DB::connection('sqlsrv_payroll')
            ->table('dbo.year_end_adjustment_settings')
            ->where('target_year', (int) date('Y'))
            ->value('publish_date');

        return $publishDate !== null && Carbon::parse($publishDate)->lessThanOrEqualTo(now());
    }

    // 共通まとめ
    protected function commonViewData(Request $request, array $data = []): array
    {
        $staffId = $this->staffPortalStaffId($request);
        $staffRow = $this->staffPortalStaffRow($staffId);
        $showPayrollLinks = $this->shouldShowPayrollLinks($staffId, $staffRow);
        $isAdmin = $this->isAdmin($staffRow);

        return array_merge($data, [
            'displayName' => $this->resolveDisplayName($staffRow, $staffId),
            'isMaintenanceWindow' => $this->isMaintenanceWindow(),
            'isAdmin' => $isAdmin,
            'isStoreManager' => $this->isStoreManager($staffRow),
            'isAccounting' => $this->isAccounting($staffRow),
            'isOushinStaff' => $this->isOushinStaff($staffRow),
            'isPaymentCheck' => $this->isPaymentCheck($staffRow),
            'isVisitManagement' => $this->isVisitManagement($staffRow),
            'isViewOnly' => $this->isViewOnly($staffRow),
            'isDailyReport' => $this->isDailyReport($staffRow),
            'hidePayrollLinks' => !$showPayrollLinks,
            'isYearEndAdjustmentVisible' => $isAdmin || $this->isYearEndAdjustmentPublished(),
        ]);
    }

    // 権限業務委託
    private function isContractor(?array $staffRow): bool
    {
        if (!$staffRow) {
            return false;
        }

        $division = trim((string) ($staffRow['staff_division'] ?? ''));

        return str_contains($division, '業務委託')
            || str_contains($division, '委託');
    }

    private function shouldShowPayrollLinks(string $staffId, ?array $staffRow): bool
    {
        if ($this->isContractor($staffRow)) {
            return false;
        }

        $count = (int) DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_kyuyo_shou')
            ->whereRaw('LTRIM(RTRIM(kyuyo_staff_id)) = ?', [$staffId])
            ->where('edit_lock', 1)
            ->count();

        return $count > 0;
    }


    protected function parseMoneyInput(mixed $value): ?float
    {
        $normalized = str_replace(',', '', trim((string) ($value ?? '')));
        if ($normalized === '') {
            return null;
        }

        return (float) $normalized;
    }


    // targetMonth
    protected function targetMonth(Request $request, string $parameterName = 'target_month'): string
    {
        return trim((string) $request->query($parameterName, now()->format('Y-m')));
    }

    protected function targetMonthStartDate(string $targetMonth): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $targetMonth . '-01')->startOfMonth();
    }


    // 往診スタッフ選択（退職者除外、選択月退職者のみ含む）
    protected function homeVisitStaffOptions(Carbon|string $targetMonthStart): array
    {
        $targetMonthStartDate = $targetMonthStart instanceof Carbon
            ? $targetMonthStart->format('Y-m-d')
            : $targetMonthStart;

        return DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->select(['staff_id', 'staff_name', 'display_name_ja'])
            ->where(function ($query): void {
                $query->where('oushin_staff', true)
                    ->orWhere('oushin_staff', 1);
            })
            ->where(function ($query) use ($targetMonthStartDate): void {
                $query->whereNull('tai_date')
                    ->orWhereDate('tai_date', '>=', $targetMonthStartDate);
            })
            ->whereNotNull('staff_id')
            ->where('staff_id', '<>', '')
            ->whereNotNull('staff_name')
            ->where('staff_name', '<>', '')
            ->orderBy('staff_name')
            ->get()
            ->map(fn($row): array => [
                'staff_id' => trim((string) ($row->staff_id ?? '')),
                'staff_name' => trim((string) ($row->staff_name ?? '')),
                'display_name_ja' => trim((string) ($row->display_name_ja ?? '')),
            ])
            ->filter(fn(array $row): bool => $row['staff_id'] !== '' && $row['staff_name'] !== '')
            ->unique('staff_id')
            ->values()
            ->all();
    }

    // 往診スタッフ選択（退職者含む）
    public function patientRegisteredStaffOptions(): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.hv_kanjya_info as k')
            ->join('dbo.mx_staffs as s', 's.staff_id', '=', 'k.collection_staff')
            ->select('s.staff_id', 's.staff_name', 's.display_name_ja')
            ->whereNotNull('k.collection_staff')
            ->where('k.collection_staff', '<>', '')
            ->distinct()
            ->orderBy('s.display_name_ja')
            ->get()
            ->map(fn($row): array => [
                'staff_id' => trim((string) ($row->staff_id ?? '')),
                'staff_name' => trim((string) ($row->staff_name ?? '')),
                'display_name_ja' => trim((string) ($row->display_name_ja ?? '')),
            ])
            ->filter(fn(array $row): bool => $row['staff_id'] !== '')
            ->values()
            ->all();
    }

    // 全スタッフ表示（中身おかしい）
    protected function allStaffOptions(): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->select(['staff_id', 'staff_name', 'display_name_ja'])
            ->whereNotNull('staff_id')
            ->where('staff_id', '<>', '')
            ->orderBy('staff_name')
            ->get()
            ->map(fn($row): array => [
                'staff_id' => trim((string) ($row->staff_id ?? '')),
                'staff_name' => trim((string) ($row->staff_name ?? '')),
                'display_name_ja' => trim((string) ($row->display_name_ja ?? '')),
            ])
            ->filter(fn(array $row): bool => $row['staff_id'] !== '')
            ->values()
            ->all();
    }

    // 往診日報店舗（エリア）選択
    // public function receiptVisitAreaOptions(): array
    // {
    //     return DB::connection('sqlsrv')
    //         ->table('dbo.mx_departments')
    //         ->select(['visit_area'])
    //         ->where(function ($query): void {
    //             $query->where('has_receipt', true)
    //                 ->orWhere('has_receipt', 1);
    //         })
    //         ->whereNull('closed_on')
    //         ->whereNotNull('visit_area')
    //         ->orderBy('visit_area')
    //         ->get()
    //         ->map(fn($row): string => trim((string) ($row->visit_area ?? '')))
    //         ->filter(fn(string $name): bool => $name !== '')
    //         ->unique()
    //         ->values()
    //         ->all();
    // }
    // 店舗売上スタッフ選択（業務委託除外）
    protected function storeSalesStaffOptions(Carbon|string $targetMonthStart): array
    {
        $targetMonthStartDate = $targetMonthStart instanceof Carbon
            ? $targetMonthStart->format('Y-m-d')
            : $targetMonthStart;

        return DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->select(['staff_id', 'staff_name', 'display_name_ja'])
            ->whereNotNull('staff_id')
            ->where('staff_id', '<>', '')
            ->where(function ($query) use ($targetMonthStartDate): void {
                $query->whereNull('tai_date')
                    ->orWhereDate('tai_date', '>=', $targetMonthStartDate);
            })
            ->where(function ($query): void {
                $query->whereNull('staff_division')
                    ->orWhere(function ($query): void {
                        $query->where('staff_division', 'not like', '%業務委託%')
                            ->where('staff_division', 'not like', '%委託%');
                    });
            })
            ->orderBy('staff_id')
            ->get()
            ->map(fn($row): array => [
                'staff_id' => trim((string) ($row->staff_id ?? '')),
                'staff_name' => trim((string) ($row->staff_name ?? '')),
                'display_name_ja' => trim((string) ($row->display_name_ja ?? '')),
            ])
            ->filter(fn(array $row): bool => $row['staff_id'] !== '' && !in_array($row['staff_id'], ['000', '001'], true))
            ->unique('staff_id')
            ->values()
            ->all();
    }

    public function receiptVisitAreaOptions(): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_departments')
            ->join('dbo.mx_stores', 'dbo.mx_stores.store_code', '=', 'dbo.mx_departments.official_store_no')
            ->select(['dbo.mx_departments.visit_area'])
            ->where('dbo.mx_stores.business_type', '（往診）')
            ->where(function ($query): void {
                $query->where('has_receipt', true)
                    ->orWhere('has_receipt', 1);
            })
            ->whereNull('closed_on') // ←そのまま
            ->whereNotNull('dbo.mx_departments.visit_area')
            ->orderBy('dbo.mx_departments.visit_area')
            ->get()
            ->map(fn($row): string => trim((string) ($row->visit_area ?? '')))
            ->filter(fn(string $name): bool => $name !== '')
            ->unique()
            ->values()
            ->all();
    }

    // 往診店舗選択
    protected function receiptStoreOptions(): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_departments')
            ->join('dbo.mx_stores', 'dbo.mx_stores.store_code', '=', 'dbo.mx_departments.official_store_no')
            ->select(['store_category'])
            ->whereIn('dbo.mx_stores.business_type', ['（往診）', '（店舗）'])
            ->where(function ($query): void {
                $query->where('has_receipt', true)
                    ->orWhere('has_receipt', 1);
            })
            ->whereNull('closed_on')
            ->whereNotNull('store_category')
            ->orderBy('store_category')
            ->get()
            ->map(fn($row): string => trim((string) ($row->store_category ?? '')))
            ->filter(fn(string $name): bool => $name !== '')
            ->unique()
            ->values()
            ->all();
    }

    // 往診施設名選択
    public function patientFacilityOptions(): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.hv_kanjya_info')
            ->whereNotNull('facility_name')
            ->where('facility_name', '<>', '')
            ->distinct()
            ->orderBy('facility_name')
            ->pluck('facility_name')
            ->toArray();
    }

    // 店舗選択セレクト
    protected function selectedStore(Request $request, string $parameterName = 'store_name'): string
    {
        return trim((string) $request->query($parameterName, ''));
    }


    // レセプト関連DB指定
    protected function receiptDetailBaseQuery()
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_insurance_claim_details as detail')
            ->leftJoin('dbo.mx_insurers as insurer', 'detail.insurer_number', '=', 'insurer.insurer_number')
            ->leftJoin('dbo.mx_departments as department', 'department.store_short_name', '=', 'detail.store_name')
            ->leftJoin('dbo.mx_stores as store', 'department.official_store_no', '=', 'store.store_code')
            ->leftJoin('dbo.mx_companies as company', 'store.company_id', '=', 'company.company_id');
    }

    // 書式変換
    protected function formatMoneyValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $numericValue = (float) $value;

        if ($numericValue == 0.0) {
            return '0';
        }

        return number_format($numericValue);
    }

    protected function formatDateValue(mixed $value, string $format): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->format($format);
        } catch (\Throwable) {
            return '';
        }
    }

    protected function formatDecimalInput(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $numericValue = (float) $value;

        if ($numericValue == 0.0) {
            return '';
        }

        return floor($numericValue) == $numericValue
            ? (string) (int) $numericValue
            : rtrim(rtrim((string) $numericValue, '0'), '.');
    }
    // 退職除外
    private function isRetired(?array $staffRow): bool
    {
        $employment = str_replace(['　', ' '], '', (string) ($staffRow['employment'] ?? ''));

        return $employment !== '' && mb_strpos($employment, '退職') !== false;
    }

    // メンテナンス（管理画面「通知・申請」から設定）
    protected function isMaintenanceWindow(): bool
    {
        $now = Carbon::now('Asia/Tokyo')->format('Y-m-d H:i:s');

        return DB::connection('sqlsrv')
            ->table('dbo.mx_maintenance_windows')
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->exists();
    }


    // // スタッフポータルヘッダー名前表示
    // protected function headerData(Request $request): array
    // {
    //     $staffId = $this->staffPortalStaffId($request);

    //     return [
    //         'staffId' => $staffId,
    //         'displayName' => $this->resolveDisplayName($staffId),
    //         'hidePayrollLinks' => false,
    //     ];
    // }

    // ヘッダー給与賞与表示判定
    // protected function staffPortalHidePayrollLinks(string $staffId): bool
    // {
    //     $staffRow = $this->staffPortalStaffRow($staffId);

    //     return !$this->staffPortalShouldShowPayrollLinks($staffId, $staffRow);
    // }
    // protected function staffPortalStaffRow(string $staffId): ?array
    // {
    // $row = DB::connection('sqlsrv')
    //     ->table('dbo.mx_staffs')
    //     ->whereRaw('LTRIM(RTRIM(CAST(staff_id as nvarchar(50)))) = ?', [$staffId])
    //     ->first();

    // return $row === null ? null : (array) $row;
    // }

    // protected function staffPortalIsContractor(?array $staffRow): bool
    // {
    //     if (!$staffRow) {
    //         return false;
    //     }

    //     $division = trim((string) ($staffRow['staff_division'] ?? ''));

    //     return str_contains($division, '業務委託')
    //         || str_contains($division, '委託');
    // }

    // protected function staffPortalShouldShowPayrollLinks(string $staffId, ?array $staffRow): bool
    // {
    //     if ($this->staffPortalIsContractor($staffRow)) {
    //         return false;
    //     }

    //     $count = (int) DB::connection('sqlsrv_payroll')
    //         ->table('dbo.mx_kyuyo_shou')
    //         ->whereRaw('LTRIM(RTRIM(kyuyo_staff_id)) = ?', [$staffId])
    //         ->where('edit_lock', 1)
    //         ->count();

    //     return $count > 0;
    // }

}

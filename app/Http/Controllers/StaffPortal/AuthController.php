<?php

namespace App\Http\Controllers\StaffPortal;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AuthController extends Controller
{
    use HandlesStaffPortalContext;

    public function index(Request $request): View
    {
        return view('staff_portal.login', [
            'isMaintenance' => $this->isMaintenanceWindow(),
            'errorMessage' => (string) $request->session()->get('errorMessage', ''),
        ]);
    }

    // ログイン
    public function login(Request $request): RedirectResponse
    {
        $staffId = mb_convert_kana(trim((string) $request->input('staff_id', '')), 'as', 'UTF-8');
        $password = trim((string) $request->input('password', ''));
        $loginError = 'ログインIDまたはパスワードが正しくありません。';

        if ($staffId === '' || $password === '') {
            return back()->withInput($request->only('staff_id'))->with('errorMessage', $loginError);
        }

        $staff = $this->getStaffRow($staffId);
        if ($staff === null) {
            return back()->withInput($request->only('staff_id'))->with('errorMessage', $loginError);
        }

        if (trim((string) ($staff['password'] ?? '')) !== $password) {
            return back()->withInput($request->only('staff_id'))->with('errorMessage', $loginError);
        }

        if ($this->isRetired($staff)) {
            return back()->withInput($request->only('staff_id'))->with('errorMessage', $loginError);
        }

        if (!$this->isAdmin($staff) && $this->isMaintenanceWindow()) {
            return back()->withInput($request->only('staff_id'))->with('errorMessage', 'ただいまメンテナンス中です。しばらくしてから再度ログインしてください。');
        }

        // NOTE:
        // Permission flags are unstable during schema migration.
        // Keep login available in dev by validating only ID/password here.

        $request->session()->regenerate();

        if ($password === '123') {
            $request->session()->put('password_change_staff_id', (string) ($staff['staff_id'] ?? $staffId));
            $request->session()->put('password_change_staff_name', (string) ($staff['staff_name'] ?? ''));

            return redirect()->route('staff_portal.password_change');
        }

        $request->session()->put('staff_id', (string) ($staff['staff_id'] ?? $staffId));

        return redirect()->route('dashboard');
    }

    // ダッシュボード
    public function dashboard(Request $request): RedirectResponse|View
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'ログインしてください。');
        }

        return view('staff_portal.dashboard.index', $this->commonViewData($request, [
            'staffId' => $staffId,
            'needsCorrection' => $this->hasReturnedAttendance($staffId),
            'needsYearEndAttention' => $this->hasReturnedYearEndApplication($staffId),
            'unconfirmedPaymentMonths' => $this->unconfirmedPaymentMonths($staffId),
            'informationMessages' => $this->informationMessages(),
        ]));
    }

    // 自分の入金確定がまだの月一覧
    private function unconfirmedPaymentMonths(string $staffId): array
    {
        $monthEnd = now('Asia/Tokyo')->startOfMonth()->addMonthNoOverflow()->format('Y-m-d');

        return DB::connection('sqlsrv')
            ->table('dbo.hv_ryoukin')
            ->whereRaw('LTRIM(RTRIM(payment_staff)) = ?', [$staffId])
            ->where('target_month', '<', $monthEnd)
            ->where(function ($query): void {
                $query->whereNull('is_payment_confirmed')
                    ->orWhere('is_payment_confirmed', 0);
            })
            ->selectRaw('DISTINCT target_month')
            ->orderBy('target_month')
            ->pluck('target_month')
            ->map(fn($month): string => Carbon::parse($month)->format('Y年n月'))
            ->all();
    }

    private function hasReturnedYearEndApplication(string $staffId): bool
    {
        return DB::connection('sqlsrv_payroll')
            ->table('dbo.mx_nen_tyo')
            ->whereRaw('LTRIM(RTRIM(staff_id)) = ?', [$staffId])
            ->whereYear('year_end', (int) date('Y'))
            ->where('application_status', 'returned')
            ->exists();
    }

    public function passwordChange(Request $request): RedirectResponse|View
    {
        $staffId = (string) $request->session()->get('password_change_staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal');
        }

        return view('staff_portal.password_change', [
            'staffId' => $staffId,
            'staffName' => (string) $request->session()->get('password_change_staff_name', ''),
            'errorMessage' => (string) $request->session()->get('errorMessage', ''),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $staffId = (string) $request->session()->get('password_change_staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal');
        }

        $password = trim((string) $request->input('password', ''));
        $passwordConfirm = trim((string) $request->input('password_confirm', ''));

        if ($password === '' || $password !== $passwordConfirm) {
            return back()->with('errorMessage', 'パスワードが一致していません。');
        }

        if ($password === '123') {
            return back()->with('errorMessage', '初期パスワード以外を入力してください。');
        }

        $updated = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->whereRaw('LTRIM(RTRIM(CAST(staff_id as nvarchar(50)))) = ?', [$staffId])
            ->update(['password' => $password]);

        if ($updated < 1) {
            return back()->with('errorMessage', 'パスワードを更新できませんでした。');
        }

        $request->session()->forget(['password_change_staff_id', 'password_change_staff_name']);
        $request->session()->put('staff_id', $staffId);

        // 就業状況「採用予定」＝新入社スタッフのアカウントは、初回パスワード変更後は
        // ダッシュボードではなく入社手続きフォームへ誘導する。事務所が入社手続きを
        // 確認・反映する際にemploymentを「在職」へ切り替えるため、以降はこの分岐に来ない。
        $staffRow = $this->staffPortalStaffRow($staffId);
        if (trim((string) ($staffRow['employment'] ?? '')) === '採用予定') {
            return redirect()->route('onboarding_request');
        }

        return redirect()->route('dashboard');
    }

    private function informationMessages(): array
    {
        return DB::connection('sqlsrv')
            ->table('dbo.mx_message')
            ->select(['mesa_no', 'mase_date', 'title', 'contents'])
            ->whereIn('destination', ['info', 'All'])
            ->where(function ($query): void {
                $query->whereNull('me_check')
                    ->orWhere('me_check', 0);
            })
            ->orderByDesc('mase_date')
            ->orderByDesc('mesa_no')
            ->get()
            ->map(function ($row): array {
                $messageDate = '';

                try {
                    $messageDate = $row->mase_date ? Carbon::parse($row->mase_date)->format('Y/m/d') : '';
                } catch (\Throwable) {
                    $messageDate = trim((string) ($row->mase_date ?? ''));
                }

                return [
                    'mase_date' => $messageDate,
                    'title' => trim((string) ($row->title ?? '')),
                    'contents' => trim((string) ($row->contents ?? '')),
                ];
            })
            ->filter(fn(array $row): bool => $row['title'] !== '' || $row['contents'] !== '')
            ->values()
            ->all();
    }

    private function hasReturnedAttendance(string $staffId): bool
    {
        $staff = $this->getStaffRow($staffId);
        $timeCardKeys = array_values(array_unique(array_filter([
            trim((string) ($staff['staff_id'] ?? $staffId)),
            trim((string) ($staff['staff_name'] ?? '')),
        ], static fn(string $value): bool => $value !== '')));

        if ($timeCardKeys === []) {
            return false;
        }

        return DB::connection('sqlsrv')
            ->table('dbo.mx_time_cards')
            ->where('is_returned', 1)
            ->whereIn(DB::raw('LTRIM(RTRIM(staff_name))'), $timeCardKeys)
            ->exists();
    }

    public function adminLogin(Request $request): RedirectResponse
    {
        if (!$request->session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        $adminStaffId = trim((string) $request->session()->get('admin_staff_id', ''));
        if ($adminStaffId === '') {
            return redirect()->route('admin.login');
        }

        $staff = $this->getStaffRow($adminStaffId);
        if ($staff === null || !$this->isAdmin($staff) || $this->isRetired($staff)) {
            $request->session()->forget('staff_id');

            return redirect()->route('admin.dashboard');
        }

        $request->session()->put('staff_id', (string) ($staff['staff_id'] ?? $adminStaffId));

        return redirect()->route('dashboard');
    }

    private function getStaffRow(string $staffId): ?array
    {
        $row = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->where(function ($q) use ($staffId): void {
                $q->whereRaw('LTRIM(RTRIM(COALESCE(staff_id, CAST(staff_id as nvarchar(50))))) = ?', [$staffId])
                    ->orWhereRaw('LTRIM(RTRIM(CAST(staff_id as nvarchar(50)))) = ?', [$staffId]);
            })
            ->first();

        if ($row === null) {
            return null;
        }

        $r = (array) $row;

        return [
            'staff_id' => trim((string) ($r['staff_id'] ?? '')),
            'staff_name' => (string) ($r['staff_name'] ?? ''),
            'password' => (string) ($r['password'] ?? ''),
            'employment' => (string) ($r['employment'] ?? ''),
            'staff_division' => (string) ($r['staff_division'] ?? ''),
            'is_admin' => (int) ($r['is_admin'] ?? 0),
            'is_daily_report_user' => (int) ($r['is_daily_report_user'] ?? 0),
            'is_payment_check_user' => (int) ($r['is_payment_check_user'] ?? 0),
            'is_visit_management_user' => (int) ($r['is_visit_management_user'] ?? 0),
            // 'is_store_management_user' => (int) ($r['is_store_management_user'] ?? 0),
            'oushin_staff' => (int) ($r['oushin_staff'] ?? 0),
            'is_accounting_user' => (int) ($r['is_accounting_user'] ?? 0),
            'is_view_only_user' => (int) ($r['is_view_only_user'] ?? 0),
            // 'front_staff' => (int) ($r['front_staff'] ?? 0),
        ];
    }

    // private function resolveDisplayName(?array $staffRow, string $fallback): string
    // {
    //     $name = (string) ($staffRow['staff_name'] ?? '');

    //     return $name !== '' ? $name : $fallback;
    // }

    // 権限
    // private function resolveIsAdmin(?array $staffRow): bool
    // {
    //     return ((int) ($staffRow['is_admin'] ?? 0)) === 1;
    // }

    // private function resolveIsVisit(?array $staffRow): bool
    // {
    //     return ((int) ($staffRow['is_visit_management_user'] ?? 0)) === 1;
    // }
    // private function resolveIsOfficeAdmin(?array $staffRow): bool
    // {
    //     return ((int) ($staffRow['is_payment_check_user'] ?? 0)) === 1;
    // }

    // private function resolveIsDailyReportUser(?array $staffRow): bool
    // {
    //     return ((int) ($staffRow['is_daily_report_user'] ?? 0)) === 1;
    // }

    // private function resolveIsStoreAdmin(?array $staffRow): bool
    // {
    //     return ((int) ($staffRow['is_store_manager'] ?? 0)) === 1;
    // }

    // private function resolveIsAccounting(?array $staffRow): bool
    // {
    //     return ((int) ($staffRow['is_accounting_user'] ?? 0)) === 1;
    // }

    // private function resolveIsViewOnly(?array $staffRow): bool
    // {
    //     return ((int) ($staffRow['is_view_only_user'] ?? 0)) === 1;
    // }

    // // private function resolveIsStoreManager(?array $staffRow): bool
    // // {
    // //     return ((int) ($staffRow['is_store_management_user'] ?? 0)) === 1;
    // // }

    // private function resolveoOushinStaff(?array $staffRow): bool
    // {
    //     return ((int) ($staffRow['oushin_staff'] ?? 0)) === 1;
    // }



    // private function isRetired(?array $staffRow): bool
    // {
    //     $employment = str_replace(['　', ' '], '', (string) ($staffRow['employment'] ?? ''));

    //     return $employment !== '' && mb_strpos($employment, '退職') !== false;
    // }

    // private function isMaintenanceWindow(): bool
    // {
    //     $now = Carbon::now('Asia/Tokyo');

    //     return $now->day === 20 && ((int) $now->format('H')) >= 18;
    // }

    // private function isContractor(?array $staffRow): bool
    // {
    //     if (!$staffRow) {
    //         return false;
    //     }

    //     $division = trim((string) ($staffRow['staff_division'] ?? ''));

    //     return str_contains($division, '業務委託')
    //         || str_contains($division, '委託');
    // }

    // private function shouldShowPayrollLinks(string $staffId, ?array $staffRow): bool
    // {
    //     if ($this->isContractor($staffRow)) {
    //         return false;
    //     }

    //     try {
    //         $query = DB::connection('sqlsrv_payroll');
    //         if ($this->useMxPayrollTable()) {
    //             $count = (int) $query
    //                 ->table('dbo.mx_kyuyo_shou')
    //                 ->whereRaw('LTRIM(RTRIM(kyuyo_staff_id)) = ?', [$staffId])
    //                 ->where('edit_lock', 1)
    //                 ->count();
    //         } else {
    //             $count = (int) $query
    //                 ->table('dbo.t_kyuyo_shou')
    //                 ->where('kyuyo_staff_id', $staffId)
    //                 ->where('edit_lock', 1)
    //                 ->count();
    //         }

    //         return $count > 0;
    //     } catch (\Throwable $e) {
    //         Log::warning('Payroll visibility fallback: sqlsrv_payroll check failed.', [
    //             'staff_id' => $staffId,
    //             'error' => $e->getMessage(),
    //         ]);

    //         return false;
    //     }
    // }

}

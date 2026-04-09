<?php

namespace App\Http\Controllers\StaffPortal;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function index(Request $request): View
    {
        return view('staff_portal.landing', [
            'isMaintenance' => $this->isMaintenanceWindow(),
            'errorMessage' => (string) $request->session()->get('errorMessage', ''),
        ]);
    }

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

        // NOTE:
        // Permission flags are unstable during schema migration.
        // Keep login available in dev by validating only ID/password here.

        $request->session()->regenerate();
        $request->session()->put('staff_id', (string) ($staff['staff_id'] ?? $staffId));

        return redirect()->route('dashboard');
    }

    public function dashboard(Request $request): RedirectResponse|View
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'Login required.');
        }

        $staffRow = $this->getStaffRow($staffId);
        $showPayrollLinks = $this->shouldShowPayrollLinks($staffId, $staffRow);

        return view('staff_portal.dashboard.index', [
            'staffId' => $staffId,
            'displayName' => $this->resolveDisplayName($staffRow, $staffId),
            'isAdmin' => $this->resolveIsAdmin($staffRow),
            'isOfficeAdmin' => $this->resolveIsOfficeAdmin($staffRow),
            'needsCorrection' => false,
            'hidePayrollLinks' => !$showPayrollLinks,
        ]);
    }

    private function getStaffRow(string $staffId): ?array
    {
        if (!$this->useMxStaffTable()) {
            return null;
        }

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
            'is_daily_report_user' => (int) ($r['日報'] ?? 0),
            'is_payment_check_user' => (int) ($r['is_payment_check_user'] ?? 0),
            'is_store_manager' => (int) ($r['is_store_management_user'] ?? 0),
            'is_admin' => (int) ($r['is_store_management_user'] ?? 0),
        ];
    }

    private function useMxStaffTable(): bool
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        try {
            $cached = Schema::connection('sqlsrv')->hasTable('mx_staffs')
                || Schema::connection('sqlsrv')->hasTable('dbo.mx_staffs');
        } catch (\Throwable) {
            $cached = false;
        }

        return $cached;
    }

    private function resolveDisplayName(?array $staffRow, string $fallback): string
    {
        $name = (string) ($staffRow['staff_name'] ?? '');

        return $name !== '' ? $name : $fallback;
    }

    private function resolveIsAdmin(?array $staffRow): bool
    {
        return ((int) ($staffRow['is_admin'] ?? 0)) === 1;
    }

    private function resolveIsOfficeAdmin(?array $staffRow): bool
    {
        return ((int) ($staffRow['is_payment_check_user'] ?? 0)) === 1;
    }

    private function resolveIsDailyReportUser(?array $staffRow): bool
    {
        return ((int) ($staffRow['is_daily_report_user'] ?? 0)) === 1;
    }

    private function resolveIsPayrollManager(?array $staffRow): bool
    {
        return ((int) ($staffRow['is_store_manager'] ?? 0)) === 1;
    }

    private function isRetired(?array $staffRow): bool
    {
        $employment = str_replace(['　', ' '], '', (string) ($staffRow['employment'] ?? ''));

        return $employment !== '' && mb_strpos($employment, '退職') !== false;
    }

    private function isMaintenanceWindow(): bool
    {
        $now = Carbon::now('Asia/Tokyo');

        return $now->day === 20 && ((int) $now->format('H')) >= 18;
    }

    private function isContractor(?array $staffRow): bool
    {
        if (!$staffRow) {
            return false;
        }

        foreach ($staffRow as $value) {
            if (!is_scalar($value) && $value !== null) {
                continue;
            }
            $text = trim((string) $value);
            if ($text === '') {
                continue;
            }
            if (mb_strpos($text, '業務委託') !== false || mb_strpos($text, '委託') !== false) {
                return true;
            }
        }

        return false;
    }

    private function shouldShowPayrollLinks(string $staffId, ?array $staffRow): bool
    {
        if ($this->isContractor($staffRow)) {
            return false;
        }

        try {
            $query = DB::connection('sqlsrv_payroll');
            if ($this->useMxPayrollTable()) {
                $count = (int) $query
                    ->table('dbo.mx_kyuyo_shou')
                    ->whereRaw('LTRIM(RTRIM(kyuyo_staff_id)) = ?', [$staffId])
                    ->where('edit_lock', 1)
                    ->count();
            } else {
                $count = (int) $query
                    ->table('dbo.t_kyuyo_shou')
                    ->where('kyuyo_staff_id', $staffId)
                    ->where('edit_lock', 1)
                    ->count();
            }

            return $count > 0;
        } catch (\Throwable $e) {
            Log::warning('Payroll visibility fallback: sqlsrv_payroll check failed.', [
                'staff_id' => $staffId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function useMxPayrollTable(): bool
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        try {
            $cached = Schema::connection('sqlsrv_payroll')->hasTable('mx_kyuyo_shou')
                || Schema::connection('sqlsrv_payroll')->hasTable('dbo.mx_kyuyo_shou');
        } catch (\Throwable) {
            $cached = false;
        }

        return $cached;
    }
}

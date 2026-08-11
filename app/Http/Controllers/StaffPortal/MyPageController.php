<?php

namespace App\Http\Controllers\StaffPortal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MyPageController extends Controller
{
    use HandlesStaffPortalContext;

    public function index(Request $request): RedirectResponse|View
    {
        $staffId = $this->staffPortalStaffId($request);

        $staffRow = DB::connection('sqlsrv')
            ->table('dbo.mx_staffs')
            ->whereRaw('LTRIM(RTRIM(staff_id)) = ?', [$staffId])
            ->first(['mail', 'password', 'staff_name']);

        return view('staff_portal.mypage.index', [
            'displayName' => $this->resolveDisplayName($staffRow === null ? null : (array) $staffRow, $staffId),
            'selectedRow' => $staffRow === null ? [] : (array) $staffRow,
            'needsProfileRequestAttention' => $this->hasReturnedProfileRequest($staffId),
        ]);
    }

    private function hasReturnedProfileRequest(string $staffId): bool
    {
        return DB::connection('sqlsrv_payroll')
            ->table('dbo.staff_profile_requests')
            ->where('staff_id', $staffId)
            ->where('status', 'returned')
            ->exists();
    }

    public function update(Request $request): RedirectResponse
    {
        $staffId = $this->staffPortalStaffId($request);

        $action = (string) $request->input('_action', '');
        $updates = [];

        if ($action === 'save_mail') {
            $updates['mail'] = trim((string) $request->input('mail', ''));
        }

        if ($action === 'save_password') {
            $password = trim((string) $request->input('password', ''));
            $passwordConfirm = trim((string) $request->input('password_confirm', ''));

            if ($password === '' || $password !== $passwordConfirm) {
                return redirect()
                    ->route('mypage')
                    ->with('errorMessage', 'パスワードが一致していません。');
            }

            $updates['password'] = $password;
        }

        if ($updates !== []) {
            DB::connection('sqlsrv')
                ->table('dbo.mx_staffs')
                ->whereRaw('LTRIM(RTRIM(staff_id)) = ?', [$staffId])
                ->update($updates);
        }

        return redirect()->route('mypage')->with('statusMessage', '保存しました。');
    }
}

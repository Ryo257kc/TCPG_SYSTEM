<?php

namespace App\Http\Middleware;

use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StaffPortalAuthenticate
{
    use HandlesStaffPortalContext;

    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'ログインしてください。');
        }

        $staffRow = $this->staffPortalStaffRow($staffId);
        if (!$this->isAdmin($staffRow) && $this->isMaintenanceWindow()) {
            $request->session()->forget('staff_id');

            return redirect()->route('login.portal')->with('errorMessage', 'ただいまメンテナンス中です。しばらくしてから再度ログインしてください。');
        }

        return $next($request);
    }
}

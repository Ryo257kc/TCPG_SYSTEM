<?php

namespace App\Http\Middleware;

use App\Http\Controllers\StaffPortal\Concerns\HandlesStaffPortalContext;
use App\Services\Admin\V2\AdminTwoFactorService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StaffPortalAuthenticate
{
    use HandlesStaffPortalContext;

    public function __construct(private readonly AdminTwoFactorService $twoFactor)
    {
    }

    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $staffId = (string) $request->session()->get('staff_id', '');
        if ($staffId === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'ログインしてください。');
        }

        // スタッフ直接ログインは従来どおり。管理者認証情報が残る同一IDの
        // 引継ぎセッションだけ、初期化後に管理機能を使い続けられないよう確認する。
        if ($this->twoFactor->enabled()
            && $request->session()->get('admin_2fa_staff_id') === $staffId
            && !$this->twoFactor->verified($request)) {
            $this->twoFactor->clear($request);

            return redirect()->route('login.portal')->with('errorMessage', '管理者認証が失効しました。ログインし直してください。');
        }

        $staffRow = $this->staffPortalStaffRow($staffId);
        if (!$this->isAdmin($staffRow) && $this->isMaintenanceWindow()) {
            $request->session()->forget('staff_id');

            return redirect()->route('login.portal')->with('errorMessage', 'ただいまメンテナンス中です。しばらくしてから再度ログインしてください。');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\Admin\V2\AdminTwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthenticate
{
    public function __construct(private readonly AdminTwoFactorService $twoFactor)
    {
    }

    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if (! $request->session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        if ($this->twoFactor->enabled()) {
            if (!$this->twoFactor->verified($request)) {
                $this->twoFactor->clear($request);

                return redirect()->route('admin.login');
            }
            if ($request->session()->has('admin_2fa_recovery_codes')) {
                return redirect()->route('admin.two_factor.recovery');
            }
        }

        return $next($request);
    }
}

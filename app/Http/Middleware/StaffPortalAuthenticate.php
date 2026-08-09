<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StaffPortalAuthenticate
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if ((string) $request->session()->get('staff_id', '') === '') {
            return redirect()->route('login.portal')->with('errorMessage', 'ログインしてください。');
        }

        return $next($request);
    }
}

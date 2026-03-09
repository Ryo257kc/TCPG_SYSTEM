<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthenticate
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if (! $request->session()->get('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}

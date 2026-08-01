<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\ForceUtf8Response::class);
        $middleware->validateCsrfTokens(except: [
            'staff/login',
        ]);

        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuthenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            return redirect()
                ->back()
                ->with('error', 'ページを更新して再度お試しください');
        });
    })
    ->create();

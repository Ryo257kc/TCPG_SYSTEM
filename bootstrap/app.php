<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
            'staff.auth' => \App\Http\Middleware\StaffPortalAuthenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Illuminate\Session\TokenMismatchExceptionで登録すると発火しない。Handler::render()が
        // prepareException()でTokenMismatchExceptionをSymfony\...\HttpException(419)に変換して
        // からrenderViaCallbacks()でコールバックの型をチェックするため、変換後の型で
        // 登録する必要がある(2026-09-16、実際に419の生ページが出る不具合で発覚・再現確認済み)。
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            return redirect()
                ->back()
                ->with('error', 'ページを更新して再度お試しください');
        });
    })
    ->create();

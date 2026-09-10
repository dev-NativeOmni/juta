<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureApiTokenIsNotExpired;
use App\Support\ApiExceptionRenderer;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
        |--------------------------------------------------------------------------
        | Sanctum API Middleware
        |--------------------------------------------------------------------------
        */
        $middleware->statefulApi();

        /*
        |--------------------------------------------------------------------------
        | Middleware Aliases
        |--------------------------------------------------------------------------
        */
        $middleware->alias([
            'role' => CheckRole::class,
            'api.token.not_expired' => EnsureApiTokenIsNotExpired::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Sesi telah kadaluarsa, silakan muat ulang halaman.',
                ], 419);
            }

            if (! auth()->check()) {
                return redirect()->route('login')
                    ->with('error', 'Sesi Anda telah berakhir karena tidak aktif semalaman. Silakan login kembali.');
            }

            return redirect()->back()
                ->withInput($request->except('_token', 'password', 'password_confirmation'))
                ->with('error', 'Sesi halaman telah kadaluarsa. Data isian Anda telah dipulihkan, silakan coba kirim ulang.');
        });

        $exceptions->render(function (Throwable $exception, Request $request) {
            return ApiExceptionRenderer::render($exception, $request);
        });
    })
    ->create();

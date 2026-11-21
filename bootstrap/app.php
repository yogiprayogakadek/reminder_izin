<?php

use App\Http\Middleware\ApplyAppSettings;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands()
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            ApplyAppSettings::class,
        ]);

        $middleware->alias([
            'active' => EnsureUserIsActive::class,
        ]);

        $middleware->encryptCookies(except: [
            'siperiz_sidebar',
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/webhooks/waha',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

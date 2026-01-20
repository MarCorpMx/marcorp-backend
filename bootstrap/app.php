<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Mantienes tu CORS (esto ya lo tenías)
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);

        // IMPORTANTE: Esto es lo que necesita Sanctum
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

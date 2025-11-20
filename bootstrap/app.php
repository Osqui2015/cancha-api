<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
// Importamos nuestros middlewares (asegúrate de crearlos en el Paso 3)
use App\Http\Middleware\AdminRoleMiddleware;
use App\Http\Middleware\OwnerRoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php', // Esta línea se agrega automáticamente con install:api
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ---------------------------------------------------------
        // AQUÍ REGISTRAMOS LOS ALIAS PARA PROTEGER LAS RUTAS
        // ---------------------------------------------------------
        $middleware->alias([
            'auth.admin' => AdminRoleMiddleware::class,
            'auth.owner' => OwnerRoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

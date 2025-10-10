<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\{Exceptions, Middleware};

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Rate limiting personalizado para APIs de loteria
        $middleware->throttleApi('lottery-intensive:10,1'); // 10 requests por minuto para operações intensivas
        $middleware->throttleApi('lottery-moderate:30,1');  // 30 requests por minuto para operações moderadas
        $middleware->throttleApi('lottery-light:60,1');     // 60 requests por minuto para consultas leves
        $middleware->throttleApi('lottery-info:120,1');     // 120 requests por minuto para informações
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

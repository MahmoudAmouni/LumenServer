<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CorsMiddleware;
use App\Http\Middleware\RecruiterMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'recruiter' => RecruiterMiddleware::class,
        ]);
        
        $middleware->append(CorsMiddleware::class);
        
        $middleware->api(prepend: [
            CorsMiddleware::class,
        ]);
        
        $middleware->web(prepend: [
            CorsMiddleware::class,
        ]);
    })  
    ->withExceptions()->create();

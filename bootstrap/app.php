<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'recruiter' => \App\Http\Middleware\RecruiterMiddleware::class,
        ]);
        
        $middleware->append(\App\Http\Middleware\CorsMiddleware::class);
        
        $middleware->api(prepend: [
            \App\Http\Middleware\CorsMiddleware::class,
        ]);
        
        $middleware->web(prepend: [
            \App\Http\Middleware\CorsMiddleware::class,
        ]);
    })
    
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
            
            $response = response()->json([
                'status' => 'failure',
                'payload' => $e->getMessage() ?: 'An error occurred'
            ], $statusCode);
            
            return $response
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
        });
    })->create();

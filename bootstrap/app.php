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
    
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($e instanceof ValidationException) {
                $response = response()->json([
                    'status' => 'Validation failed',
                    'payload' => $e->errors()
                ], 422);
            } else {
                $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                
                $response = response()->json([
                    'status' => 'failure',
                    'payload' => $e->getMessage() ?: 'An error occurred'
                ], $statusCode);
            }
            
            return $response
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
        });
    })->create();

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => 'failure',
                'payload' => ['message' => 'Unauthorized']
            ], 401);
        }

        if ($user->type_id !== 1) {
            return response()->json([
                'status' => 'failure',
                'payload' => ['message' => 'Forbidden: Admin access required']
            ], 403);
        }

        return $next($request);
    }
}

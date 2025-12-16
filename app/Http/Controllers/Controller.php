<?php

namespace App\Http\Controllers;

use App\Trait\ResponseTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Exception;

abstract class Controller
{
    use ResponseTrait;

    /**
     * Handle exceptions consistently across all controllers
     */
    protected function handleException(Exception $e, string $operation = 'Operation'): JsonResponse
    {
        // Log full error details
        \Log::error("{$operation} failed", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        // Return appropriate response based on exception type
        if ($e instanceof ModelNotFoundException) {
            return $this->responseJSON('Resource not found', 'failure', 404);
        }

        if ($e instanceof ValidationException) {
            return $this->responseJSON($e->errors(), 'Validation failed', 422);
        }

        // In production, don't expose internal error details
        $message = app()->environment('production')
            ? "{$operation} failed. Please try again later."
            : "{$operation} failed: " . $e->getMessage();

        return $this->responseJSON($message, 'failure', 500);
    }
}

<?php

namespace App\Http\Controllers;

use App\Trait\ResponseTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Exception;

abstract class Controller
{
    use ResponseTrait;

    protected function handleException(Exception $e, string $operation = 'Operation'): JsonResponse
    {
        Log::error("{$operation} failed", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        if ($e instanceof ModelNotFoundException) {
            return $this->responseJSON('Resource not found', 'failure', 404);
        }

        if ($e instanceof ValidationException) {
            return $this->responseJSON($e->errors(), 'Validation failed', 422);
        }

        $message = app()->environment('production')
            ? "{$operation} failed. Please try again later."
            : "{$operation} failed: " . $e->getMessage();

        return $this->responseJSON($message, 'failure', 500);
    }
}

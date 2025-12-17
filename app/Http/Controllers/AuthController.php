<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {
    }

    public function register(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->all());
            return $this->responseJSON($result, 'success', 201);
        } catch (ValidationException $e) {
            return $this->responseJSON($e->errors(), 'Validation failed', 422);
        } catch (Exception $e) {
            return $this->responseJSON("Duplicate", 'Registration failed: ' . $e->getMessage(), 500);
        }
    }

    public function login(Request $request): JsonResponse
    {
        try {
            // Handle JSON requests - try multiple methods for compatibility
            $data = [];
            
            // First, try to get JSON data if method exists (Laravel)
            if (method_exists($request, 'json') && $request->json()) {
                $data = $request->json()->all();
            }
            
            // Fallback to regular request data (works in both Laravel and Lumen)
            if (empty($data)) {
                $data = $request->all();
            }
            
            // If still empty, try parsing raw content manually
            if (empty($data) && $request->getContent()) {
                $jsonData = json_decode($request->getContent(), true);
                if (json_last_error() === JSON_ERROR_NONE && $jsonData) {
                    $data = $jsonData;
                }
            }
            
            $result = $this->authService->login(
                $data['email'] ?? null,
                $data['password'] ?? null
            );
            return $this->responseJSON($result, 'success', 201);
        } catch (ValidationException $e) {
            return $this->responseJSON($e->errors(), 'Validation failed', 422);
        } catch (Exception $e) {
            return $this->responseJSON("LoginError", 'Login failed: ' . $e->getMessage(), 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout($request->user());
            return $this->responseJSON(['message' => 'Logged out successfully'], 'success', 201);
        } catch (Exception $e) {
            return $this->responseJSON("LogoutError", 'Logout failed: ' . $e->getMessage(), 500);
        }
    }

    public function displayError(): JsonResponse
    {
        return $this->responseJSON('Unauthorized', 'failure', 401);
    }
}

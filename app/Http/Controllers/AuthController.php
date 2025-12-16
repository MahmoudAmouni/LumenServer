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
            // Get all request data (works for both JSON and form-encoded)
            $data = $request->all();
            
            // If no data found, try parsing raw JSON body
            if (empty($data) && $request->getContent()) {
                $jsonData = json_decode($request->getContent(), true);
                if ($jsonData) {
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

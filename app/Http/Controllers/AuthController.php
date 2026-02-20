<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

class AuthController extends Controller
{

    private $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated());

            $user = $result['user'];
            $user->token = $result['token'];

            return $this->responseJSON($user, "User created successfully", 201);
        } catch (\Exception $e) {
            return $this->responseJSON($e->getMessage(), 'Validation failed', 422);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $credentials = $request->validated();
            
            $result = $this->authService->login($credentials);

            if (!$result) {
                return $this->responseJSON(null, "Invalid credentials", 401);
            }

            $user = $result['user'];
            $user->token = $result['token'];

            return $this->responseJSON($user, 'Login successful', 201);
        } catch (\Exception $e) {
            return $this->responseJSON($e->getMessage(), 'Failed to login', 422);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout();
            return $this->responseJSON(null, "Successfully logged out");
        } catch (\Exception $e) {
            return $this->responseJSON(null, "Failed to logout: " . $e->getMessage(), 500);
        }
    }

    public function displayError(): JsonResponse
    {
        return $this->responseJSON('Unauthorized', 'failure', 401);
    }
}

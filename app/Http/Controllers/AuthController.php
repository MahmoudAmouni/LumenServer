<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}


    public function register(Request $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());
        return $this->responseJSON($result, 201);
    }


    public function login(Request $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->authService->login($validated['email'], $validated['password']);

        return $this->responseJSON($result, 201);
    }

    
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());
        return $this->responseJSON(['message' => 'Logged out successfully'], 201);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $this->authService->getAuthenticatedUser($request->user());
        return $this->responseJSON($user, 201);
    }
}

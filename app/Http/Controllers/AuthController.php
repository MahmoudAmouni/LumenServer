<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string'],
                'email' => ['required', 'email', 'unique:users,email'],
                'password' => ['required', 'string', 'min:6'],
                'type_id' => ['required', 'integer'],
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $data = $validator->validated();
            $result = $this->authService->register($data);

            return $this->responseJSON($result, 'success', 201);

        } catch (Exception $e) {
            return $this->responseJSON("Duplicate", 'Registration failed: ' . $e->getMessage(), 500);
        }
    }

    public function login(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $data = $validator->validated();

            $result = $this->authService->login($data['email'], $data['password']);

            return $this->responseJSON($result, 'success', 201);

        } catch (ValidationException $e) {
            throw $e;

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

    public function user(Request $request): JsonResponse
    {
        try {
            $user = $this->authService->getAuthenticatedUser($request->user());
            return $this->responseJSON($user, 'success', 201);

        } catch (Exception $e) {
            return $this->responseJSON("UserError", 'Could not fetch user: ' . $e->getMessage(), 500);
        }
    }

    public function displayError(): JsonResponse
    {
        return $this->responseJSON(['message' => 'Unauthorized'], 'failure', 401);
    }
}
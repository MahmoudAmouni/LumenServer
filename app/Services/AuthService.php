<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{//

    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'type_id' => $data['type_id'],
            'company_id' => $data['company_id'] ?? null,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;
        $user->load('userType');

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;
        $user->load('userType');

        return [
            'user' => $user,
            'token' => $token,
        ];
    }


    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }


    public function getAuthenticatedUser(User $user): User
    {
        return $user->load('userType', 'company');
    }
}
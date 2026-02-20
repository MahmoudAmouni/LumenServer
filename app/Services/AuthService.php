<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function register(array $data): array
    {
        $user = new User;

        $user->type_id = $data['type_id'];
        $user->company_id = $data['company_id'] ?? null;
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = $data['password'];
        
        $user->save();

        $token = Auth::login($user);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function login(array $credentials): ?array
    {
        if (!$token = Auth::attempt($credentials)) {
            return null;
        }

        return [
            'user'  => Auth::user(),
            'token' => $token
        ];
    }

    public function logout(User $user): void
    {
        Auth::logout();
    }

    public function refresh(): array
    {
        return [
            'user'  => Auth::user(),
            'token' => Auth::refresh(),
        ];
    }
}

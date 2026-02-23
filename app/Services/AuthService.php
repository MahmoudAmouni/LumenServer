<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;

class AuthService{

    public function register(array $data): array{
        $user = new User;

        $user->type_id = $data['type_id'];
        $user->company_id = $data['company_id'] ?? null;
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = $data['password'];// hashing is handled by the model's cast
        
        $user->save();

        $token = Auth::login($user);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function login(array $credentials): ?array{

        if(!$token = Auth::attempt($credentials)){
            throw new Exception("Invalid credentials");
        }

        return [
            'user'  => Auth::user(),
            'token' => $token
        ];
    }

    public function logout(): void{
        Auth::logout();
    }

    public function refresh(): array{
        return [
            'user'  => Auth::user(),
            'token' => Auth::refresh(),
        ];
    }
}

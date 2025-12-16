<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserService
{
    public function createUser(array $data): User
    {
        $this->validateUserData($data, isUpdate: false);
        $typeId = $this->mapRoleToTypeId($data['role']);
        $user = $this->createUserRecord($data, $typeId);

        return $user->load(['userType', 'company']);
    }

    public function getAllUsers(): array
    {
        $users = User::with(['userType', 'company'])->get();

        return $this->formatUsersResponse($users);
    }

    public function getUsersByCompany(int $companyId): array
    {
        $users = User::where('company_id', $companyId)
            ->with(['userType', 'company'])
            ->get();

        return $this->formatUsersResponse($users);
    }

    public function deleteUser(int $id): void
    {
        $user = User::find($id);

        if (!$user) {
            throw new ModelNotFoundException("User not found", 404);
        }

        $user->delete();
    }

    private function validateUserData(array $data, bool $isUpdate): void
    {
        $rules = $isUpdate 
            ? $this->getUpdateValidationRules($data)
            : $this->getCreateValidationRules();

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    private function getCreateValidationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'company_id' => ['nullable', 'integer', 'exists:company_names,id'],
            'role' => ['required', 'string', 'in:recruiter,interviewer'],
        ];
    }

    private function getUpdateValidationRules(array $data): array
    {
        $baseRules = [
            'name' => ['string', 'max:255'],
            'email' => ['email', 'unique:users,email'],
            'password' => ['string', 'min:6'],
            'company_id' => ['nullable', 'integer', 'exists:company_names,id'],
            'role' => ['string', 'in:recruiter,interviewer'],
        ];

        return array_intersect_key($baseRules, $data);
    }

    private function mapRoleToTypeId(string $role): int
    {
        return $role === 'recruiter' ? 2 : 3;
    }

    private function createUserRecord(array $data, int $typeId): User
    {
        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = Hash::make($data['password']);
        $user->type_id = $typeId;
        $user->company_id = $data['company_id'] ?? null;
        $user->save();

        return $user;
    }

    private function formatUsersResponse($users): array
    {
        return $users->map(fn($user) => $this->formatSingleUser($user))->toArray();
    }

    private function formatSingleUser($user): array
    {
        return [
            'id' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'companyId' => $user->company_id ? (string) $user->company_id : null,
            'role' => $user->userType ? strtolower($user->userType->name) : null,
            'createdAt' => $user->created_at->toIso8601String(),
        ];
    }
}



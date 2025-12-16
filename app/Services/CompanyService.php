<?php

namespace App\Services;

use App\Models\CompanyName;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CompanyService
{
    public function createCompany(array $data): CompanyName
    {
        $this->validateCompanyData($data, isUpdate: false);
        $company = $this->createCompanyRecord($data);

        return $company->load('users');
    }

    public function getAllCompanies(): array
    {
        $companies = CompanyName::with('users')->get();

        return $this->formatCompaniesResponse($companies);
    }

    public function deleteCompany(int $id): void
    {
        $company = CompanyName::find($id);

        if (!$company) {
            throw new ModelNotFoundException("Company not found", 404);
        }

        $company->delete();
    }

    private function validateCompanyData(array $data, bool $isUpdate): void
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
        ];
    }

    private function getUpdateValidationRules(array $data): array
    {
        $baseRules = [
            'name' => ['string', 'max:255'],
        ];

        return array_intersect_key($baseRules, $data);
    }

    private function createCompanyRecord(array $data): CompanyName
    {
        $company = new CompanyName();
        $company->name = $data['name'];
        $company->save();

        return $company;
    }

    private function formatCompaniesResponse($companies): array
    {
        return $companies->map(fn($company) => $this->formatSingleCompany($company))->toArray();
    }

    private function formatCompanyUsers($users): array
    {
        return $users->map(fn($user) => $this->formatSingleCompanyUser($user))->toArray();
    }

    private function formatSingleCompany($company): array
    {
        return [
            'id' => (string) $company->id,
            'name' => $company->name,
            'createdAt' => $company->created_at->toIso8601String(),
            'users' => $this->formatCompanyUsers($company->users),
        ];
    }

    private function formatSingleCompanyUser($user): array
    {
        return [
            'id' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}

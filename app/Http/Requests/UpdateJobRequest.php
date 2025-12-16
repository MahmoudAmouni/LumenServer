<?php

namespace App\Http\Requests;

use App\Enums\JobStatus;
use Illuminate\Http\Request as BaseRequest;
use Illuminate\Validation\Rule;

class UpdateJobRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recruiter_id' => ['sometimes', 'integer', 'exists:users,id'],
            'company_id' => ['sometimes', 'integer', 'exists:company_names,id'],
            'jobTitle' => ['sometimes', 'string', 'max:255'],
            'jobDescription' => ['sometimes', 'string'],
            'jobLocation' => ['nullable', 'string', 'max:255'],
            'employmentType' => ['nullable', 'string', 'max:255'],
            'jobLevel' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string', Rule::in(JobStatus::values())],
        ];
    }
}


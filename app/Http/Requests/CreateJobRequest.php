<?php

namespace App\Http\Requests;

use App\Enums\JobStatus;
use App\Enums\SkillType;
use Illuminate\Http\Request as BaseRequest;
use Illuminate\Validation\Rule;

class CreateJobRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recruiter_id' => ['required', 'integer', 'exists:users,id'],
            'company_id' => ['required', 'integer', 'exists:company_names,id'],
            'jobTitle' => ['required', 'string', 'max:255'],
            'jobDescription' => ['required', 'string'],
            'jobLocation' => ['nullable', 'string', 'max:255'],
            'employmentType' => ['nullable', 'string', 'max:255'],
            'jobLevel' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(JobStatus::values())],
            'skills' => ['nullable', 'array'],
            'skills.*.name' => ['required', 'string', 'max:255'],
            'skills.*.type' => ['required', 'integer', Rule::in(SkillType::values())],
            'pipeline' => ['nullable', 'array'],
            'pipeline.*.name' => ['required', 'string', 'max:255'],
            'criteria' => ['nullable', 'array'],
            'criteria.*.name' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'recruiter_id.exists' => 'The selected recruiter does not exist.',
            'company_id.exists' => 'The selected company does not exist.',
            'status.in' => 'Status must be one of: ' . implode(', ', JobStatus::values()),
            'skills.*.type.in' => 'Skill type must be 1 (required) or 2 (nice to have).',
        ];
    }
}


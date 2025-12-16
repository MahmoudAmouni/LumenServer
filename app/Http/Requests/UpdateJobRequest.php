<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $jobStatusValues = class_exists(\App\Enums\JobStatus::class) ? \App\Enums\JobStatus::values() : [];

        $rules = [
            'recruiter_id' => ['sometimes', 'integer', 'exists:users,id'],
            'company_id' => ['sometimes', 'integer', 'exists:company_names,id'],
            'jobTitle' => ['sometimes', 'string', 'max:255'],
            'jobDescription' => ['sometimes', 'string'],
            'jobLocation' => ['nullable', 'string', 'max:255'],
            'employmentType' => ['nullable', 'string', 'max:255'],
            'jobLevel' => ['nullable', 'string', 'max:255'],
        ];

        if (!empty($jobStatusValues)) {
            $rules['status'] = ['sometimes', 'string', Rule::in($jobStatusValues)];
        } else {
            $rules['status'] = ['sometimes', 'string'];
        }

        return $rules;
    }
}


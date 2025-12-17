<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $jobStatusValues = class_exists(\App\Enums\JobStatus::class) ? \App\Enums\JobStatus::values() : [];
        $skillTypeValues = class_exists(\App\Enums\SkillType::class) ? \App\Enums\SkillType::values() : [];

        $rules = [
            'recruiter_id' => ['required', 'integer', 'exists:users,id'],
            'company_id' => ['required', 'integer', 'exists:company_names,id'],
            'jobTitle' => ['required', 'string', 'max:255'],
            'jobDescription' => ['required', 'string'],
            'jobLocation' => ['nullable', 'string', 'max:255'],
            'employmentType' => ['nullable', 'string', 'max:255'],
            'jobLevel' => ['nullable', 'string', 'max:255'],
            'skills' => ['nullable', 'array'],
            'skills.*.name' => ['required_with:skills', 'string', 'max:255'],
            'skills.*.type' => ['required_with:skills', 'integer'],
            'pipeline' => ['nullable', 'array'],
            'pipeline.*.name' => ['required_with:pipeline', 'string', 'max:255'],
            'criteria' => ['nullable', 'array'],
            'criteria.*.name' => ['required_with:criteria', 'string', 'max:255'],
        ];

        if (!empty($jobStatusValues)) {
            $rules['status'] = ['nullable', 'string', Rule::in($jobStatusValues)];
        } else {
            $rules['status'] = ['nullable', 'string'];
        }

        if (!empty($skillTypeValues)) {
            $rules['skills.*.type'][] = Rule::in($skillTypeValues);
        }

        return $rules;
    }

    public function messages(): array
    {
        $messages = [
            'recruiter_id.exists' => __('The selected recruiter does not exist.'),
            'company_id.exists' => __('The selected company does not exist.'),
        ];

        if (class_exists(\App\Enums\JobStatus::class)) {
            $messages['status.in'] = __('Status must be one of: :values', ['values' => implode(', ', \App\Enums\JobStatus::values())]);
        }

        if (class_exists(\App\Enums\SkillType::class)) {
            $messages['skills.*.type.in'] = __('Skill type must be one of: :values', ['values' => implode(', ', \App\Enums\SkillType::values())]);
        }

        return $messages;
    }
}


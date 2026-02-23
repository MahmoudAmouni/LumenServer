<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateJobRequest extends FormRequest{
    public function authorize(): bool{
        return true;
    }

    public function rules(): array{

    
        return [
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

    }
}


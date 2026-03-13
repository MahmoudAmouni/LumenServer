<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCandidateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'job_id' => 'required|integer|exists:jobs,id',
            'stage' => 'required|string|max:100',
            'recruiter_id' => 'nullable|integer|exists:users,id',
            'level' => 'nullable|string|max:100',
            'age' => 'nullable|integer|min:16|max:100',
            'phone_number' => 'nullable|string|max:30',
            'location' => 'nullable|string|max:255',
            'github_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'source' => 'nullable|string|max:100'
        ];
    }
}

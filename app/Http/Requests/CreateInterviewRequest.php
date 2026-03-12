<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateInterviewRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'job_id'         => ['required', 'integer', 'exists:jobs,id'],
            'candidate_id'   => ['required', 'integer', 'exists:candidates,id'],
            'interviewer_id' => ['required', 'integer', 'exists:users,id'],
            'notes'          => ['nullable', 'string'],
            'duration'       => ['nullable', 'integer', 'min:1'],
            'scheduled_at'   => ['required', 'date'],
            'status'         => ['sometimes', 'string', 'in:scheduled,completed,cancelled'],
        ];
    }

    public function messages(): array
    {
        return [
            'job_id.required'         => 'The job ID is required.',
            'job_id.exists'           => 'The selected job does not exist.',
            'candidate_id.required'   => 'The candidate ID is required.',
            'candidate_id.exists'     => 'The selected candidate does not exist.',
            'interviewer_id.required' => 'The interviewer ID is required.',
            'interviewer_id.exists'   => 'The selected interviewer does not exist.',
            'scheduled_at.required'   => 'The scheduled date and time is required.',
            'scheduled_at.date'       => 'Please provide a valid date and time.',
            'status.in'               => 'Status must be one of: scheduled, completed, cancelled.',
            'duration.integer'         => 'Duration must be an integer.',
        ];
    }
}

<?php

namespace App\Http\Requests;

class UpdateInterviewRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'job_id'         => ['sometimes', 'integer', 'exists:jobs,id'],
            'candidate_id'   => ['sometimes', 'integer', 'exists:candidates,id'],
            'interviewer_id' => ['sometimes', 'integer', 'exists:users,id'],
            'notes'          => ['nullable', 'string'],
            'duration'       => ['nullable', 'integer', 'min:1'],
            'scheduled_at'   => ['sometimes', 'date'],
            'status'         => ['sometimes', 'string', 'in:scheduled,completed,cancelled'],
        ];
    }
}

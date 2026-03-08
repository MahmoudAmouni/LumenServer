<?php

namespace App\Http\Requests;

class CreateScorecardRequest extends ApiRequest
{

    public function rules(): array
    {
        return [
            'candidate_id'   => ['required', 'integer', 'exists:candidates,id'],
            'job_id'         => ['required', 'integer', 'exists:jobs,id'],
            'interview_id'   => ['required', 'integer', 'exists:interviews,id'],
            'status'         => ['nullable', 'string', 'max:255'],
            'score_rate'     => ['nullable', 'integer', 'min:1', 'max:5'],
            'label_names'    => ['required', 'array', 'min:1'],
            'label_names.*'  => ['required', 'string', 'max:255', 'exists:score_labels,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'candidate_id.required'   => 'Candidate ID is required.',
            'candidate_id.exists'     => 'The selected candidate does not exist.',
            'job_id.required'         => 'Job ID is required.',
            'job_id.exists'           => 'The selected job does not exist.',
            'interview_id.required'   => 'Interview ID is required.',
            'interview_id.exists'     => 'The selected interview does not exist.',
            'label_names.required'    => 'At least one score label is required.',
            'label_names.array'       => 'Score labels must be provided as an array.',
            'label_names.min'         => 'At least one score label must be provided.',
            'label_names.*.required'  => 'Each label name is required.',
            'label_names.*.string'    => 'Each label name must be a string.',
            'label_names.*.exists'    => 'One or more label names do not exist in score labels.',
        ];
    }
}

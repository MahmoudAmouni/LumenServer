<?php

namespace App\Services;

use App\Models\Interview;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class InterviewService
{

    public function createInterview(array $data): Interview
    {
        $validator = Validator::make($data, [
            'candidate_id' => ['required', 'integer', 'exists:candidates,id'],
            'interviewer_id' => ['required', 'integer', 'exists:users,id'],
            'interview_type_id' => ['required', 'integer'],
            'notes' => ['nullable', 'string'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'scheduled_at' => ['required', 'date'],
            'status' => ['nullable', 'string', 'in:scheduled,completed,cancelled'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        $interview = new Interview();
        $interview->candidate_id = $validated['candidate_id'];
        $interview->interviewer_id = $validated['interviewer_id'];
        $interview->interview_type_id = $validated['interview_type_id'];
        $interview->notes = $validated['notes'] ?? null;
        $interview->duration = $validated['duration'] ?? null;
        $interview->scheduled_at = $validated['scheduled_at'];
        $interview->status = $validated['status'] ?? 'scheduled';
        $interview->save();

        return $interview;
    }
    public function updateInterview(int $id, array $data): Interview
    {
        $validator = Validator::make($data, [
            'candidate_id' => ['sometimes', 'integer', 'exists:candidates,id'],
            'interviewer_id' => ['sometimes', 'integer', 'exists:users,id'],
            'interview_type_id' => ['sometimes', 'integer'],
            'notes' => ['nullable', 'string'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'scheduled_at' => ['sometimes', 'date'],
            'status' => ['sometimes', 'string', 'in:pending,scheduled,completed,cancelled'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        $interview = Interview::findOrFail($id);

        $fillable = $interview->getFillable();

        foreach ($fillable as $field) {
            if (isset($validated[$field])) {
                $interview->$field = $validated[$field];
            }
        }

        $interview->save();

        return $interview;
    }
}

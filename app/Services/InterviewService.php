<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Interview;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class InterviewService
{
    public function createInterview(array $data): Interview
    {
        $validated = $this->validateInterviewData($data, isUpdate: false);
        $interview = $this->createInterviewRecord($validated);

        return $interview;
    }

    public function updateInterview(int $id, array $data): Interview
    {
        $validated = $this->validateInterviewData($data, isUpdate: true);
        $interview = Interview::findOrFail($id);
        $this->updateInterviewFields($interview, $validated);

        return $interview;
    }

    public function updateInterviewNotesByCandidateAndJob(int $candidateId, int $jobId, string $notes): Interview
    {
        $interview = $this->findOrCreateInterviewForCandidateAndJob($candidateId, $jobId);
        $interview->notes = $notes;
        $interview->save();

        return $interview;
    }

    private function validateInterviewData(array $data, bool $isUpdate): array
    {
        $rules = $isUpdate 
            ? $this->getUpdateValidationRules()
            : $this->getCreateValidationRules();

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    private function getCreateValidationRules(): array
    {
        return [
            'candidate_id' => ['required', 'integer', 'exists:candidates,id'],
            'interviewer_id' => ['required', 'integer', 'exists:users,id'],
            'interview_type_id' => ['required', 'integer'],
            'notes' => ['nullable', 'string'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'scheduled_at' => ['required', 'date'],
            'status' => ['nullable', 'string', 'in:scheduled,completed,cancelled'],
        ];
    }

    private function getUpdateValidationRules(): array
    {
        return [
            'candidate_id' => ['sometimes', 'integer', 'exists:candidates,id'],
            'interviewer_id' => ['sometimes', 'integer', 'exists:users,id'],
            'interview_type_id' => ['sometimes', 'integer'],
            'notes' => ['nullable', 'string'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'scheduled_at' => ['sometimes', 'date'],
            'status' => ['sometimes', 'string', 'in:pending,scheduled,completed,cancelled'],
        ];
    }

    private function createInterviewRecord(array $validated): Interview
    {
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

    private function updateInterviewFields(Interview $interview, array $validated): void
    {
        $fillable = $interview->getFillable();

        collect($fillable)->each(function ($field) use ($interview, $validated) {
            if (isset($validated[$field])) {
                $interview->$field = $validated[$field];
            }
        });

        $interview->save();
    }

    private function findOrCreateInterviewForCandidateAndJob(int $candidateId, int $jobId): Interview
    {
        $interview = Interview::whereHas('scorecards', function ($query) use ($jobId) {
            $query->where('job_id', $jobId);
        })
        ->where('candidate_id', $candidateId)
        ->first();

        if (!$interview) {
            $interview = $this->createDefaultInterview($candidateId);
        }

        return $interview;
    }

    private function createDefaultInterview(int $candidateId): Interview
    {
            $candidate = Candidate::find($candidateId);
        $interviewerId = $candidate && $candidate->recruiter_id ? $candidate->recruiter_id : 1;
            
            $interview = new Interview();
            $interview->candidate_id = $candidateId;
            $interview->interviewer_id = $interviewerId;
            $interview->interview_type_id = 1;
            $interview->scheduled_at = now();
            $interview->status = 'completed';
        $interview->save();

        return $interview;
    }
}

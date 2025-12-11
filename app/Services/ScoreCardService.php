<?php

namespace App\Services;

use App\Models\Scorecard;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ScorecardService
{
    public function __construct(
        private readonly ScoreLabelService $scoreLabelService
    ) {
    }

    public function getAllScorecards(?int $candidateId = null, ?int $interviewId = null, ?int $jobId = null, ?string $status = null)
    {
        $query = Scorecard::with(['candidate', 'interview', 'scorelabel', 'job']);

        if ($candidateId !== null) {
            $query->where('candidate_id', $candidateId);
        }

        if ($interviewId !== null) {
            $query->where('interview_id', $interviewId);
        }

        if ($jobId !== null) {
            $query->where('job_id', $jobId);
        }

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    public function getScorecardById(int $id)
    {
        $scorecard = Scorecard::with(['candidate', 'interview', 'scorelabel', 'job'])->find($id);
        if (!$scorecard) {
            throw new \Exception("Scorecard not found");
        }
        return $scorecard;
    }

    public function createScorecard(array $data): Scorecard
    {
        $validator = Validator::make($data, [
            'candidate_id' => ['required', 'integer', 'exists:candidates,id'],
            'interview_id' => ['required', 'integer', 'exists:interviews,id'],
            'job_id' => ['required', 'integer', 'exists:jobs,id'],
            'scorelabel_id' => ['sometimes', 'integer', 'exists:score_labels,id'],
            'scorelabel_name' => ['sometimes', 'string'],
            'scorelabel_max_score' => ['sometimes', 'integer'],
            'scorerate_id' => ['required', 'integer'],
            'status' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        if (!isset($data['scorelabel_id']) && isset($data['scorelabel_name']) && isset($data['scorelabel_max_score'])) {
            $scoreLabel = $this->scoreLabelService->createScoreLabel(
                $data['scorelabel_name'],
                $data['scorelabel_max_score']
            );
            $data['scorelabel_id'] = $scoreLabel->id;
        }

        if (!isset($data['scorelabel_id'])) {
            throw new \Exception("Either scorelabel_id or both scorelabel_name and scorelabel_max_score must be provided");
        }

        $scorecard = new Scorecard();
        $scorecard->candidate_id = $data['candidate_id'];
        $scorecard->interview_id = $data['interview_id'];
        $scorecard->job_id = $data['job_id'];
        $scorecard->scorelabel_id = $data['scorelabel_id'];
        $scorecard->scorerate_id = $data['scorerate_id'];
        $scorecard->status = $data['status'] ?? 'pending';
        $scorecard->save();

        return $scorecard->load(['candidate', 'interview', 'scorelabel', 'job']);
    }

    public function updateScorecard(int $id, array $data): Scorecard
    {
        $scorecard = Scorecard::find($id);
        if (!$scorecard) {
            throw new \Exception("Scorecard not found");
        }

        $validator = Validator::make($data, [
            'candidate_id' => ['sometimes', 'integer', 'exists:candidates,id'],
            'interview_id' => ['sometimes', 'integer', 'exists:interviews,id'],
            'job_id' => ['sometimes', 'integer', 'exists:jobs,id'],
            'scorelabel_id' => ['sometimes', 'integer', 'exists:score_labels,id'],
            'scorerate_id' => ['sometimes', 'integer'],
            'status' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $scorecard->candidate_id = $data['candidate_id'] ?? $scorecard->candidate_id;
        $scorecard->interview_id = $data['interview_id'] ?? $scorecard->interview_id;
        $scorecard->job_id = $data['job_id'] ?? $scorecard->job_id;
        $scorecard->scorelabel_id = $data['scorelabel_id'] ?? $scorecard->scorelabel_id;
        $scorecard->scorerate_id = $data['scorerate_id'] ?? $scorecard->scorerate_id;
        $scorecard->status = $data['status'] ?? $scorecard->status;
        $scorecard->save();

        return $scorecard->load(['candidate', 'interview', 'scorelabel', 'job']);
    }

    public function deleteScorecard(int $id)
    {
        $scorecard = Scorecard::find($id);
        if (!$scorecard) {
            throw new \Exception("Scorecard not found");
        }
        $scorecard->delete();
        return true;
    }
}
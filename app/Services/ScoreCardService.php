<?php

namespace App\Services;

use App\Models\Scorecard;
use App\Models\ScoreLabel;
use Exception;
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

        $this->applyScorecardFilters($query, $candidateId, $interviewId, $jobId, $status);

        return $query->get();
    }

    public function getScorecardById(int $id)
    {
        $scorecard = Scorecard::with(['candidate', 'interview', 'scorelabel', 'job'])->find($id);
        
        if (!$scorecard) {
            throw new Exception("Scorecard not found");
        }
        
        return $scorecard;
    }

    public function createScorecard(array $data)
    {
        $this->validateScorecardData($data, isUpdate: false);

        $scoreLabels = $data['score_labels'];
        $candidateId = $data['candidate_id'];
        $interviewId = $data['interview_id'];
        $jobId = $data['job_id'];
        $scoreRate = $data['score_rate'];
        $status = $data['status'] ?? 'pending';

        $existingLabels = $this->getOrCreateScoreLabels($scoreLabels);
        $this->createScorecardRecords($candidateId, $interviewId, $jobId, $scoreRate, $status, $scoreLabels, $existingLabels);

        return $this->getCreatedScorecards($candidateId, $interviewId, $jobId);
    }

    public function updateScorecard(int $id, array $data)
    {
        $scorecard = Scorecard::find($id);
        
        if (!$scorecard) {
            throw new Exception("Scorecard not found");
        }

        $this->validateScorecardData($data, isUpdate: true);
        $this->updateScorecardFields($scorecard, $data);

        return $scorecard->load(['candidate', 'interview', 'scorelabel', 'job']);
    }

    private function applyScorecardFilters($query, ?int $candidateId, ?int $interviewId, ?int $jobId, ?string $status): void
    {
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
    }

    private function validateScorecardData(array $data, bool $isUpdate): void
    {
        $rules = $isUpdate 
            ? $this->getUpdateValidationRules($data)
            : $this->getCreateValidationRules();

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    private function getCreateValidationRules(): array
    {
        return [
            'candidate_id' => ['required', 'integer', 'exists:candidates,id'],
            'interview_id' => ['required', 'integer', 'exists:interviews,id'],
            'job_id' => ['required', 'integer', 'exists:jobs,id'],
            'score_rate' => ['required', 'integer'],
            'status' => ['nullable', 'string', 'max:255'],
            'score_labels' => ['required', 'array'],
            'score_labels.*.name' => ['required', 'string'],
        ];
    }

    private function getUpdateValidationRules(array $data): array
    {
        $baseRules = [
            'candidate_id' => ['sometimes', 'integer', 'exists:candidates,id'],
            'interview_id' => ['sometimes', 'integer', 'exists:interviews,id'],
            'job_id' => ['sometimes', 'integer', 'exists:jobs,id'],
            'scorelabel_id' => ['sometimes', 'integer', 'exists:score_labels,id'],
            'score_rate' => ['sometimes', 'integer'],
            'status' => ['nullable', 'string', 'max:255'],
        ];

        return array_intersect_key($baseRules, $data);
    }

    private function getOrCreateScoreLabels(array $scoreLabels)
    {
        $now = now();
        $allScoreLabelsData = collect($scoreLabels)->mapWithKeys(function ($labelData) use ($now) {
            $labelName = $labelData['name'];
            return [
                $labelName => [
                    'name'       => $labelName,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ];
        })->all();

        $labelNames = array_keys($allScoreLabelsData);
        $existingLabels = ScoreLabel::whereIn('name', $labelNames)
            ->get()
            ->keyBy('name');

        $newLabelsData = collect($allScoreLabelsData)
            ->filter(function ($labelData, $name) use ($existingLabels) {
                return !isset($existingLabels[$name]);
            })
            ->values()
            ->all();

        if (!empty($newLabelsData)) {
            ScoreLabel::insert($newLabelsData);
            $newLabels = ScoreLabel::whereIn('name', array_column($newLabelsData, 'name'))
                ->get()
                ->keyBy('name');
            $existingLabels = $existingLabels->merge($newLabels);
        }

        return $existingLabels;
    }

    private function createScorecardRecords(
        int $candidateId,
        int $interviewId,
        int $jobId,
        int $scoreRate,
        string $status,
        array $scoreLabels,
        $existingLabels
    ): void {
        $now = now();

        $scorecardsData = collect($scoreLabels)
            ->map(function ($labelData) use ($candidateId, $interviewId, $jobId, $scoreRate, $status, $existingLabels, $now) {
                $labelName = $labelData['name'];
                $labelId = $existingLabels[$labelName]->id;

                return [
                    'candidate_id'  => $candidateId,
                    'interview_id'  => $interviewId,
                    'job_id'        => $jobId,
                    'scorelabel_id' => $labelId,
                    'score_rate'    => $scoreRate,
                    'status'        => $status,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            })
            ->all();

        Scorecard::insert($scorecardsData);
    }

    private function getCreatedScorecards(int $candidateId, int $interviewId, int $jobId)
    {
        return Scorecard::where('candidate_id', $candidateId)
            ->where('interview_id', $interviewId)
            ->where('job_id', $jobId)
            ->with(['candidate', 'interview', 'scorelabel', 'job'])
            ->get();
    }

    private function updateScorecardFields(Scorecard $scorecard, array $data): void
    {
        $scorecard->candidate_id = $data['candidate_id'] ?? $scorecard->candidate_id;
        $scorecard->interview_id = $data['interview_id'] ?? $scorecard->interview_id;
        $scorecard->job_id = $data['job_id'] ?? $scorecard->job_id;
        $scorecard->scorelabel_id = $data['scorelabel_id'] ?? $scorecard->scorelabel_id;
        $scorecard->score_rate = $data['score_rate'] ?? $scorecard->score_rate;
        $scorecard->status = $data['status'] ?? $scorecard->status;
        $scorecard->save();
    }
}

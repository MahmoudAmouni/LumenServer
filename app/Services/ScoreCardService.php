<?php

namespace App\Services;

use App\Models\Scorecard;
use App\Models\ScoreLabel;
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

     private function validateScorecardData(array $data, bool $isUpdate = false): void
    {
        $rules = [
            'candidate_id' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'exists:candidates,id'],
            'interview_id' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'exists:interviews,id'],
            'job_id' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'exists:jobs,id'],
            'scorerate_id' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
            'status' => ['nullable', 'string', 'max:255'],
        ];

        if (!$isUpdate) {
            $rules['score_labels'] = ['required', 'array'];
            $rules['score_labels.*.name'] = ['required', 'string'];
        } else {
            $rules['scorelabel_id'] = ['sometimes', 'integer', 'exists:score_labels,id'];
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    public function createScorecard(array $data)
    {
       $this->validateScorecardData($data, false);

        $scoreLabels = $data['score_labels'];
        $candidateId = $data['candidate_id'];
        $interviewId = $data['interview_id'];
        $jobId = $data['job_id'];
        $scorerateId = $data['scorerate_id'];
        $status = $data['status'] ?? 'pending';

        $allScoreLabelsData = [];
        foreach ($scoreLabels as $labelData) {
            $labelName = $labelData['name'];
            $allScoreLabelsData[$labelName] = [
                'name' => $labelName,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // get existing labels by name
        $labelNames = array_keys($allScoreLabelsData);
        $existingLabels = ScoreLabel::whereIn('name', $labelNames)
            ->get()
            ->keyBy('name');

        // create new labels if they don't exist
        $newLabelsData = [];
        foreach ($allScoreLabelsData as $name => $labelData) {
            if (!isset($existingLabels[$name])) {
                $newLabelsData[] = $labelData;
            }
        }

        if (!empty($newLabelsData)) {
            ScoreLabel::insert($newLabelsData);
            // refresh existing labels to include newly created ones
            $newLabels = ScoreLabel::whereIn('name', array_column($newLabelsData, 'name'))
                ->get()
                ->keyBy('name');
            $existingLabels = $existingLabels->merge($newLabels);
        }

        // create scorecards for each label
        $scorecardsData = [];
        $now = now();
        foreach ($scoreLabels as $labelData) {
            $labelName = $labelData['name'];
            $labelId = $existingLabels[$labelName]->id;
            
            $scorecardsData[] = [
                'candidate_id' => $candidateId,
                'interview_id' => $interviewId,
                'job_id' => $jobId,
                'scorelabel_id' => $labelId,
                'scorerate_id' => $scorerateId,
                'status' => $status,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Scorecard::insert($scorecardsData);

        // return all created scorecards
        return Scorecard::where('candidate_id', $candidateId)
            ->where('interview_id', $interviewId)
            ->where('job_id', $jobId)
            ->with(['candidate', 'interview', 'scorelabel', 'job'])
            ->get();
    }

    public function updateScorecard(int $id, array $data)
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
}
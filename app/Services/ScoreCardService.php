<?php

namespace App\Services;

use App\Models\Scorecard;
use App\Models\ScoreLabel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ScorecardService
{

    public function getScorecardsByInterviewId(int $interviewId)
    {
        return Scorecard::with('scorelabel')
            ->where('interview_id', $interviewId)
            ->get();
    }

    public function createScorecardsForInterview(array $data)
    {

        $candidateId = $data['candidate_id'];
        $jobId = $data['job_id'];
        $interviewId = $data['interview_id'];
        $labelNames = $data['label_names'];

        $existingLabels = ScoreLabel::whereIn('name', $labelNames)->get()->keyBy('name');

        if (count($existingLabels) !== count($labelNames)) {
            $missing = array_diff($labelNames, $existingLabels->keys()->toArray());
            throw new \Exception('Missing score labels: ' . implode(', ', $missing));
        }

        $now = now();
        $scorecardData = [];
        foreach ($labelNames as $name) {
            $labelId = $existingLabels[$name]->id;
            $scorecardData[] = [
                'candidate_id' => $candidateId,
                'job_id' => $jobId,
                'interview_id' => $interviewId,
                'scorelabel_id' => $labelId,
                'score_rate' => null,
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Scorecard::insert($scorecardData);

        return Scorecard::with('scorelabel')
            ->where('interview_id', $interviewId)
            ->get();
    }

    public function updateScorecardsFromAI(int $interviewId, array $scores)
    {

        $existing = Scorecard::where('interview_id', $interviewId)
            ->with('scorelabel')
            ->get()
            ->keyBy(fn($s) => optional($s->scorelabel)->name);

        foreach ($scores as $item) {
            $labelName = $item['label_name'];
            $scoreRate = $item['score_rate'];

            if ($existing->has($labelName)) {
                $scorecard = $existing[$labelName];
                $scorecard->score_rate = $scoreRate;
                $scorecard->status = 'completed';
                $scorecard->save();
            }
        }

        return Scorecard::with('scorelabel')
            ->where('interview_id', $interviewId)
            ->get();
    }
}
<?php

namespace App\Services;

use App\Models\Interview;
use App\Models\PipelineStages;
use Illuminate\Support\Facades\Http;

class InterviewN8nService
{
    public function __construct(){}

    public function sendPostInterviewWorkflow(int $interviewId, string $notes)
    {
        // load interview with necessary relationships
        $interview = Interview::with([
            'candidate',
            'scorecards.scorelabel',
            'scorecards.job.pipelines.pipelineStages.stage',
            'candidate.candidatePipelineStages' => function ($query) {
                $query->with(['pipelineStage', 'job.pipelines.pipelineStages.stage'])
                    ->latest('moved_at');
            }
        ])->find($interviewId);

        if (!$interview) {
            return [
                'success' => false,
                'error' => 'Interview not found',
            ];
        }

        // get labels from scorecards
        $labels = $interview->scorecards
            ->map(function ($scorecard) {
                return $scorecard->scorelabel ? $scorecard->scorelabel->name : null;
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // get candidate email
        $email = $interview->candidate->email ?? null;

        if (!$email) {
            return [
                'success' => false,
                'error' => 'Candidate email not found',
            ];
        }

        // get next pipeline stage
        $nextPipelineStage = $this->getNextPipelineStage($interview);

        // prepare data payload
        $payload = [
            'notes' => $notes,
            'labels' => $labels,
            'email' => $email,
            'next_stage' => $nextPipelineStage,
        ];

        // send to n8n
        $n8nUrl = config('services.n8n.summarize_notes_webhook');

        if (!$n8nUrl) {
            return [
                'success' => false,
                'error' => 'N8N webhook URL not configured',
            ];
        }

        $response = Http::timeout(180)
            ->post($n8nUrl, $payload);

        if (!$response->successful()) {
            return [
                'success' => false,
                'error' => 'N8N request failed',
                'status' => $response->status(),
                'body' => $response->body(),
            ];
        }

        return [
            'success' => true,
            'data' => $response->json(),
        ];
    }

    private function getNextPipelineStage(Interview $interview)
    {
        $candidate = $interview->candidate;
        
        if (!$candidate) {
            return null;
        }

        // try to get job from scorecards first
        $job = $interview->scorecards->first()?->job;
        
        // if no job from scorecards, get from candidate's most recent pipeline stage
        if (!$job) {
            $currentPipelineStage = $candidate->candidatePipelineStages
                ->sortByDesc('moved_at')
                ->first();
            
            $job = $currentPipelineStage?->job;
        }

        if (!$job) {
            return null;
        }

        // get the most recent pipeline stage for this candidate and job
        $currentPipelineStage = $candidate->candidatePipelineStages
            ->where('job_id', $job->id)
            ->sortByDesc('moved_at')
            ->first();

        if (!$currentPipelineStage) {
            return null;
        }

        $currentStage = $currentPipelineStage->pipelineStage;

        if (!$currentStage) {
            return null;
        }

        $pipeline = $job->pipelines->first();

        if (!$pipeline) {
            return null;
        }

        // get all pipeline stages by order
        $pipelineStages = PipelineStages::where('pipeline_id', $pipeline->id)
            ->with('stage')
            ->orderBy('order')
            ->get();

        // find current stage position
        $currentPosition = null;
        foreach ($pipelineStages as $index => $pipelineStage) {
            if ($pipelineStage->stage_id === $currentStage->id) {
                $currentPosition = $index;
                break;
            }
        }

        if ($currentPosition !== null && isset($pipelineStages[$currentPosition + 1])) {
            $nextPipelineStage = $pipelineStages[$currentPosition + 1];
            return $nextPipelineStage->stage->name ?? null;
        }

        return null;
    }
}


/* 
{
    "notes": "some notes from interviewer",
    "labels": ["label name 1", "label name 2", "label name 3"],
    "email": "email of this candidate",
    "next pipeline stage after interview": "stage name for this pipline" 
}


*/
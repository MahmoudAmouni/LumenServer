<?php

namespace App\Services;

use App\Models\Interview;
use App\Models\PipelineStages;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class InterviewN8nService
{
    private const HTTP_TIMEOUT_SECONDS = 180;

    public function sendPostInterviewWorkflow(int $interviewId, string $notes): array
    {
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
            throw new ModelNotFoundException('Interview not found');
        }

        $labels = $this->formatScorecardLabels($interview->scorecards);

        $email = $interview->candidate->email ?? null;

        if (!$email) {
            throw new InvalidArgumentException('Candidate email not found');
        }

        $nextPipelineStage = $this->getNextPipelineStage($interview);

        $payload = [
            'notes' => $notes,
            'labels' => $labels,
            'email' => $email,
            'next_stage' => $nextPipelineStage,
        ];

        $n8nUrl = config('services.n8n.summarize_notes_webhook');

        if (!$n8nUrl) {
            throw new RuntimeException('N8N webhook URL not configured');
        }

        $response = Http::timeout(self::HTTP_TIMEOUT_SECONDS)
            ->post($n8nUrl, $payload);

        if (!$response->successful()) {
            throw new RuntimeException(
                'N8N request failed: ' . $response->status() . ' - ' . $response->body()
            );
        }

        return $response->json() ?? [];
    }

    private function formatScorecardLabels($scorecards): array
    {
        return $scorecards
            ->map(function ($scorecard) {
                return $scorecard->scorelabel?->name;
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    private function getNextPipelineStage(Interview $interview): ?string
    {
        $candidate = $interview->candidate;
        
        if (!$candidate) {
            return null;
        }

        $job = $interview->scorecards->first()?->job;
        
        if (!$job) {
            $currentPipelineStage = $candidate->candidatePipelineStages
                ->sortByDesc('moved_at')
                ->first();
            
            $job = $currentPipelineStage?->job;
        }

        if (!$job) {
            return null;
        }

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

        $pipelineStages = PipelineStages::where('pipeline_id', $pipeline->id)
            ->with('stage')
            ->orderBy('order')
            ->get();

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
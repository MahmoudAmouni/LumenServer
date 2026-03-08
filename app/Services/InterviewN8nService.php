<?php

namespace App\Services;

use App\Models\Interview;
use App\Models\Candidate;
use App\Models\CandidatePipelineStage;
use App\Models\Job;
use App\Models\Scorecard;
use App\Services\ScorecardService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class InterviewN8nService
{
    public function __construct(
        private readonly ScorecardService $scorecardService
    ) {}

    public function summarizeAndScoreInterview(int $interviewId, string $notes)
    {
        $interview = Interview::with([
            'candidate',
            'candidate.candidateJobs.job',
            'scorecards.scorelabel'
        ])->find($interviewId);
        
        if (!$interview) {
            throw new \Exception("Interview not found");
        }
    
        $candidate = $interview->candidate;
        if (!$candidate) {
            throw new \Exception("Candidate not found for this interview");
        }
    
        $job = $candidate->candidateJobs->first()?->job;
        if (!$job) {
            throw new \Exception("Job not found for candidate");
        }
    
        // get existing scorecard labels from scorecards
        $labelNames = Scorecard::where('interview_id', $interviewId)
            ->with('scorelabel')
            ->get()
            ->pluck('scorelabel.name')
            ->filter()
            ->toArray();
    
        if (empty($labelNames)) {
            throw new \Exception("No scoring criteria found for this interview");
        }
    
        $payload = [
            'notes' => $notes,
            'labels' => $labelNames,
            'job_title'   => $job->title,
            'job_description' => $job->description,
        ];
    
        $n8nUrl = config('services.n8n.summarize_notes_webhook');
        if (!$n8nUrl) {
            throw new \Exception("n8n summarize_notes_webhook URL not configured");
        }
    
        $response = Http::timeout(120)->post($n8nUrl, $payload);
    
        if (!$response->successful()) {
            throw new \Exception("n8n AI scoring failed: " . $response->status() . " — " . $response->body());
        }
    
        $aiResult = $response->json();
        
        if (is_array($aiResult) && !isset($aiResult['summary']) && !isset($aiResult['scores'])) {
            if (count($aiResult) > 0 && is_array($aiResult[0])) {
                $aiResult = $aiResult[0];
            }
        }
        
        if (!is_array($aiResult)) {
            throw new \Exception("n8n AI scoring failed: Invalid or non-JSON response from webhook. Response body: " . $response->body());
        }
    
        $this->validateAiResponse($aiResult);
    
        $this->scorecardService->updateScorecardsFromAI($interviewId, $aiResult['scores']);
    
        return [
            'summary' => $aiResult['summary'],
            'scores' => $aiResult['scores'],
            'interview_id' => $interviewId,
            'candidate_id' => $candidate->id,
            'job_id' => $job->id,
        ];
    }

    public function sendNextStepEmail(int $candidatePipelineStageId)
    {
        // load stage + candidate + job
        $stageRecord = CandidatePipelineStage::with([
            'candidate',
            'pipelineStage',
            'job'
        ])->findOrFail($candidatePipelineStageId);

        $candidate = $stageRecord->candidate;
        $stage = $stageRecord->pipelineStage;
        $job = $stageRecord->job;

        if (!$candidate || !$stage || !$job) {
            throw new \Exception("Missing candidate, stage, or job data");
        }

        $stageName = strtolower($stage->name);
        $emailType = in_array($stageName, ['rejected', 'declined']) ? 'rejected' : 'proceed';

        $payload = [
            'candidate_email' => $candidate->email,
            'candidate_name' => $candidate->full_name,
            'job_title' => $job->title,
            'stage_name' => $stage->name,
            'email_type' => $emailType,
        ];

        // call n8n email workflow
        $n8nUrl = config('services.n8n.send_email_webhook');
        if (!$n8nUrl) {
            throw new \Exception("n8n send_email_webhook URL not configured");
        }

        $response = Http::timeout(30)->post($n8nUrl, $payload);

        if (!$response->successful()) {
            throw new \Exception("n8n email sending failed: " . $response->status() . " — " . $response->body());
        }

        return [
            'success' => true,
            'message' => 'Email sent successfully',
            'to' => $candidate->email,
            'stage' => $stage->name,
            'type' => $emailType,
        ];
    }

    private function validateAiResponse(array $data)
    {
        $validator = Validator::make($data, [
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.label_name' => ['required', 'string'],
            'scores.*.score_rate' => ['required', 'integer', 'min:1', 'max:5'],
            'summary' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
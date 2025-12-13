<?php

namespace App\Services;

use App\Models\Pipeline;
use App\Models\CandidatePipelineStage;
use App\Models\Candidate;
use App\Models\Job;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CandidateService
{
    /**
     * Get candidates by job ID and pipeline stage ID
     * Returns candidates in a specific stage for a specific job
     * 
     * @param int $jobId The job ID
     * @param int $pipelineStageId The pipeline stage ID
     * @return \Illuminate\Support\Collection Formatted candidate data with scorecards
     */
    public function getCandidatesByJobIdAndPipelineStage(int $jobId, int $pipelineStageId){
        $candidatePipelineStages = CandidatePipelineStage::with([
            'candidate',
            'candidate.scorecards' => function ($query) use ($jobId) {
                $query->where('job_id', $jobId)->with('scorelabel');
            },
            'pipelineStage',
            'job'
        ])
        ->where('job_id', $jobId)
        ->where('pipeline_stage_id', $pipelineStageId)
        ->orderBy('moved_at', 'desc')
        ->get();

        return $candidatePipelineStages->map(function ($item) {
            $candidate = $item->candidate;
            $pipelineStage = $item->pipelineStage;
            $scorecards = $candidate->scorecards ?? collect([]);

            return [
                'candidate_pipeline_stage_id' => $item->id,
                'candidate' => [
                    'id' => $candidate->id,
                    'name' => $candidate->full_name,
                    'email' => $candidate->email,
                ],
                'pipeline_stage' => [
                    'id' => $pipelineStage->id ?? null,
                    'name' => $pipelineStage->name ?? null,
                ],
                'scorecards' => $scorecards->map(function ($scorecard) {
                    return [
                        'scorerate_id' => $scorecard->scorerate_id,
                        'scorelabel' => $scorecard->scorelabel->name ?? null,
                        'max_score' => $scorecard->scorelabel->max_score ?? null,
                    ];
                })->toArray(),
                'moved_at' => $item->moved_at,
                'notes' => $item->notes,
            ];
        });
    }

    
    public function getCandidateProfile(int $candidateId, ?int $jobId = null)
    {
        $candidate = Candidate::with([
            'recruiter',
            'candidatePipelineStages' => function ($query) use ($jobId) {
                if ($jobId !== null) {
                    $query->where('job_id', $jobId);
                }
                $query->with(['pipelineStage', 'job.company'])->latest('moved_at');
            },
            'candidateJobs' => function ($query) use ($jobId) {
                if ($jobId !== null) {
                    $query->where('job_id', $jobId);
                }
                $query->with(['job.company']);
            },
            'scorecards' => function ($query) use ($jobId) {
                if ($jobId !== null) {
                    $query->where('job_id', $jobId);
                }
                $query->with('scorelabel');
            },
            'interviews' => function ($query) use ($jobId) {
                if ($jobId !== null) {
                    // Get interviews related to this job through scorecards
                    $query->whereHas('scorecards', function ($q) use ($jobId) {
                        $q->where('job_id', $jobId);
                    });
                }
                $query->with('interviewer');
            },
            'offers' => function ($query) use ($jobId) {
                if ($jobId !== null) {
                    $query->where('job_id', $jobId);
                }
                $query->with('job');
            }
        ])->find($candidateId);

        if (!$candidate) {
            throw new ModelNotFoundException("Candidate not found", 404);
        }

        // Get current job application (most recent or specific job)
        $currentPipelineStage = null;
        $currentJob = null;
        
        if ($jobId !== null) {
            $currentPipelineStage = $candidate->candidatePipelineStages
                ->where('job_id', $jobId)
                ->sortByDesc('moved_at')
                ->first();
            $currentJob = $candidate->candidateJobs->where('job_id', $jobId)->first();
        } else {
            // Get most recent pipeline stage
            $currentPipelineStage = $candidate->candidatePipelineStages
                ->sortByDesc('moved_at')
                ->first();
            if ($currentPipelineStage) {
                $currentJob = $candidate->candidateJobs
                    ->where('job_id', $currentPipelineStage->job_id)
                    ->first();
            }
        }

        $job = $currentPipelineStage ? $currentPipelineStage->job : null;
        $company = $job ? $job->company : null;

        return [
            ...$candidate->only(['id', 'full_name', 'email', 'phone_number', 'age', 'location', 'level', 'github_url', 'linkedin_url', 'cv_path']),
            'recruiter' => $candidate->recruiter ? ['id' => $candidate->recruiter->id, 'name' => $candidate->recruiter->name, 'email' => $candidate->recruiter->email] : null,
            'current_application' => $currentPipelineStage ? ['candidate_pipeline_stage_id' => $currentPipelineStage->id, 'job' => [...$job->only(['id', 'title', 'description', 'location', 'employment_type', 'level']), 'company' => $company ? $company->name : null], 'moved_at' => $currentPipelineStage->moved_at, 'notes' => $currentPipelineStage->notes] : null,
            'interviews' => $candidate->interviews->map(fn($interview) => [...$interview->only(['id', 'candidate_id', 'interviewer_id', 'interview_type_id', 'notes', 'duration', 'scheduled_at', 'status']), 'interviewer' => $interview->interviewer ? ['id' => $interview->interviewer->id, 'name' => $interview->interviewer->name, 'email' => $interview->interviewer->email] : null]),
            'scorecards' => $candidate->scorecards->map(fn($scorecard) => [...$scorecard->only(['id', 'candidate_id', 'job_id', 'interview_id', 'scorerate_id', 'scorelabel_id', 'status']), 'scorelabel' => $scorecard->scorelabel ? ['id' => $scorecard->scorelabel->id, 'name' => $scorecard->scorelabel->name, 'max_score' => $scorecard->scorelabel->max_score] : null]),
        ];
    }
}


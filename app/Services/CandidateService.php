<?php

namespace App\Services;

use App\Models\CandidatePipelineStage;
use App\Models\Candidate;
use App\Models\CandidateJob;
use App\Models\Stage;
use App\Models\Job;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CandidateService
{

    private function toIso8601String($date): ?string
    {
        if (!$date) {
            return null;
        }
        if (is_string($date)) {
            try {
                return (new \Carbon\Carbon($date))->toIso8601String();
            } catch (\Exception $e) {
                return $date; 
            }
        }
        if (method_exists($date, 'toIso8601String')) {
            return $date->toIso8601String();
        }
        return null;
    }

    public function getCandidatesByJobIdAndPipelineStage(int $jobId, $pipelineStageIdOrName = null)
    {
        $validator = Validator::make([
            'job_id' => $jobId,
        ], [
            'job_id' => ['required', 'integer', 'exists:jobs,id'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $query = CandidatePipelineStage::with([
            'candidate',
            'candidate.scorecards' => function ($query) use ($jobId) {
                $query->where('job_id', $jobId)->with('scorelabel');
            },
            'pipelineStage',
            'job'
        ])
        ->where('job_id', $jobId);

        if ($pipelineStageIdOrName !== null) {
            if (is_numeric($pipelineStageIdOrName)) {
                $query->where('pipeline_stage_id', (int) $pipelineStageIdOrName);
            } else {
                $normalizedStageName = strtolower(trim($pipelineStageIdOrName));
                $stage = Stage::whereRaw('LOWER(TRIM(name)) = ?', [$normalizedStageName])->first();
                
                if ($stage) {
                    $query->where('pipeline_stage_id', $stage->id);
                } else {
                    \Log::warning("Stage not found: {$pipelineStageIdOrName}, normalized: {$normalizedStageName}");
                    return collect([]);
                }
            }
        }

        $candidatePipelineStages = $query->orderBy('moved_at', 'desc')->get();

        return $candidatePipelineStages->map(function ($item) {
            $candidate = $item->candidate;
            $pipelineStage = $item->pipelineStage;
            $scorecards = $candidate->scorecards ?? collect([]);

            $stageName = $pipelineStage ? ucfirst(strtolower($pipelineStage->name)) : null;

            return [
                'id' => (string) $candidate->id,
                'name' => $candidate->full_name,
                'email' => $candidate->email,
                'stage' => $stageName,
                'jobId' => (string) $item->job_id,
                'age' => $candidate->age,
                'location' => $candidate->location,
                'level' => $candidate->level,
                'linkedin' => $candidate->linkedin_url,
                'github' => $candidate->github_url,
                'phone' => $candidate->phone_number,
                'recruiter' => $candidate->recruiter ? $candidate->recruiter->name : null,
                'recruiterEmail' => $candidate->recruiter ? $candidate->recruiter->email : null,
                'internalNotes' => $item->notes,
                'appliedDate' => $this->toIso8601String($item->moved_at),
                'candidate_pipeline_stage_id' => $item->id,
                'scorecards' => $scorecards->map(function ($scorecard) {
                    return [
                        'score_rate' => $scorecard->score_rate,
                        'scorelabel' => $scorecard->scorelabel->name ?? null
                    ];
                })->toArray(),
            ];
        });
    }

    public function getCandidateProfile(int $candidateId, ?int $jobId = null)
    {
        $validator = Validator::make([
            'candidate_id' => $candidateId,
            'job_id' => $jobId,
        ], [
            'candidate_id' => ['required', 'integer'],
            'job_id' => ['nullable', 'integer', 'exists:jobs,id'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

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

        $currentPipelineStage = $this->getCurrentPipelineStage($candidate, $jobId);
        $job = $currentPipelineStage ? $currentPipelineStage->job : null;
        $company = $job ? $job->company : null;
        $stageName = $currentPipelineStage && $currentPipelineStage->pipelineStage 
            ? ucfirst(strtolower($currentPipelineStage->pipelineStage->name)) 
            : null;

        $timeline = $candidate->candidatePipelineStages
            ->where('job_id', $jobId ?? $currentPipelineStage?->job_id)
            ->sortBy('moved_at')
            ->map(function ($cps) {
                $stageName = $cps->pipelineStage ? ucfirst(strtolower($cps->pipelineStage->name)) : 'Unknown';
                return [
                    'date' => $this->toIso8601String($cps->moved_at) ?? now()->toIso8601String(),
                    'event' => $cps->moved_at ? "Moved to {$stageName} Stage" : "Application Received"
                ];
            })
            ->values()
            ->toArray();

        return [
            'id' => (string) $candidate->id,
            'name' => $candidate->full_name,
            'email' => $candidate->email,
            'phone' => $candidate->phone_number,
            'age' => $candidate->age,
            'location' => $candidate->location,
            'level' => $candidate->level,
            'linkedin' => $candidate->linkedin_url,
            'github' => $candidate->github_url,
            'stage' => $stageName,
            'jobId' => $job ? (string) $job->id : null,
            'recruiter' => $candidate->recruiter ? $candidate->recruiter->name : null,
            'recruiterEmail' => $candidate->recruiter ? $candidate->recruiter->email : null,
            'internalNotes' => $currentPipelineStage ? $currentPipelineStage->notes : null,
            'coverLetter' => null, 
            'source' => null, 
            'appliedDate' => $this->toIso8601String($currentPipelineStage?->moved_at) ?? now()->toIso8601String(),
            'attachments' => $candidate->cv_path ? [$candidate->cv_path] : [],
            'timeline' => $timeline,
            'interviewNotes' => $candidate->interviews->first() ? $candidate->interviews->first()->notes : null,
            'current_application' => $currentPipelineStage ? [
                'candidate_pipeline_stage_id' => $currentPipelineStage->id,
                'job' => $job ? [
                    'id' => $job->id,
                    'title' => $job->title,
                    'description' => $job->description,
                    'location' => $job->location,
                    'employment_type' => $job->employment_type,
                    'level' => $job->level,
                    'company' => $company ? $company->name : null
                ] : null,
                'moved_at' => $this->toIso8601String($currentPipelineStage->moved_at),
                'notes' => $currentPipelineStage->notes
            ] : null,
            'interviews' => $candidate->interviews->map(fn($interview) => [
                'id' => $interview->id,
                'candidate_id' => $interview->candidate_id,
                'interviewer_id' => $interview->interviewer_id,
                'interview_type_id' => $interview->interview_type_id,
                'notes' => $interview->notes,
                'duration' => $interview->duration,
                'scheduled_at' => $interview->scheduled_at ? $interview->scheduled_at->toIso8601String() : null,
                'status' => $interview->status,
                'interviewer' => $interview->interviewer ? [
                    'id' => $interview->interviewer->id,
                    'name' => $interview->interviewer->name,
                    'email' => $interview->interviewer->email
                ] : null
            ]),
            'scorecards' => $candidate->scorecards->map(fn($scorecard) => [
                'id' => $scorecard->id,
                'candidate_id' => $scorecard->candidate_id,
                'job_id' => $scorecard->job_id,
                'interview_id' => $scorecard->interview_id,
                'score_rate' => $scorecard->score_rate,
                'scorelabel_id' => $scorecard->scorelabel_id,
                'status' => $scorecard->status,
                'scorelabel' => $scorecard->scorelabel ? [
                    'id' => $scorecard->scorelabel->id,
                    'name' => $scorecard->scorelabel->name
                ] : null
            ]),
        ];
    }

    public function createCandidate(array $data)
    {
        $validator = Validator::make($data, [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'job_id' => ['required', 'integer', 'exists:jobs,id'],
            'stage' => ['nullable', 'string'],
            'recruiter_id' => ['nullable', 'integer', 'exists:users,id'],
            'level' => ['nullable', 'string', 'max:255'],
            'age' => ['nullable', 'integer', 'min:18', 'max:100'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'github_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }


        $recruiterId = $data['recruiter_id'] ?? 1; // Should come localStorage

        //validation email
        $candidate = Candidate::where('email', $data['email'])->first();

        if (!$candidate) {
            $candidate = Candidate::create([
                'full_name'    => $data['full_name'],
                'email'        => $data['email'],
                'recruiter_id' => $recruiterId,
                'level'        => $data['level'] ?? 'junior',
                'age'          => $data['age'] ?? null,
                'phone_number' => $data['phone_number'] ?? null,
                'location'     => $data['location'] ?? null,
                'github_url'   => $data['github_url'] ?? null,
                'linkedin_url' => $data['linkedin_url'] ?? null,
            ]);
        } else {
            // Update existing candidate with new data (if provided)
            $updateData = [];
            if (isset($data['full_name'])) $updateData['full_name'] = $data['full_name'];
            if (isset($data['age'])) $updateData['age'] = $data['age'];
            if (isset($data['phone_number'])) $updateData['phone_number'] = $data['phone_number'];
            if (isset($data['location'])) $updateData['location'] = $data['location'];
            if (isset($data['level'])) $updateData['level'] = $data['level'];
            if (isset($data['github_url'])) $updateData['github_url'] = $data['github_url'];
            if (isset($data['linkedin_url'])) $updateData['linkedin_url'] = $data['linkedin_url'];
            
            if (!empty($updateData)) {
                $candidate->update($updateData);
            }
        }

        // Find stage by name (default to "applied" - lowercase to match DB)
        $stageName = strtolower($data['stage'] ?? 'applied');
        
        // First, try to find the stage from the job's pipeline (most accurate)
        $job = Job::with(['pipelines.pipelineStages.stage'])->find($data['job_id']);
        $stage = null;
        
        if ($job && $job->pipelines->isNotEmpty()) {
            $pipeline = $job->pipelines->first();
            if ($pipeline && $pipeline->pipelineStages->isNotEmpty()) {
                // Try to find the stage by name in this job's pipeline
                foreach ($pipeline->pipelineStages as $pipelineStage) {
                    if ($pipelineStage->stage && strtolower($pipelineStage->stage->name) === $stageName) {
                        $stage = $pipelineStage->stage;
                        break;
                    }
                }
                
                // If not found by name, use the first stage (usually "applied")
                if (!$stage) {
                    $firstPipelineStage = $pipeline->pipelineStages->sortBy('order')->first();
                    if ($firstPipelineStage && $firstPipelineStage->stage) {
                        $stage = $firstPipelineStage->stage;
                    }
                }
            }
        }
        
        // Fallback: try to find stage globally by name
        if (!$stage) {
            $stage = Stage::whereRaw('LOWER(name) = ?', [$stageName])->first();
        }
        
        // Last resort: try "applied" globally
        if (!$stage) {
            $stage = Stage::whereRaw('LOWER(name) = ?', ['applied'])->first();
        }
        
        if (!$stage) {
            throw new ModelNotFoundException("Stage '{$stageName}' not found for job {$data['job_id']}", 404);
        }

        // Check if stage already exists for this candidate and job
        $candidatePipelineStage = CandidatePipelineStage::where('candidate_id', $candidate->id)
            ->where('job_id', $data['job_id'])
            ->first();

        if (!$candidatePipelineStage) {
            // Create candidate pipeline stage if it doesn't exist
            $candidatePipelineStage = CandidatePipelineStage::create([
                'candidate_id' => $candidate->id,
                'job_id' => $data['job_id'],
                'pipeline_stage_id' => $stage->id,
                'moved_at' => now(),
            ]);
        } else {
            // Update existing pipeline stage to the new stage
            $candidatePipelineStage->update([
                'pipeline_stage_id' => $stage->id,
                'moved_at' => now(),
            ]);
        }

        // Check if candidate_jobs entry already exists
        $candidateJob = CandidateJob::where('candidate_id', $candidate->id)
            ->where('job_id', $data['job_id'])
            ->first();

        if (!$candidateJob) {
            // Create jobs entry if it doesn't exist
            CandidateJob::create([
                'candidate_id' => $candidate->id,
                'job_id' => $data['job_id'],
                'recruiter_id' => $recruiterId,
                'source' => $data['source'] ?? null, 
            ]);
        } else {
            // Update existing entry with new source if provided
            if (isset($data['source'])) {
                $candidateJob->update(['source' => $data['source']]);
            }
        }

        return $candidatePipelineStage->load(['candidate', 'pipelineStage', 'job']);
    }

    public function updateCandidateStage(int $candidateId, int $jobId, $stageNameOrId)
    {
        $validator = Validator::make([
            'candidate_id' => $candidateId,
            'job_id' => $jobId,
        ], [
            'candidate_id' => ['required', 'integer', 'exists:candidates,id'],
            'job_id' => ['required', 'integer', 'exists:jobs,id'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Find stage by name or ID
        if (is_numeric($stageNameOrId)) {
            $stage = Stage::find((int) $stageNameOrId);
        } else {
            $stage = Stage::whereRaw('LOWER(name) = ?', [strtolower($stageNameOrId)])->first();
        }

        if (!$stage) {
            throw new ModelNotFoundException("Stage not found", 404);
        }

        // Create or update candidate pipeline stage
        $candidatePipelineStage = CandidatePipelineStage::updateOrCreate(
            [
                'candidate_id' => $candidateId,
                'job_id' => $jobId,
            ],
            [
                'pipeline_stage_id' => $stage->id,
                'moved_at' => now(),
            ]
        );

        return $candidatePipelineStage->load(['candidate', 'pipelineStage', 'job']);
    }

    private function getCurrentPipelineStage(Candidate $candidate, ?int $jobId = null)
    {
        if ($jobId !== null) {
            return $candidate->candidatePipelineStages
                ->where('job_id', $jobId)
                ->sortByDesc('moved_at')
                ->first();
        }

        return $candidate->candidatePipelineStages
            ->sortByDesc('moved_at')
            ->first();
    }
}

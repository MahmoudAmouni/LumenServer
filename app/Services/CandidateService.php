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
    public function getCandidatesByJobIdAndPipelineStage(
        int $jobId,
        $pipelineStageIdOrName = null,
        ?int $perPage = null,
        int $page = 1
    )
    {
        $this->validateJobId($jobId);

        $query = $this->buildCandidatesQuery($jobId);
        $this->applyStageFilter($query, $pipelineStageIdOrName);

        if ($perPage !== null) {
            $candidatePipelineStages = $query
                ->orderBy('moved_at', 'desc')
                ->forPage($page, $perPage)
                ->get();
        } else {
            $candidatePipelineStages = $query->orderBy('moved_at', 'desc')->get();
        }

        return $this->formatCandidatesResponse($candidatePipelineStages, $jobId);
    }

    public function getCandidateProfile(int $candidateId, ?int $jobId = null)
    {
        $this->validateCandidateProfileInput($candidateId, $jobId);

        $candidate = $this->loadCandidateWithRelations($candidateId, $jobId);

        if (!$candidate) {
            throw new ModelNotFoundException("Candidate not found", 404);
        }

        $currentPipelineStage = $this->getCurrentPipelineStage($candidate, $jobId);
        $job = $currentPipelineStage ? $currentPipelineStage->job : null;
        $company = $job ? $job->company : null;
        $stageName = $this->formatStageName($currentPipelineStage);
        $timeline = $this->buildTimeline($candidate, $jobId, $currentPipelineStage);

        return $this->formatCandidateProfileResponse(
            $candidate,
            $currentPipelineStage,
            $job,
            $company,
            $stageName,
            $timeline
        );
    }

    public function createCandidate(array $data)
    {
        $this->validateCandidateData($data, isUpdate: false);

        $recruiterId = $data['recruiter_id'] ?? 1;
        $candidate = $this->getOrCreateCandidate($data, $recruiterId);
        $stage = $this->findStageForJob($data['job_id'], $data['stage'] ?? 'applied');
        $candidatePipelineStage = $this->createOrUpdatePipelineStage($candidate->id, $data['job_id'], $stage->id);
        $this->createOrUpdateCandidateJob($candidate->id, $data['job_id'], $recruiterId, $data['source'] ?? null);

        return $candidatePipelineStage->load(['candidate', 'pipelineStage', 'job']);
    }

    public function updateCandidateStage(int $candidateId, int $jobId, $stageNameOrId)
    {
        $this->validateUpdateStageInput($candidateId, $jobId);

        $stage = $this->findStageByIdOrName($stageNameOrId);

        if (!$stage) {
            throw new ModelNotFoundException("Stage not found", 404);
        }

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

    private function validateJobId(int $jobId): void
    {
        $data = ['job_id' => $jobId];
        $rules = $this->getJobIdValidationRules();

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    private function getJobIdValidationRules(): array
    {
        return [
            'job_id' => ['required', 'integer', 'exists:jobs,id'],
        ];
    }

    private function buildCandidatesQuery(int $jobId)
    {
        return CandidatePipelineStage::with([
            'candidate',
            'candidate.scorecards' => function ($query) use ($jobId) {
                $query->where('job_id', $jobId)->with('scorelabel');
            },
            'pipelineStage',
            'job'
        ])->where('job_id', $jobId);
    }

    private function applyStageFilter($query, $pipelineStageIdOrName): void
    {
        if ($pipelineStageIdOrName === null) {
            return;
        }

            if (is_numeric($pipelineStageIdOrName)) {
                $query->where('pipeline_stage_id', (int) $pipelineStageIdOrName);
            return;
        }

                $normalizedStageName = strtolower(trim($pipelineStageIdOrName));
                $stage = Stage::whereRaw('LOWER(TRIM(name)) = ?', [$normalizedStageName])->first();
                
                if ($stage) {
                    $query->where('pipeline_stage_id', $stage->id);
                } else {
                    \Log::warning("Stage not found: {$pipelineStageIdOrName}, normalized: {$normalizedStageName}");
        }
    }

    private function formatCandidatesResponse($candidatePipelineStages, int $jobId)
    {
        return $candidatePipelineStages->map(function ($item) {
            return $this->formatCandidatePipelineStageItem($item);
        });
    }

    private function formatCandidatePipelineStageItem($item): array
    {
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
            'scorecards' => $scorecards->map(fn($scorecard) => $this->formatSingleScorecardForList($scorecard))->toArray(),
        ];
    }

    private function validateCandidateProfileInput(int $candidateId, ?int $jobId): void
    {
        $data = [
            'candidate_id' => $candidateId,
            'job_id' => $jobId,
        ];
        $rules = $this->getCandidateProfileValidationRules();

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    private function getCandidateProfileValidationRules(): array
    {
        return [
            'candidate_id' => ['required', 'integer'],
            'job_id' => ['nullable', 'integer', 'exists:jobs,id'],
        ];
    }

    private function loadCandidateWithRelations(int $candidateId, ?int $jobId)
    {
        return Candidate::with([
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
    }

    private function formatStageName($currentPipelineStage): ?string
    {
        if (!$currentPipelineStage || !$currentPipelineStage->pipelineStage) {
            return null;
        }

        return ucfirst(strtolower($currentPipelineStage->pipelineStage->name));
    }

    private function buildTimeline(Candidate $candidate, ?int $jobId, $currentPipelineStage): array
    {
        return $candidate->candidatePipelineStages
            ->where('job_id', $jobId ?? $currentPipelineStage?->job_id)
            ->sortBy('moved_at')
            ->map(fn($cps) => $this->formatTimelineItem($cps))
            ->values()
            ->toArray();
    }

    private function formatTimelineItem($cps): array
    {
        $stageName = $cps->pipelineStage ? ucfirst(strtolower($cps->pipelineStage->name)) : 'Unknown';
        return [
            'date' => $this->toIso8601String($cps->moved_at) ?? now()->toIso8601String(),
            'event' => $cps->moved_at ? "Moved to {$stageName} Stage" : "Application Received"
        ];
    }

    private function formatCandidateProfileResponse(
        Candidate $candidate,
        $currentPipelineStage,
        $job,
        $company,
        ?string $stageName,
        array $timeline
    ): array {
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
            'current_application' => $this->formatCurrentApplication($currentPipelineStage, $job, $company),
            'interviews' => $this->formatInterviews($candidate->interviews),
            'scorecards' => $this->formatScorecards($candidate->scorecards),
        ];
    }

    private function formatCurrentApplication($currentPipelineStage, $job, $company): ?array
    {
        if (!$currentPipelineStage) {
            return null;
        }

        return [
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
        ];
    }

    private function formatInterviews($interviews)
    {
        return $interviews->map(fn($interview) => $this->formatSingleInterview($interview));
    }

    private function formatSingleInterview($interview): array
    {
        return [
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
                'email' => $interview->interviewer->email,
            ] : null,
        ];
    }

    private function formatScorecards($scorecards)
    {
        return $scorecards->map(fn($scorecard) => $this->formatSingleScorecard($scorecard));
    }

    private function formatSingleScorecardForList($scorecard): array
    {
        return [
            'score_rate' => $scorecard->score_rate,
            'scorelabel' => $scorecard->scorelabel->name ?? null,
        ];
    }

    private function formatSingleScorecard($scorecard): array
    {
        return [
            'id' => $scorecard->id,
            'candidate_id' => $scorecard->candidate_id,
            'job_id' => $scorecard->job_id,
            'interview_id' => $scorecard->interview_id,
            'score_rate' => $scorecard->score_rate,
            'scorelabel_id' => $scorecard->scorelabel_id,
            'status' => $scorecard->status,
            'scorelabel' => $scorecard->scorelabel ? [
                'id' => $scorecard->scorelabel->id,
                'name' => $scorecard->scorelabel->name,
            ] : null,
        ];
    }

    private function validateCandidateData(array $data, bool $isUpdate): void
    {
        $rules = $this->getCandidateRules(isCreate: !$isUpdate, data: $data);

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    private function getCandidateRules(bool $isCreate, array $data): array
    {
        // base rules (all fields optional here)
        $baseRules = [
            'full_name' => ['string', 'max:255'],
            'email' => ['email', 'max:255'],
            'job_id' => ['integer', 'exists:jobs,id'],
            'stage' => ['nullable', 'string'],
            'recruiter_id' => ['nullable', 'integer', 'exists:users,id'],
            'level' => ['nullable', 'string', 'max:255'],
            'age' => ['nullable', 'integer', 'min:18', 'max:100'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'github_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
        ];

        if ($isCreate) {
            // For create, mark core fields as required
            $baseRules['full_name'][] = 'required';
            $baseRules['email'][] = 'required';
            $baseRules['job_id'][] = 'required';

            return $baseRules;
        }

        // For update, only validate fields that are actually present
        return array_intersect_key($baseRules, $data);
    }

    private function getOrCreateCandidate(array $data, int $recruiterId): Candidate
    {
        $candidate = Candidate::where('email', $data['email'])->first();

        if (!$candidate) {
            $candidate = new Candidate();
            $candidate->full_name    = $data['full_name'];
            $candidate->email        = $data['email'];
            $candidate->recruiter_id = $recruiterId;
            $candidate->level        = $data['level'] ?? 'junior';
            $candidate->age          = $data['age'] ?? null;
            $candidate->phone_number = $data['phone_number'] ?? null;
            $candidate->location     = $data['location'] ?? null;
            $candidate->github_url   = $data['github_url'] ?? null;
            $candidate->linkedin_url = $data['linkedin_url'] ?? null;
            $candidate->save();

            return $candidate;
        }

        $this->updateCandidateIfNeeded($candidate, $data);
        return $candidate;
    }

    private function updateCandidateIfNeeded(Candidate $candidate, array $data): void
    {
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

    private function findStageForJob(int $jobId, string $stageName): Stage
    {
        $normalizedStageName = strtolower($stageName);
        $job = Job::with(['pipelines.pipelineStages.stage'])->find($jobId);
        
        $stage = $this->findStageInJobPipeline($job, $normalizedStageName);
        
                if (!$stage) {
            $stage = $this->findStageGlobally($normalizedStageName);
        }
        
        if (!$stage) {
            $stage = $this->findStageGlobally('applied');
        }
        
        if (!$stage) {
            throw new ModelNotFoundException("Stage '{$stageName}' not found for job {$jobId}", 404);
        }

        return $stage;
    }

    private function findStageInJobPipeline($job, string $normalizedStageName): ?Stage
    {
        if (!$job || $job->pipelines->isEmpty()) {
            return null;
        }
        
        $pipeline = $job->pipelines->first();
        if (!$pipeline || $pipeline->pipelineStages->isEmpty()) {
            return null;
        }

        $match = $pipeline->pipelineStages->first(function ($pipelineStage) use ($normalizedStageName) {
            return $pipelineStage->stage
                && strtolower($pipelineStage->stage->name) === $normalizedStageName;
        });

        if ($match && $match->stage) {
            return $match->stage;
        }

        $firstPipelineStage = $pipeline->pipelineStages->sortBy('order')->first();
        return $firstPipelineStage && $firstPipelineStage->stage ? $firstPipelineStage->stage : null;
    }

    private function findStageGlobally(string $stageName): ?Stage
    {
        return Stage::whereRaw('LOWER(name) = ?', [$stageName])->first();
    }

    private function createOrUpdatePipelineStage(int $candidateId, int $jobId, int $stageId): CandidatePipelineStage
    {
        $candidatePipelineStage = CandidatePipelineStage::where('candidate_id', $candidateId)
            ->where('job_id', $jobId)
            ->first();

        if (!$candidatePipelineStage) {
            $candidatePipelineStage = new CandidatePipelineStage();
            $candidatePipelineStage->candidate_id      = $candidateId;
            $candidatePipelineStage->job_id            = $jobId;
            $candidatePipelineStage->pipeline_stage_id = $stageId;
            $candidatePipelineStage->moved_at          = now();
            $candidatePipelineStage->save();

            return $candidatePipelineStage;
        }

        $candidatePipelineStage->update([
            'pipeline_stage_id' => $stageId,
            'moved_at' => now(),
        ]);

        return $candidatePipelineStage;
    }

    private function createOrUpdateCandidateJob(int $candidateId, int $jobId, int $recruiterId, ?string $source): void
    {
        $candidateJob = CandidateJob::where('candidate_id', $candidateId)
            ->where('job_id', $jobId)
            ->first();

        if (!$candidateJob) {
            $candidateJob = new CandidateJob();
            $candidateJob->candidate_id = $candidateId;
            $candidateJob->job_id       = $jobId;
            $candidateJob->recruiter_id = $recruiterId;
            $candidateJob->source       = $source;
            $candidateJob->save();
        } elseif ($source !== null) {
            $candidateJob->update(['source' => $source]);
            }
        }

    private function validateUpdateStageInput(int $candidateId, int $jobId): void
    {
        $data = [
            'candidate_id' => $candidateId,
            'job_id' => $jobId,
        ];
        $rules = $this->getUpdateStageValidationRules();

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    private function getUpdateStageValidationRules(): array
    {
        return [
            'candidate_id' => ['required', 'integer', 'exists:candidates,id'],
            'job_id' => ['required', 'integer', 'exists:jobs,id'],
        ];
    }

    private function findStageByIdOrName($stageNameOrId): ?Stage
    {
        if (is_numeric($stageNameOrId)) {
            return Stage::find((int) $stageNameOrId);
        }

        return Stage::whereRaw('LOWER(name) = ?', [strtolower($stageNameOrId)])->first();
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
}

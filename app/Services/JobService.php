<?php

namespace App\Services;

use App\Models\Job;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class JobService
{
    public function __construct(
        private readonly PipelineService $pipelineService,
        private readonly JobSkillService $jobSkillService,
        private readonly ScoreLabelService $scoreLabelService
    ) {
    }

    public function createJob(array $data): Job
    {
        $validator = Validator::make($data, [
            'recruiter_id' => ['required', 'integer', 'exists:users,id'],
            'company_id' => ['required', 'integer', 'exists:company_names,id'],
            'title' => ['required', 'string'],
            'description' => ['required', 'string'],
            'location' => ['nullable', 'string'],
            'employment_type' => ['nullable', 'string'],
            'level' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'skills' => ['nullable', 'array'],
            'skills.*.name' => ['required', 'string'],
            'skills.*.type' => ['required', 'integer', 'in:1,2'],
            'pipeline_stages' => ['nullable', 'array'],
            'pipeline_stages.*.name' => ['required', 'string'],
            'score_labels' => ['nullable', 'array'],
            'score_labels.*.name' => ['required', 'string'],
            'score_labels.*.max_score' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $job = new Job();
        $job->recruiter_id = $data['recruiter_id'];
        $job->company_id = $data['company_id'];
        $job->title = $data['title'];
        $job->description = $data['description'];
        $job->location = $data['location'] ?? null;
        $job->employment_type = $data['employment_type'] ?? null;
        $job->level = $data['level'] ?? null;
        $job->status = $data['status'] ?? 'open';
        $job->save();

        if (isset($data['skills']) && !empty($data['skills'])) {
            $this->jobSkillService->attachSkillsToJob($job->id, $data['skills']);
        }

        if (isset($data['pipeline_stages']) && !empty($data['pipeline_stages'])) {
            $this->pipelineService->createPipeline($job->id, $job->title, $data['pipeline_stages']);
        }

        if (isset($data['score_labels']) && !empty($data['score_labels'])) {
            $this->scoreLabelService->createScoreLabels($data['score_labels']);
        }

        return $job->load(['recruiter', 'company', 'jobSkills.skill', 'pipelines.stages']);
    }

    public function getAllJobs(?int $recruiterId = null, ?int $companyId = null, ?string $status = null)
    {
        $query = Job::with(['recruiter', 'company', 'jobSkills.skill', 'pipelines.stages']);

        if ($recruiterId !== null) {
            $query->where('recruiter_id', $recruiterId);
        }

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    public function getJobById(int $id)
    {
        $job = Job::with(['recruiter', 'company', 'jobSkills.skill', 'pipelines.stages'])->find($id);
        if (!$job) {
            throw new \Exception("Job not found");
        }
        return $job;
    }

    public function updateJob(int $id, array $data): Job
    {
        $job = Job::find($id);
        if (!$job) {
            throw new \Exception("Job not found");
        }

        $validator = Validator::make($data, [
            'recruiter_id' => ['sometimes', 'integer', 'exists:users,id'],
            'company_id' => ['sometimes', 'integer', 'exists:company_names,id'],
            'title' => ['sometimes', 'string'],
            'description' => ['sometimes', 'string'],
            'location' => ['nullable', 'string'],
            'employment_type' => ['nullable', 'string'],
            'level' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'skills' => ['nullable', 'array'],
            'skills.*.name' => ['required', 'string'],
            'skills.*.type' => ['required', 'integer', 'in:1,2'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $job->recruiter_id = $data['recruiter_id'] ?? $job->recruiter_id;
        $job->company_id = $data['company_id'] ?? $job->company_id;
        $job->title = $data['title'] ?? $job->title;
        $job->description = $data['description'] ?? $job->description;
        $job->location = $data['location'] ?? $job->location;
        $job->employment_type = $data['employment_type'] ?? $job->employment_type;
        $job->level = $data['level'] ?? $job->level;
        $job->status = $data['status'] ?? $job->status;
        $job->save();

        if (isset($data['skills'])) {
            $this->jobSkillService->detachAllSkillsFromJob($job->id);
            if (!empty($data['skills'])) {
                $this->jobSkillService->attachSkillsToJob($job->id, $data['skills']);
            }
        }

        return $job->load(['recruiter', 'company', 'jobSkills.skill', 'pipelines.stages']);
    }

    public function deleteJob(int $id)
    {
        $job = Job::find($id);
        if (!$job) {
            throw new \Exception("Job not found");
        }
        $job->delete();
        return true;
    }
}
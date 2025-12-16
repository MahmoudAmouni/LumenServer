<?php
namespace App\Services;

use App\Models\Job;
use App\Models\CompanyName;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class JobService
{
    public function __construct(
        private readonly PipelineService $pipelineService,
        private readonly JobSkillService $jobSkillService,
        private readonly ScoreLabelService $scoreLabelService
    ) {}

    public function getJobsByCompanyId(Request $request, int $companyId)
    {
        // Check if company exists
        $company = CompanyName::find($companyId);
        if (!$company) {
            throw new ModelNotFoundException("Company not found");
        }

        $perPage = min((int) $request->query('per_page', 20), 100);

        $jobs = Job::query()
            ->with(['pipelines.stages'])
            ->where('company_id', $companyId)
            ->orderBy('id')
            ->cursorPaginate($perPage);

        // If no jobs found, still return empty result (not an error)
        return $jobs;
    }

    public function createJob(array $data)
    {
        // Use database transaction to ensure atomicity
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $job = $this->createJobRecord($data);

            $this->attachSkillsToJob($job->id, $data['skills'] ?? []);
            $this->createPipelineForJob($job->id, $job->title, $data['pipeline'] ?? []);
            $this->createScoreLabels($data['criteria'] ?? []);

            return $job->load([
                'recruiter',
                'company',
                'jobSkills.skill',
                'pipelines.stages'
            ]);
        });
    }

    public function updateJob(int $id, array $data)
    {
        $job = Job::findOrFail($id);
        $this->updateJobFields($job, $data);

        return $job->load([
            'recruiter',
            'company',
            'jobSkills.skill',
            'pipelines.stages'
        ]);
    }

    public function deleteJob(int $id): void
    {
        $job = Job::findOrFail($id);
        $job->delete();
    }

    private function createJobRecord(array $data): Job
    {
        $job = new Job();
        $job->recruiter_id    = $data['recruiter_id'];
        $job->company_id      = $data['company_id'];
        $job->title           = $data['jobTitle'];
        $job->description     = $data['jobDescription'];
        $job->location        = $data['jobLocation'] ?? null;
        $job->employment_type = $data['employmentType'] ?? null;
        $job->level           = $data['jobLevel'] ?? null;
        $job->status          = $data['status'] ?? 'open';
        $job->save();

        return $job;
    }

    private function attachSkillsToJob(int $jobId, array $skills): void
    {
        if (!empty($skills)) {
            $this->jobSkillService->attachSkillsToJob($jobId, $skills);
        }
    }

    private function createPipelineForJob(int $jobId, string $jobTitle, array $pipeline): void
    {
        if (empty($pipeline)) {
            return;
        }
        $stages = collect($pipeline)
            ->map(fn($item) => ['name' => $item['name']])
            ->all();

        $this->pipelineService->createPipeline($jobId, $jobTitle, $stages);
    }

    private function createScoreLabels(array $criteria): void
    {
        if (empty($criteria)) {
            return;
        }
        $labels = collect($criteria)
            ->map(fn($item) => ['name' => $item['name']])
            ->all();

        $this->scoreLabelService->createScoreLabels($labels);
    }

    private function updateJobFields(Job $job, array $data): void
    {
        $updatableFields = [
            'title', 'description', 'location',
            'employment_type', 'level', 'status',
            'recruiter_id', 'company_id'
        ];

        collect($updatableFields)->each(function ($field) use ($job, $data) {
            if (array_key_exists($field, $data)) {
                $job->$field = $data[$field];
            }
        });

        $job->save();
    }
}

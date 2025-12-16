<?php
namespace App\Services;

use App\Models\Job;
use App\Models\CompanyName;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
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
        $perPage = min((int) $request->query('per_page', 20), 100);

        return Job::query()
            ->with(['pipelines.stages'])
            ->where('company_id', $companyId)
            ->orderBy('id')
            ->cursorPaginate($perPage);
    }

    public function createJob(array $data)
    {
        $this->validateJobData($data, isUpdate: false);

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
    }

    public function updateJob(int $id, array $data)
    {
        $job = Job::findOrFail($id);
        $this->validateJobData($data, isUpdate: true);
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

    private function validateJobData(array $data, bool $isUpdate): void
    {
        $rules = $isUpdate 
            ? $this->getUpdateValidationRules($data)
            : $this->getCreateValidationRules();

        $validator = Validator::make($data, $rules);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    private function getCreateValidationRules(): array
    {
        return [
                'recruiter_id' => ['required', 'integer', 'exists:users,id'],
                'company_id' => ['required', 'integer', 'exists:company_names,id'],
                'jobTitle' => ['required', 'string'],
                'jobDescription' => ['required', 'string'],
                'jobLocation' => ['nullable', 'string'],
                'employmentType' => ['nullable', 'string'],
                'jobLevel' => ['nullable', 'string'],
                'status' => ['nullable', 'string', 'in:open,closed,draft,paused'],
                'skills' => ['nullable', 'array'],
                'skills.*.name' => ['required', 'string'],
                'skills.*.type' => ['required', 'integer', 'in:1,2'],
                'pipeline' => ['nullable', 'array'],
                'pipeline.*.name' => ['required', 'string'],
                'criteria' => ['nullable', 'array'],
                'criteria.*.name' => ['required', 'string'],
            ];
    }

    private function getUpdateValidationRules(array $data): array
    {
            $baseRules = [
                'recruiter_id' => ['integer', 'exists:users,id'],
                'company_id' => ['integer', 'exists:company_names,id'],
                'jobTitle' => ['string'],
                'jobDescription' => ['string'],
                'jobLocation' => ['nullable', 'string'],
                'employmentType' => ['nullable', 'string'],
                'jobLevel' => ['nullable', 'string'],
                'status' => ['string', 'in:open,closed,draft,paused'],
            ];

        return array_intersect_key($baseRules, $data);
        }
    }

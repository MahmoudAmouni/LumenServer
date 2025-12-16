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
        $company = CompanyName::find($companyId);
        if (!$company) {
            throw new ModelNotFoundException("Company not found");
        }

        // for pagination
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

        $job = new Job();
        $job->recruiter_id = $data['recruiter_id'];
        $job->company_id = $data['company_id'];
        $job->title = $data['jobTitle'];
        $job->description = $data['jobDescription'];
        $job->location = $data['jobLocation'] ?? null;
        $job->employment_type = $data['employmentType'] ?? null;
        $job->level = $data['jobLevel'] ?? null;
        $job->status = $data['status'] ?? 'open';
        $job->save();

        if (!empty($data['skills'])) {
            $this->jobSkillService->attachSkillsToJob($job->id, $data['skills']);
        }

        if (!empty($data['pipeline'])) {
            $stages = [];
            foreach ($data['pipeline'] as $item) {
                $stages[] = ['name' => $item['name']];
            }
            $this->pipelineService->createPipeline($job->id, $job->title, $stages);
        }

        if (!empty($data['criteria'])) {
            $labels = [];
            foreach ($data['criteria'] as $item) {
                $labels[] = ['name' => $item['name']];
            }
            $this->scoreLabelService->createScoreLabels($labels);
        }

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

        $updatableFields = [
            'title', 'description', 'location',
            'employment_type', 'level', 'status',
            'recruiter_id', 'company_id'
        ];

        foreach ($updatableFields as $field) {
            if (array_key_exists($field, $data)) {
                $job->$field = $data[$field];
            }
        }

        $job->save();

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

    private function validateJobData(array $data, bool $isUpdate)
    {
        if (!$isUpdate) {
            $rules = [
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
        } else {
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

            $rules = array_intersect_key($baseRules, $data);
        }

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
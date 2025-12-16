<?php
namespace App\Services;

use App\Models\Job;
use App\Models\JobSkill;
use App\Models\Skill;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class JobSkillService
{
    public function __construct(private SkillService $skillService) {}

    public function attachSkillsToJob(int $jobId, array $skills)
    {
        $this->validateInput($jobId, $skills);

        if (empty($skills)) {
            JobSkill::where('job_id', $jobId)->delete();
            return;
        }

        $skillNames = $this->extractSkillNames($skills);
        $skillTypes = $this->extractSkillTypes($skills);

        $this->skillService->getOrCreateSkills($skillNames);
        $nameToId = $this->mapSkillNamesToIds($skillNames);

        $this->replaceJobSkills($jobId, $skills, $skillTypes, $nameToId);
    }

    private function validateInput(int $jobId, array $skills): void
    {
        $data = [
            'job_id' => $jobId,
            'skills' => $skills,
        ];

        $rules = $this->getCreateValidationRules();

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    private function getCreateValidationRules(): array
    {
        return [
            'job_id' => ['required', 'integer', 'exists:jobs,id'],
            'skills' => ['required', 'array'],
            'skills.*.name' => ['required', 'string'],
            'skills.*.type' => ['required', 'integer', 'in:1,2'],
        ];
    }

    private function extractSkillNames(array $skills): array
    {
        return collect($skills)
            ->map(fn($skill) => $skill['name'])
            ->values()
            ->all();
    }

    private function extractSkillTypes(array $skills): array
    {
        return collect($skills)
            ->mapWithKeys(fn($skill) => [$skill['name'] => $skill['type']])
            ->all();
    }

    private function mapSkillNamesToIds(array $skillNames): array
    {
        return Skill::whereIn('name', $skillNames)->pluck('id', 'name')->toArray();
    }

    private function replaceJobSkills(int $jobId, array $skills, array $skillTypes, array $nameToId): void
    {
        JobSkill::where('job_id', $jobId)->delete();

        $now = now();
        $pivotData = collect($skills)
            ->map(function ($skill) use ($jobId, $skillTypes, $nameToId, $now) {
                return [
                    'job_id'     => $jobId,
                    'skill_id'   => $nameToId[$skill['name']],
                    'type'       => $skillTypes[$skill['name']],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->all();

        JobSkill::insert($pivotData);
    }
}

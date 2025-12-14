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

        // Extract names and types
        $skillNames = [];
        $skillTypes = [];
        foreach ($skills as $skill) {
            $name = $skill['name'];
            $skillNames[] = $name;
            $skillTypes[$name] = $skill['type'];
        }

        // Ensure all skills exist globally
        $this->skillService->getOrCreateSkills($skillNames);

        // Map names to IDs
        $nameToId = Skill::whereIn('name', $skillNames)->pluck('id', 'name');

        // Clear old attachments for this job
        JobSkill::where('job_id', $jobId)->delete();

        // Insert new job-skill links
        $now = now();
        $pivotData = [];
        foreach ($skills as $skill) {
            $pivotData[] = [
                'job_id' => $jobId,
                'skill_id' => $nameToId[$skill['name']],
                'type' => $skillTypes[$skill['name']],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        JobSkill::insert($pivotData);
    }

    private function validateInput(int $jobId, array $skills): void
    {
        // Validate job exists
        if (!Job::where('id', $jobId)->exists()) {
            throw new ValidationException(
                Validator::make([], [])->errors()->add('job_id', 'Job not found.')
            );
        }

        // Validate skill structure
        $validator = Validator::make(['skills' => $skills], [
            'skills' => ['required', 'array'],
            'skills.*.name' => ['required', 'string'],
            'skills.*.type' => ['required', 'integer', 'in:1,2'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
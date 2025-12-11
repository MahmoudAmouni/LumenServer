<?php

namespace App\Services;

use App\Models\JobSkill;

class JobSkillService
{
    public function __construct(private readonly SkillService $skillService) {
    }

    public function attachSkillsToJob(int $jobId, array $skills)
    {
        $skillTypes = [];
        $skillNames = [];

        foreach ($skills as $skillData) {
            $skillName = $skillData['name'];
            $skillNames[] = $skillName;
            $skillTypes[$skillName] = $skillData['type'];
        }

        $skillIds = $this->skillService->createSkills($skillNames);

        if (!empty($skillIds)) {
            $pivotData = [];
            $now = now();

            foreach ($skillIds as $skillName => $skillId) {
                $pivotData[] = [
                    'job_id' => $jobId,
                    'skill_id' => $skillId,
                    'Type' => $skillTypes[$skillName],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            JobSkill::insert($pivotData);
        }
    }

    public function detachAllSkillsFromJob(int $jobId)
    {
        JobSkill::where('job_id', $jobId)->delete();
    }

    public function getJobSkillsByJobId(int $jobId)
    {
        return JobSkill::with('skill')->where('job_id', $jobId)->get();
    }

    public function getJobSkillsBySkillId(int $skillId)
    {
        return JobSkill::with('job')->where('skill_id', $skillId)->get();
    }
}
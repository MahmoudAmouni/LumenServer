<?php

namespace App\Services;

use App\Models\Skill;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\JobSkill;

class SkillService
{

    private function validateSkillsInput(int $jobId, array $skills)
    {
        $data = [
            'job_id' => $jobId,
            'skills' => $skills,
        ];

        $validator = Validator::make($data, [
            'job_id' => ['required', 'integer', 'exists:jobs,id'],
            'skills' => ['required', 'array'],
            'skills.*.name' => ['required', 'string'],
            'skills.*.type' => ['required', 'integer', 'in:1,2'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    public function createSkills($jobId, $skills)
    {
        $this->validateSkillsInput($jobId, $skills);

        $skillNames = [];
        $skillTypes = [];
        foreach ($skills as $skillData) {
            $name = $skillData['name'];
            $skillNames[] = $name;
            $skillTypes[$name] = $skillData['type'];
        }

        $existingSkills = Skill::whereIn('name', $skillNames)
            ->get()
            ->keyBy('name');

        $newSkillsData = [];
        foreach ($skillNames as $name) {
            if (!isset($existingSkills[$name])) {
                $newSkillsData[] = [
                    'name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($newSkillsData)) {
            Skill::insert($newSkillsData);
            $newSkills = Skill::whereIn('name', array_column($newSkillsData, 'name'))
                ->get()
                ->keyBy('name');
            $existingSkills = $existingSkills->merge($newSkills);
        }

        JobSkill::where('job_id', $jobId)->delete();

        if (!empty($skills)) {
            $pivotData = [];
            $now = now();
            foreach ($skills as $skillData) {
                $name = $skillData['name'];
                $skillId = $existingSkills[$name]->id;
                $pivotData[] = [
                    'job_id' => $jobId,
                    'skill_id' => $skillId,
                    'Type' => $skillTypes[$name],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            JobSkill::insert($pivotData);
        }
    }

}
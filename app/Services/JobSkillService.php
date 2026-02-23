<?php
namespace App\Services;

use App\Models\JobSkill;
use App\Models\Skill;
use Illuminate\Support\Facades\DB;


class JobSkillService
{
    public function __construct(private SkillService $skillService) {}

    public function attachSkillsToJob(int $jobId, array $skills){

        DB::transaction(function () use ($jobId, $skills) {

            $skillNames = $this->extractSkillNames($skills);
            $skillTypes = $this->extractSkillTypes($skills);

            $this->skillService->getOrCreateSkills($skillNames);
            $nameToId = $this->mapSkillNamesToIds($skillNames);

            $this->replaceJobSkills($jobId, $skills, $skillTypes, $nameToId);
        });
    }

    private function extractSkillNames(array $skills): array{
        return collect($skills)
            ->map(fn($skill) => $skill['name'])
            ->values()
            ->all();
    }

    private function extractSkillTypes(array $skills): array{
        return collect($skills)
            ->mapWithKeys(fn($skill) => [$skill['name'] => $skill['type']])
            ->all();
    }

    private function mapSkillNamesToIds(array $skillNames): array{
        return Skill::whereIn('name', $skillNames)->pluck('id', 'name')->toArray();
    }

    private function replaceJobSkills(int $jobId, array $skills, array $skillTypes, array $nameToId): void{
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

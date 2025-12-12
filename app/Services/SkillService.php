<?php

namespace App\Services;

use App\Models\Skill;
//

class SkillService
{
    public function getAllSkills()
    {
        return Skill::with('jobSkills.job')->get();
    }

    public function getSkillById(int $id)
    {
        $skill = Skill::with('jobSkills.job')->find($id);
        if (!$skill) {
            throw new \Exception("Skill not found");
        }
        return $skill;
    }

    public function createSkill(string $name): Skill
    {
        $skill = new Skill();
        $skill->name = $name;
        $skill->save();
        return $skill;
    }

    public function createSkills(array $skills): array
    {
        $allSkillsData = [];

        foreach ($skills as $skillData) {
            $skillName = is_array($skillData) ? $skillData['name'] : $skillData;
            $allSkillsData[$skillName] = [
                'name' => $skillName,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $skillNames = array_keys($allSkillsData);
        $existingSkills = Skill::whereIn('name', $skillNames)
            ->get()
            ->keyBy('name');

        $newSkillsData = [];
        $allSkillIds = [];

        foreach ($allSkillsData as $name => $data) {
            if (isset($existingSkills[$name])) {
                $allSkillIds[$name] = $existingSkills[$name]->id;
            } else {
                $newSkillsData[] = $data;
            }
        }

        if (!empty($newSkillsData)) {
            Skill::insert($newSkillsData);

            $newSkills = Skill::whereIn('name', array_column($newSkillsData, 'name'))
                ->get()
                ->keyBy('name');

            foreach ($newSkills as $name => $skill) {
                $allSkillIds[$name] = $skill->id;
            }
        }

        return $allSkillIds;
    }

    public function updateSkill(int $id, string $name): Skill
    {
        $skill = Skill::find($id);
        if (!$skill) {
            throw new \Exception("Skill not found");
        }
        $skill->name = $name;
        $skill->save();
        return $skill;
    }

    public function deleteSkill(int $id)
    {
        $skill = Skill::find($id);
        if (!$skill) {
            throw new \Exception("Skill not found");
        }
        $skill->delete();
        return true;
    }
}
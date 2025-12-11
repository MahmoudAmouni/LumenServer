<?php

namespace App\Services;

use App\Models\Skill;

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
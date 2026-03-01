<?php
namespace App\Services;

use App\Models\Skill;
use Illuminate\Support\Collection;

class SkillService{

    public function getOrCreateSkills(array $skillNames){
        if (empty($skillNames)) {
            return collect();
        }

        $existing = Skill::whereIn('name', $skillNames)->get()->keyBy('name');
        $missingNames = array_diff($skillNames, $existing->keys()->toArray());

        if(!empty($missingNames)){
          $this->createMissingSkills($missingNames , $existing);
        }

        return collect($existing->values());
    }

    private function createMissingSkills(array $missingNames , Skill $existing){
        $now = now();
        $insertData = collect($missingNames)
            ->map(function ($name) use ($now) {
                return [
                    'name'       => $name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->all();
        Skill::insert($insertData);

        $newSkills = Skill::whereIn('name', $missingNames)->get();
        $existing = $existing->merge($newSkills->keyBy('name'));
    }
}
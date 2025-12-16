<?php
namespace App\Services;

use App\Models\Skill;
use Illuminate\Support\Collection;

class SkillService
{
    public function getOrCreateSkills(array $skillNames)
    {
        if (empty($skillNames)) {
            return collect();
        }

        $existing = Skill::whereIn('name', $skillNames)->get()->keyBy('name');
        $missingNames = array_diff($skillNames, $existing->keys()->toArray());

        if (!empty($missingNames)) {
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

        return collect($existing->values());
    }
}
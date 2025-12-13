<?php

namespace Database\Factories;

use App\Models\JobSkill;
use App\Models\Job;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobSkill>
 */
class JobSkillFactory extends Factory
{
    protected $model = JobSkill::class;

    public function definition(): array
    {
        return [
            'job_id' => Job::factory(),
            'skill_id' => Skill::factory(),
            'type' => fake()->randomElement([1, 2]), // 1 = required, 2 = nice to have
        ];
    }
}


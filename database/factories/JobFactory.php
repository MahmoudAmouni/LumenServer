<?php

namespace Database\Factories;

use App\Models\Job;
use App\Models\CompanyName;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Job>
 */
class JobFactory extends Factory
{
    protected $model = Job::class;

    public function definition(): array
    {
        return [
            'title' => fake()->jobTitle(),
            'description' => fake()->paragraph(),
            'level' => fake()->randomElement(['Junior', 'Mid', 'Senior']),
            'location' => fake()->city(),
            'company_id' => CompanyName::factory(),
            'recruiter_id' => User::factory(),
        ];
    }
}


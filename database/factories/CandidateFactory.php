<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Candidate>
 */
class CandidateFactory extends Factory
{
    protected $model = Candidate::class;

    public function definition(): array
    {
        return [
            'recruiter_id' => function () {
                return (string) User::factory()->create()->id;
            },
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone_number' => fake()->optional()->phoneNumber(),
            'level' => fake()->randomElement(['Junior', 'Mid', 'Senior']),
            'github_url' => fake()->optional()->url(),
            'linkedin_url' => fake()->optional()->url(),
            'cv_path' => fake()->optional()->text(),
        ];
    }
}


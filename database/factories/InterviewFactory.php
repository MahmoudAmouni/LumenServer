<?php

namespace Database\Factories;

use App\Models\Interview;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Interview>
 */
class InterviewFactory extends Factory
{
    protected $model = Interview::class;

    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'interviewer_id' => User::factory(),
            'interview_type_id' => fake()->optional()->numberBetween(1, 5),
            'notes' => fake()->optional()->text(),
            'duration' => fake()->optional()->numberBetween(30, 120),
            'scheduled_at' => fake()->dateTimeBetween('now', '+1 month'),
            'status' => fake()->randomElement(['scheduled', 'completed', 'cancelled', 'in_progress']),
        ];
    }
}


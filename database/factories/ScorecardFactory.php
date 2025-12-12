<?php

namespace Database\Factories;

use App\Models\Scorecard;
use App\Models\Candidate;
use App\Models\User;
use App\Models\ScoreLabel;
use App\Models\Job;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Scorecard>
 */
class ScorecardFactory extends Factory
{
    protected $model = Scorecard::class;

    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'job_id' => Job::factory(),
            'scorelabel_id' => ScoreLabel::factory(),
            'scorerate_id' => fake()->numberBetween(1, 10),
            'interview_id' => fake()->numberBetween(1, 100),
            'status' => fake()->randomElement(['completed', 'pending', 'in_progress']),
        ];
    }
}


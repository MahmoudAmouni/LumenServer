<?php

namespace Database\Factories;

use App\Models\CopilotQuery;
use App\Models\Candidate;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CopilotQuery>
 */
class CopilotQueryFactory extends Factory
{
    protected $model = CopilotQuery::class;

    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'job_id' => Job::factory(),
            'query_text' => fake()->sentence(),
            'response_text' => fake()->paragraph(),
            'recruiter_id' => User::factory(),
            'citation_text' => fake()->optional()->text(),
            'source' => fake()->optional()->numberBetween(1, 10),
        ];
    }
}


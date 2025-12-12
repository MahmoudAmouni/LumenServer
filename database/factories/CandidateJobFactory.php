<?php

namespace Database\Factories;

use App\Models\CandidateJob;
use App\Models\Candidate;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CandidateJob>
 */
class CandidateJobFactory extends Factory
{
    protected $model = CandidateJob::class;

    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'job_id' => Job::factory(),
            'source' => fake()->optional()->randomElement(['LinkedIn', 'Indeed', 'Referral', 'Company Website', 'Job Board']),
            'recruiter_id' => User::factory(),
        ];
    }
}


<?php

namespace Database\Factories;

use App\Models\Offer;
use App\Models\Candidate;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Offer>
 */
class OfferFactory extends Factory
{
    protected $model = Offer::class;

    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'job_id' => Job::factory(),
            'salary' => fake()->randomFloat(2, 30000, 150000),
            'start_date' => fake()->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            'contract_type' => fake()->randomElement(['Full-time', 'Part-time', 'Contract', 'Internship']),
            'offer_letter_template' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement(['draft', 'sent', 'accepted', 'declined']),
            'recruiter_id' => User::factory(),
        ];
    }
}


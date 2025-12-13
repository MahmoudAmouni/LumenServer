<?php

namespace Database\Factories;

use App\Models\Pipeline;
use App\Models\Job;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pipeline>
 */
class PipelineFactory extends Factory
{
    protected $model = Pipeline::class;

    public function definition(): array
    {
        return [
            'job_id' => Job::factory(),
            'name' => fake()->words(2, true) . ' Pipeline',
        ];
    }
}


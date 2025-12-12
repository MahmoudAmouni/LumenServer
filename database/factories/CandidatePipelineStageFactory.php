<?php

namespace Database\Factories;

use App\Models\CandidatePipelineStage;
use App\Models\Candidate;
use App\Models\Stage;
use App\Models\Job;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CandidatePipelineStage>
 */
class CandidatePipelineStageFactory extends Factory
{
    protected $model = CandidatePipelineStage::class;

    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'pipeline_stage_id' => Stage::factory(),
            'job_id' => Job::factory(),
            'moved_at' => fake()->dateTime(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}


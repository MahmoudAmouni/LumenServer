<?php

namespace Database\Factories;

use App\Models\PipelineStages;
use App\Models\Pipeline;
use App\Models\Stage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PipelineStages>
 */
class PipelineStagesFactory extends Factory
{
    protected $model = PipelineStages::class;

    public function definition(): array
    {
        return [
            'pipeline_id' => Pipeline::factory(),
            'stage_id' => Stage::factory(),
            'order' => fake()->numberBetween(1, 10),
        ];
    }
}


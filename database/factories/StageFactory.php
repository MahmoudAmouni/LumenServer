<?php

namespace Database\Factories;

use App\Models\Stage;
use App\Models\Pipeline;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Stage>
 */
class StageFactory extends Factory
{
    protected $model = Stage::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Applied', 'Phone Screen', 'Interview', 'Offer', 'Hired']),
        ];
    }
}


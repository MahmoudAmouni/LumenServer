<?php

namespace Database\Factories;

use App\Models\ScoreLabel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScoreLabel>
 */
class ScoreLabelFactory extends Factory
{
    protected $model = ScoreLabel::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Excellent', 'Good', 'Average', 'Poor', 'Needs Improvement']),
            'max_score' => fake()->numberBetween(5, 10),
        ];
    }
}


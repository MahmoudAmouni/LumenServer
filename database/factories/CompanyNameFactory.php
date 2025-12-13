<?php

namespace Database\Factories;

use App\Models\CompanyName;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CompanyName>
 */
class CompanyNameFactory extends Factory
{
    protected $model = CompanyName::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
        ];
    }
}


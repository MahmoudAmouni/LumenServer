<?php

namespace Database\Factories;

use App\Models\CompanyName;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyNameFactory extends Factory
{
    protected $model = CompanyName::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
        ];//
    }
}

<?php

namespace Database\Factories;

use App\Models\UserType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserType>
 */
class UserTypeFactory extends Factory
{
    protected $model = UserType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Admin', 'Recruiter', 'Manager', 'Interviewer', 'HR']),
        ];
    }
}

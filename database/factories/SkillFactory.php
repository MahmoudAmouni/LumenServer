<?php

namespace Database\Factories;

use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Skill>
 */
class SkillFactory extends Factory
{
    protected $model = Skill::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'PHP', 'JavaScript', 'Python', 'Java', 'C++', 'Ruby', 'Go', 'Rust',
                'Laravel', 'React', 'Vue.js', 'Angular', 'Node.js', 'Django', 'Spring',
                'MySQL', 'PostgreSQL', 'MongoDB', 'Redis', 'Elasticsearch',
                'Docker', 'Kubernetes', 'AWS', 'Azure', 'Git', 'CI/CD',
                'REST API', 'GraphQL', 'Microservices', 'Agile', 'Scrum'
            ]),
        ];
    }
}


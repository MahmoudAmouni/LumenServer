<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Candidate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'file_path' => fake()->filePath(),
        ];
    }
}


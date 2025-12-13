<?php

namespace Database\Factories;

use App\Models\DocumentChunk;
use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DocumentChunk>
 */
class DocumentChunkFactory extends Factory
{
    protected $model = DocumentChunk::class;

    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'chunk_text' => fake()->paragraph(),
            'embedding' => fake()->optional()->text(),
            'chunk_index' => fake()->numberBetween(0, 100),
            'page_number' => fake()->optional()->numberBetween(1, 50),
            'section' => fake()->optional()->randomElement(['Introduction', 'Experience', 'Education', 'Skills', 'Summary']),
        ];
    }
}


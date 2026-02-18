<?php

namespace App\Services\RAG;

use App\Services\PromptLoaderService;
use OpenAI\Laravel\Facades\OpenAI;

class QueryNormalizerService
{
    public function normalize(string $question): string{
        $response = OpenAI::chat()->create([
            'model' => 'gpt-4.1-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' =>PromptLoaderService::load('queryNormalizer')
                ],
                [
                    'role' => 'user',
                    'content' => $question
                ]
            ],
            'temperature' => 0,
        ]);

        return trim($response->choices[0]->message->content);
    }
}

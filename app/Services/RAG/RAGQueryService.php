<?php

namespace App\Services\RAG;

use App\Services\PromptLoaderService;
use App\Services\RAG\QueryNormalizerService;
use GuzzleHttp\Client;
use OpenAI\Laravel\Facades\OpenAI;

class RagQueryService
{
    protected Client $qdrant;

    public function __construct(protected QueryNormalizerService $query_normalizer){
        $this->qdrant = new Client([
            'base_uri' => env('QDRANT_ENDPOINT_URL'),
            'headers' => [
                'api-key' => env('QDRANT_API_KEY'),
                'Content-Type' => 'application/json',
            ],
        ]);

    }

    protected function embed(string $text){
        $response = OpenAI::embeddings()->create([
            'model' => 'text-embedding-3-large',
            'input' => $text,
        ]);

        return $response->embeddings[0]->embedding;
    }

    protected function retrieve(int $candidateId, array $vector, int $limit = 6): array{
        $response = $this->qdrant->post(
            '/collections/candidates/points/search',
            [
                'json' => [
                    'vector' => $vector,
                    'limit' => $limit,
                    'with_payload' => true, 
                    'filter' => [
                        'must' => [
                            [
                                'key' => 'candidate_id',
                                'match' => ['value' => $candidateId]
                            ]
                        ]
                    ]
                ]
            ]
        );

        return json_decode($response->getBody(), true)['result'];
    }
  
    
    protected function buildContext(array $chunks): string{
        $context = '';

        foreach($chunks as $chunk){
            $p = $chunk['payload'];

            $context .=
            "SOURCE: " . ($p['source_label'] ?? 'N/A') . "\n" .
            "SECTION: " . ($p['source_section'] ?? 'General') . "\n" .
            "TEXT:\n" . ($p['text'] ?? '') . "\n\n---\n";
        }

        return $context;
    }

    protected function askModel(string $context, string $question , bool $strict = false): string{
        $response = OpenAI::chat()->create([
            'model' => env('GPT_MODEL'),
            'messages' => $this->buildMessage($context , $question, $strict)
        ]);
        return $response->choices[0]->message->content;
    }

    public function answer(int $candidateId, string $question){

        $normalizedQuestion = $this->query_normalizer->normalize($question);
        $queryVector = $this->embed($normalizedQuestion);
        $chunks = $this->retrieve($candidateId, vector: $queryVector);

        if (empty($chunks)) {
            return [
                'answer' => 'No relevant candidate data found.',
                'citations' => []
            ];
        }

        $context = $this->buildContext($chunks);
        $answer = $this->askModel($context, $question , strict:false);


        if(!$this->validateBulletFormat($answer)){
            // stricter retry
            $answer = $this->askModel($context, $question, strict: true);
        }

        // if it still fails then give up
        if (! $this->validateBulletFormat($answer)) {
            return [
                'answer' => 'Not found in candidate data
                Source: N/A',
            ];
        }

        return [
            'answer' => $answer,
        ];
    }

    private function buildMessage(string $context,string $question,bool $strict){
        $systemPrompt = PromptLoaderService::load(
            $strict ? 'rag/strict' : 'rag/default',
            ['context' => $context]
        );

        return [
            [
                'role' => 'system',
                'content' => $systemPrompt,
            ],
            [
                'role' => 'user',
                'content' => $question,
            ],
        ];
    }

    protected function validateBulletFormat(string $answer): bool{
        // must contain at least one bullet
        if (! preg_match('/^- /m', $answer)) {
            return false;
        }

        // every bullet must have a Source line
        $bullets = preg_split('/^- /m', trim($answer));

        foreach ($bullets as $bullet) {
            if (trim($bullet) === '') continue;

            if (! str_contains($bullet, 'Source:')) {
                return false;
            }
        }

        return true;
    }
}

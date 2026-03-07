<?php

namespace App\Services\RAG;
use Illuminate\Support\Facades\Http;

class RagQueryService{

    public function answer(int $candidateId, string $question): array{
        // call fastapi endpoint - Langchain hanldes everything including validation and error handling
        $answer = Http::post(env('FAST_API_LangChain_URL') . '/candidate/ask', [
            'candidate_id' => $candidateId,
            'question' => $question,
        ])->json()['answer'];

        if(!$answer){
            throw new \Exception("Failed to get answer from RAG service");
        }   

        return [
            'answer' => $answer,
        ];
    }
}

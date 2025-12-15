<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RagChatController extends Controller
{
    public function ask(Request $request)
    {
        $candidateId = (int) $request->input('candidate_id', 12);
        $question = (string) $request->input('question', 'What skills are mentioned in the CV?');
        $topK = (int) $request->input('top_k', 5);

        $ragUrl = rtrim(config('services.rag.url'), '/');
        $apiKey = env('OPENAI_API_KEY');

        if (!$apiKey) {
            return response()->json(['error' => 'OPENAI_API_KEY is missing in .env'], 500);
        }

        // 1) Retrieve from RAG
        $rag = Http::timeout(60)
            ->post("{$ragUrl}/query", [
                'candidate_id' => (string) $candidateId,
                'question' => $question,
                'top_k' => $topK,
            ])
            ->throw()
            ->json();

        $hits = $rag['hits'] ?? [];

        $context = collect($hits)->map(function ($h) {
            $chunkId = $h['chunk_id'] ?? '';
            $page = $h['page'] ?? '';
            $source = $h['source_name'] ?? '';
            $text = $h['text'] ?? '';
            return "[chunk_id={$chunkId}, page={$page}, source={$source}]\n{$text}";
        })->implode("\n\n---\n\n");

        // 2) Ask OpenAI (Responses API)
        $prompt =
            "Answer the question using ONLY the context. If not found, say: \"I don't know based on the provided context.\".\n\n"
            . "Question: {$question}\n\n"
            . "Context:\n{$context}\n";

        $openai = Http::withToken($apiKey)
            ->timeout(60)
            ->post('https://api.openai.com/v1/responses', [
                // Use a known model for testing:
                'model' => 'gpt-4.1-mini',
                'input' => $prompt,
            ])
            ->throw()
            ->json();

        $answer = $this->extractResponseText($openai);

        // Helpful debug if answer is still null:
        if ($answer === null) {
            Log::warning('OpenAI response returned no parsable text', ['openai' => $openai]);
        }

        return response()->json([
            'answer' => $answer,
            'hits' => $hits,
        ]);
    }

    private function extractResponseText(array $resp): ?string
    {
        // Sometimes present depending on client/version:
        $txt = data_get($resp, 'output_text');
        if (is_string($txt) && $txt !== '') {
            return $txt;
        }

        // Standard Responses API structure:
        foreach (data_get($resp, 'output', []) as $out) {
            foreach (data_get($out, 'content', []) as $c) {
                if (($c['type'] ?? null) === 'output_text' && !empty($c['text'])) {
                    return $c['text'];
                }
            }
        }

        return null;
    }
}

<?php

namespace App\Services\Candidate;

use App\Services\CV\CVExtractionService;
use App\Services\CV\CVSectionSplitter;
use App\Models\Candidate;
use App\Models\Interview;
use GuzzleHttp\Client;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Str;

class CandidateIngestionService{

    protected Client $qdrant;

    public function __construct(){
        $this->qdrant = new Client([
            'base_uri' => rtrim(env('QDRANT_ENDPOINT_URL') , '/'),
            'headers' => [
                'api-key' => env('QDRANT_API_KEY'),
                'Content-type' => 'application/json'
            ]
        ]);
    }

    public static function chunkText(string $text, int $size = 500){
        $chunks = [];
        $length = mb_strlen($text, 'UTF-8');

        for ($i = 0; $i < $length; $i += $size) {
            $chunks[] = mb_substr($text, $i, $size, 'UTF-8');
        }
        return $chunks;
    }   

    public function ingest(int $candidateId){
        $candidate = Candidate::findOrFail($candidateId);


        $sources = $this->buildCVSource($candidate);

        foreach ($sources as $source) {
            $this->ingestSource(
                candidateId: $candidate->id,
                text: $source['text'],
                sourceType: $source['type'],
                sourceSection : $source['section'] ?? 'General',
                sourceLabel: $source['label'],
            );
        }
    }

    protected function buildCVSource(Candidate $candidate){
        $sources = [];
        if(!$candidate->cv_path){
            return [];
        }

    
        $cv_extractor = new CVExtractionService();
        $cvText = $this->cleanUtf8($cv_extractor->extract($candidate->cv_path));

        $splitter = new CVSectionSplitter();
        $sections = $splitter->split($cvText);

        foreach($sections as $sectionName => $sectionText){
            $sources[] = [
                'type' => 'cv_pdf',
                'label' => 'CV',
                'section' => ucfirst($sectionName),
                'text' => $sectionText,
            ];
        }
        return $sources;
    }

    public function ingestInterviewNotes(int $interviewId): void{
        $interview = Interview::findOrFail($interviewId);

        if (!$interview->notes || $interview->notes === ""){ // fail silently
            return;
        }

        if ($this->interviewAlreadyIngested($interviewId)) {
            return;
        }

        $this->ingestSource(
            candidateId: $interview->candidate_id,
            text: $interview->notes,
            sourceType: 'interview_notes',
            sourceLabel: 'Interview Notes',
            sourceSection : 'Interview notes',
            extraPayload: [
                'interview_id' => $interviewId
            ]
        );
    }

    protected function ingestSource(int $candidateId,string $text,string $sourceType,string $sourceLabel , string $sourceSection = "General" , array $extraPayload = []){
        
        $chunks = $this->chunkText($text);
       
        $points = [];

        foreach ($chunks as $i => $chunk) {
            $embedding = OpenAI::embeddings()->create([
                'model' => 'text-embedding-3-large',
                'input' => $chunk,
            ]);

            $points[] = [
                'id' => (string) Str::uuid(),
                'vector' => $embedding->embeddings[0]->embedding,
                'payload' => array_merge([
                    'candidate_id' => $candidateId,
                    'source_type' => $sourceType,
                    'source_label' => $sourceLabel,
                    'source_section' => $sourceSection,
                    'chunk_id' => $i,
                    'text' => $chunk,
                ], $extraPayload)
            ];
        }

        $this->qdrant->put('/collections/candidates/points', [
            'json' => ['points' => $points]
        ]);
    }

    protected function interviewAlreadyIngested(int $interviewId): bool{
        $response = $this->qdrant->post(
            '/collections/candidates/points/scroll',
            [
                'json' => [
                    'limit' => 1,
                    'filter' => [
                        'must' => [
                            [
                                'key' => 'interview_id',
                                'match' => ['value' => $interviewId]
                            ]
                        ]
                    ]
                ]
            ]
        );

        $data = json_decode($response->getBody(), true);

        return ! empty($data['result']['points']);
    }

    public function cleanUtf8($str){
        return $str ? mb_convert_encoding($str, 'UTF-8', 'UTF-8') : null;
    }
}

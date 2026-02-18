<?php

namespace App\Jobs;

use App\Services\Candidate\CandidateIngestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;


class IngestCandidateInterviewNotes implements ShouldQueue{
    use Dispatchable, 
    Queueable, SerializesModels;

    public int $interviewId;

    public function __construct(int $interviewId) {
        $this->interviewId = $interviewId;
    }

    public function handle(CandidateIngestionService $service)
    {
        $service->ingestInterviewNotes($this->interviewId);
    }
}
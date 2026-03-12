<?php

namespace App\Services;

use App\Jobs\IngestCandidateInterviewNotes;
use App\Models\Candidate;
use App\Models\Interview;
use Illuminate\Validation\ValidationException;

class InterviewService
{
    public function createInterview(array $data): Interview
    {
        $interview = new Interview();
        
        $interview->job_id = $data['job_id'];
        $interview->candidate_id = $data['candidate_id'];
        $interview->interviewer_id = $data['interviewer_id'];
        $interview->notes = $data['notes'] ?? null;
        $interview->duration = $data['duration'] ?? null;
        $interview->scheduled_at = $data['scheduled_at'];
        $interview->status = $data['status'] ?? 'scheduled';
        $interview->save();

        return $interview;
    }

    public function updateInterview(int $id, array $data): Interview
    {
        $interview = Interview::findOrFail($id);
        $interview->fill($data)->save();
        return $interview;
    }

    public function getInterviewsByJobId(int $jobId)
    {
        $interviews = Interview::where('job_id', $jobId)
            ->with(['candidate', 'interviewer'])
            ->orderBy('scheduled_at', 'desc')
            ->get();

        return $interviews;
    }
}

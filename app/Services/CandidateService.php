<?php

namespace App\Services;

use App\Models\Pipeline;
use App\Models\CandidatePipelineStage;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CandidateService
{

    /**
     * Get candidates by job ID and pipeline stage ID
     * Returns candidates in a specific stage for a specific job
     * 
     * @param int $jobId The job ID
     * @param int $pipelineStageId The pipeline stage ID
     * @return \Illuminate\Support\Collection Formatted candidate data with scorecards
     */
    public function getCandidatesByJobIdAndPipelineStage(int $jobId, int $pipelineStageId){
        $candidatePipelineStages = CandidatePipelineStage::with([
            'candidate',
            'candidate.scorecards' => function ($query) use ($jobId) {
                $query->where('job_id', $jobId)->with('scorelabel');
            },
            'pipelineStage',
            'job'
        ])
        ->where('job_id', $jobId)
        ->where('pipeline_stage_id', $pipelineStageId)
        ->orderBy('moved_at', 'desc')
        ->get();

        return $candidatePipelineStages->map(function ($item) {
            return [
                'candidate_pipeline_stage_id' => $item->id,
                'candidate' => [
                    'id' => $item->candidate->id,
                    'name' => $item->candidate->full_name,
                    'email' => $item->candidate->email,
                ],
                'pipeline_stage' => [
                    'id' => $item->pipelineStage->id,
                    'name' => $item->pipelineStage->name,
                ],
                'scorecards' => $item->candidate->scorecards->map(function ($scorecard) {
                    return [
                        'scorerate_id' => $scorecard->scorerate_id,
                        'scorelabel' => $scorecard->scorelabel ? $scorecard->scorelabel->name : null,
                        'max_score' => $scorecard->scorelabel ? $scorecard->scorelabel->max_score : null,
                    ];
                }),
                'moved_at' => $item->moved_at,
                'notes' => $item->notes,
            ];
        });
    }   
}


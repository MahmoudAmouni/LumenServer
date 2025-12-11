<?php

namespace App\Services;

use App\Models\Pipeline;
use App\Models\CandidatePipelineStage;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CandidateService
{
    public function getAllPipelines(?int $jobId = null, ?int $companyId = null)
    {
        $query = Pipeline::with(['job.company', 'job']);

        if ($jobId !== null) {
            $query->where('job_id', $jobId);
        }

        if ($companyId !== null) {
            $query->whereHas('job', function ($q) use ($companyId) {

            });
        }

        return $query->get();
    }

    public function getPipelineById(int $id)
    {
        $pipeline = Pipeline::with(['job.company', 'job'])->find($id);
        if (!$pipeline) {
            throw new ModelNotFoundException("Pipeline not found", 404);
        }
        return $pipeline;
    }

    public function getPipelinesWithStages(?int $jobId = null, ?int $companyId = null)
    {
        $query = Pipeline::with([
            'job.company',
            'job',
            'stages'
        ]);

        if ($jobId !== null) {
            $query->where('job_id', $jobId);
        }

        if ($companyId !== null) {
            $query->whereHas('job', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }

        return $query->get();
    }


    public function getCandidatesByJobIdAndPipelineStage(int $jobId, int $pipelineStageId)
    {
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
                    'order' => $item->pipelineStage->order,
                ],
                'scorecards' => $item->candidate->scorecards->map(function ($scorecard) {
                    return [
                        'scorerate_id' => $scorecard->scorerate_id,
                        'scorelabel' => $scorecard->scorelabel ? $scorecard->scorelabel->label : null,
                        'max_score' => $scorecard->scorelabel ? $scorecard->scorelabel->max_score : null,
                    ];
                }),
                'moved_at' => $item->moved_at,
                'notes' => $item->notes,
            ];
        });
    }

    public function getPipelineCandidates(int $pipelineId, ?int $jobId = null)
    {
        $pipeline = $this->getPipelineById($pipelineId);

        $query = CandidatePipelineStage::with([
            'candidate.user',
            'pipelineStage',
            'job'
        ])->whereHas('pipelineStage', function ($q) use ($pipelineId) {
            $q->where('pipeline_id', $pipelineId);
        });

        if ($jobId !== null) {
            $query->where('job_id', $jobId);
        }

        return $query->get();
    }

    public function getPipelineCandidatesByStage(int $pipelineId, int $stageId, ?int $jobId = null)
    {
        $pipeline = $this->getPipelineById($pipelineId);

        $query = CandidatePipelineStage::with([
            'candidate.user',
            'pipelineStage',
            'job'
        ])->where('pipeline_stage_id', $stageId)
          ->whereHas('pipelineStage', function ($q) use ($pipelineId) {
              $q->where('pipeline_id', $pipelineId);
          });

        if ($jobId !== null) {
            $query->where('job_id', $jobId);
        }

        return $query->get();
    }

    public function getAllCandidatePipelineStages(?int $id = null)
    {
        if ($id === null) {
            return CandidatePipelineStage::with(['candidate.user', 'pipelineStage', 'job'])->get();
        }
        $item = CandidatePipelineStage::with(['candidate.user', 'pipelineStage', 'job'])->find($id);
        if (!$item) {
            throw new ModelNotFoundException("CandidatePipelineStage not found", 404);
        }
        return $item;
    }

    public function createOrUpdateCandidatePipelineStage(array $data, ?int $id = null)
    {
        if ($id === null) {
            $item = new CandidatePipelineStage();
        } else {
            $item = CandidatePipelineStage::find($id);
            if (!$item) {
                throw new ModelNotFoundException("CandidatePipelineStage not found", 404);
            }
        }
        $item->candidate_id = $data['candidate_id'] ?? $item->candidate_id;
        $item->pipeline_stage_id = $data['pipeline_stage_id'] ?? $item->pipeline_stage_id;
        $item->job_id = $data['job_id'] ?? $item->job_id;
        $item->moved_at = $data['moved_at'] ?? $item->moved_at;
        $item->notes = $data['notes'] ?? $item->notes;
        $item->save();
        return $item->load(['candidate.user', 'pipelineStage', 'job']);
    }

    public function deleteCandidatePipelineStage(int $id)
    {
        $item = CandidatePipelineStage::find($id);
        if (!$item) {
            throw new ModelNotFoundException("CandidatePipelineStage not found", 404);
        }
        $item->delete();
        return true;
    }
}


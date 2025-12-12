<?php

namespace App\Http\Controllers;

use App\Services\CandidateService;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CandidateController extends Controller
{
    public function __construct(private CandidateService $service){
    }

    public function getAllPipelines(Request $request){
        try {
            $jobId = $request->query('job_id') ? (int) $request->query('job_id') : null;
            $companyId = $request->query('company_id') ? (int) $request->query('company_id') : null;
            return $this->responseJSON($this->service->getAllPipelines($jobId, $companyId));
        } catch (\Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }

    public function getPipelineById($id)
    {
        try {
            return $this->responseJSON($this->service->getPipelineById((int) $id));
        } catch (ModelNotFoundException $e) {
            return $this->responseJSON($e->getMessage(), "failure", 404);
        } catch (\Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }

    public function getPipelinesWithStages(Request $request)
    {
        try {
            $jobId = $request->query('job_id') ? (int) $request->query('job_id') : null;
            $companyId = $request->query('company_id') ? (int) $request->query('company_id') : null;
            return $this->responseJSON($this->service->getPipelinesWithStages($jobId, $companyId));
        } catch (\Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }

    public function getPipelineCandidates(Request $request, $pipelineId)
    {
        try {
            $jobId = $request->query('job_id') ? (int) $request->query('job_id') : null;
            return $this->responseJSON($this->service->getPipelineCandidates((int) $pipelineId, $jobId));
        } catch (ModelNotFoundException $e) {
            return $this->responseJSON($e->getMessage(), "failure", 404);
        } catch (\Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }

    public function getPipelineCandidatesByStage(Request $request, $pipelineId, $stageId)
    {
        try {
            $jobId = $request->query('job_id') ? (int) $request->query('job_id') : null;
            return $this->responseJSON($this->service->getPipelineCandidatesByStage((int) $pipelineId, (int) $stageId, $jobId));
        } catch (ModelNotFoundException $e) {
            return $this->responseJSON($e->getMessage(), "failure", 404);
        } catch (\Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }
    public function getCandidatesByJobIdAndPipelineStage($jobId, $pipelineStageId)
    {
        try {
            return $this->responseJSON($this->service->getCandidatesByJobIdAndPipelineStage((int) $jobId, (int) $pipelineStageId));
        } catch (\Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }

    public function getAllCandidatePipelineStages($id = null)
    {
        try {
            $id = $id ? (int) $id : null;
            return $this->responseJSON($this->service->getAllCandidatePipelineStages($id));
        } catch (\Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }

    public function createOrUpdateCandidatePipelineStage(Request $request, $id = "add")
    {
        try {
            $itemId = ($id === "add") ? null : (int) $id;
            $data = $request->only([
                'candidate_id',
                'pipeline_stage_id',
                'job_id',
                'moved_at',
                'notes'
            ]);
            return $this->responseJSON($this->service->createOrUpdateCandidatePipelineStage($data, $itemId));
        } catch (\Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }

    public function deleteCandidatePipelineStage($id)
    {
        try {
            $this->service->deleteCandidatePipelineStage((int) $id);
            return $this->responseJSON(null, "success", 204);
        } catch (\Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }
}


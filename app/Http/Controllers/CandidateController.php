<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetAllPipelinesRequest;
use App\Http\Requests\GetCandidateByJobAndPipelineRequest;
use App\Services\CandidateService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CandidateController extends Controller{

    public function __construct(private CandidateService $service){}

    // public function getAllPipelines(GetAllPipelinesRequest $request): JsonResponse{
    //     try {
    //         $jobId = $request->validate()['job_id'];
    //         $companyId = $request->validate()['company_id'];

    //         $pipelines = $this->service->getAllPipelines($jobId, $companyId);
    //         return $this->responseJSON($pipelines, "Pipelines retrieved successfully");
    //     } catch (Exception $e) {
    //         return $this->handleException($e, 'Get pipelines');
    //     }
    // }

    // public function getPipelineById(int $id): JsonResponse{
    //     try {
    //         return $this->responseJSON($this->service->getPipelineById($id));
    //     } catch (Exception $e) {
    //         return $this->handleException($e, 'Get pipeline');
    //     }
    // }

    // public function getPipelinesWithStages(Request $request){
    //     try {
    //         $jobId = $request->query('job_id') ? (int) $request->query('job_id') : null;
    //         $companyId = $request->query('company_id') ? (int) $request->query('company_id') : null;
    //         return $this->responseJSON($this->service->getPipelinesWithStages($jobId, $companyId));
    //     } catch (Exception $e) {
    //         return $this->responseJSON($e->getMessage(), "failure", 400);
    //     }
    // }

    // public function getPipelineCandidates(Request $request, $pipelineId){
    //     try {
    //         $jobId = $request->query('job_id') ? (int) $request->query('job_id') : null;
    //         return $this->responseJSON($this->service->getPipelineCandidates((int) $pipelineId, $jobId));
    //     } catch (ModelNotFoundException $e) {
    //         return $this->responseJSON($e->getMessage(), "failure", 404);
    //     } catch (Exception $e) {
    //         return $this->responseJSON($e->getMessage(), "failure", 400);
    //     }
    // }

    // public function getPipelineCandidatesByStage(Request $request, $pipelineId, $stageId){
    //     try {
    //         $jobId = $request->query('job_id') ? (int) $request->query('job_id') : null;
    //         return $this->responseJSON($this->service->getPipelineCandidatesByStage((int) $pipelineId, (int) $stageId, $jobId));
    //     } catch (ModelNotFoundException $e) {
    //         return $this->responseJSON($e->getMessage(), "failure", 404);
    //     } catch (Exception $e) {
    //         return $this->responseJSON($e->getMessage(), "failure", 400);
    //     }
    // }

    public function getCandidatesByJobIdAndPipelineStage(GetCandidateByJobAndPipelineRequest $request , int $jobId){

        try {
            $pipelineStageId = $request->pipeline_stage_id ?? $request->stage_name;
            $perPage = $request->per_page;
            $page = $request->page;


            $resp =  $this->service->getCandidatesByJobIdAndPipelineStage(
                    (int) $jobId,
                    $pipelineStageId,
                    $perPage,
                    $page
            );
            return $this->responseJSON($resp);
        } catch (Exception $e) {
            return $this->responseJSON($e->getMessage(), "Failed to get candidates", 400);
        }
    }

    public function getAllCandidatePipelineStages($id = null){
        try {
            $id = $id ? (int) $id : null;
            return $this->responseJSON($this->service->getAllCandidatePipelineStages($id));
        } catch (Exception $e) {
            return $this->responseJSON($e->getMessage(), "Failed to get candidate pipeline stages", 400);
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
        } catch (Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }

    public function deleteCandidatePipelineStage(int $id): JsonResponse
    {
        try {
            $this->service->deleteCandidatePipelineStage($id);
            return $this->responseJSON(null, "success", 204);
        } catch (Exception $e) {
            return $this->handleException($e, 'Delete candidate pipeline stage');
        }
    }

    public function getCandidateProfile(Request $request, $candidateId)
    {
        try {
            $jobId = $request->query('job_id') ? (int) $request->query('job_id') : null;
            return $this->responseJSON($this->service->getCandidateProfile((int) $candidateId, $jobId));
        } catch (ModelNotFoundException $e) {
            return $this->responseJSON($e->getMessage(), "failure", 404);
        } catch (Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }

    public function createCandidate(Request $request)
    {
        try {
            $data = $request->only([
                'full_name',
                'email',
                'job_id',
                'stage',
                'recruiter_id',
                'level',
                'age',
                'phone_number',
                'location',
                'github_url',
                'linkedin_url',
                'source',
            ]);
            
            return $this->responseJSON($this->service->createCandidate($data), 'success', 201);
        } catch (ValidationException $e) {
            return $this->responseJSON($e->errors(), 'Validation failed', 422);
        } catch (ModelNotFoundException $e) {
            return $this->responseJSON($e->getMessage(), "failure", 404);
        } catch (Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }

    public function updateCandidateStage(Request $request, $candidateId)
    {
        try {
            $jobId = $request->input('job_id');
            $stage = $request->input('stage'); // Can be stage name or ID
            
            if (!$jobId || !$stage) {
                return $this->responseJSON("job_id and stage are required", "failure", 400);
            }
            
            return $this->responseJSON($this->service->updateCandidateStage((int) $candidateId, (int) $jobId, $stage));
        } catch (ModelNotFoundException $e) {
            return $this->responseJSON($e->getMessage(), "failure", 404);
        } catch (Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }
}


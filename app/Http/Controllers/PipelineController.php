<?php

namespace App\Http\Controllers;

use App\Services\PipelineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class PipelineController extends Controller
{
    public function __construct(
        private readonly PipelineService $pipelineService
    ) {
    }

    public function getStagesByJobId(int $job_id): JsonResponse
    {
        try {
            $pipeline = $this->pipelineService->getPipelineStagesByJobId($job_id);
            return $this->responseJSON($pipeline, 'success', 200);

        } catch (ModelNotFoundException $e) {
            return $this->responseJSON('NotFound', 'Pipeline not found for this job', 404);
        } catch (Exception $e) {
            return $this->responseJSON('PipelineError', 'Failed to fetch pipeline stages: ' . $e->getMessage(), 500);
        }
    }
}

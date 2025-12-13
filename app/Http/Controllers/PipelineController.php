<?php

namespace App\Http\Controllers;

use App\Services\PipelineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

class PipelineController extends Controller
{
    public function __construct(
        private readonly PipelineService $pipelineService
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $pipeline = $this->pipelineService->createPipeline(
                (int) $request->input('job_id'),
                (string) $request->input('job_title'),
                (array) $request->input('stages', [])
            );

            return $this->responseJSON($pipeline, 'success', 201);

        } catch (ValidationException $e) {
            return $this->responseJSON($e->errors(), 'failure', 422);

        } catch (Exception $e) {
            return $this->responseJSON('PipelineError', 'Create pipeline failed: ' . $e->getMessage(), 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\OfferStageWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class OfferStageWorkflowController extends Controller
{
    public function __construct(private readonly OfferStageWorkflowService $service)
    {   
    }

    public function trigger(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'candidate_pipeline_stage_id' => ['required', 'integer', 'exists:candidate_pipeline_stages,id'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $recruiterId = auth()->id();

        if (!$recruiterId) {
            return response()->json([
                'status' => 'failure',
                'payload' => [
                    'message' => 'Unauthenticated',
                ],
            ], 401);
        }

        $candidatePipelineStageId = $request->input('candidate_pipeline_stage_id');
        $result = $this->service->triggerWorkflow($candidatePipelineStageId);

        if ($result['success']) {
            $offerPacketStep = $result['steps']['offer_packet'] ?? [];
            $n8nResponse = $offerPacketStep['n8n_response'] ?? [];
            
            $responseData = [
                'success' => true,
                'candidate_pipeline_stage_id' => $result['candidate_pipeline_stage_id'],
                'candidate_id' => $result['candidate_id'],
                'job_id' => $result['job_id'],
                'offer_id' => $result['offer_id'],
                'candidate_email' => $result['candidate_email'] ?? null,
                'recruiter_email' => $result['recruiter_email'] ?? null,
                'file_path' => $n8nResponse['file_path'] ?? $offerPacketStep['file_path'] ?? null,
                'file_type' => $n8nResponse['file_type'] ?? $offerPacketStep['file_type'] ?? null,
                'generated_at' => $n8nResponse['generated_at'] ?? null,
                'status' => $n8nResponse['status'] ?? $offerPacketStep['status'] ?? 'success',
                'steps' => $result['steps'],
                'errors' => $result['errors'] ?? [],
            ];

            return response()->json([
                'status' => 'success',
                'payload' => $responseData,
            ]);
        } else {
            return response()->json([
                'status' => 'failure',
                'payload' => $result,
            ], 400);
        }
    }
}


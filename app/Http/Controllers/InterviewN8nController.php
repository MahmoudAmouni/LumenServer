<?php

namespace App\Http\Controllers;

use App\Services\InterviewN8nService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InterviewN8nController extends Controller
{
    public function __construct(
        private readonly InterviewN8nService $interviewN8nService
    ) {}

    public function summarizeAndScore(Request $request, int $interviewId): JsonResponse
    {
        try {
            $request->validate(['notes' => ['required', 'string']]);
            $result = $this->interviewN8nService->summarizeAndScoreInterview($interviewId, $request->input('notes'));
            return $this->responseJSON($result, 'success', 200);
        } catch (\Exception $e) {
            return $this->responseJSON($e->getMessage(), 'failure', 400);
        }
    }

    public function sendNextStepEmail(Request $request, int $candidatePipelineStageId): JsonResponse
    {
        try {
            $result = $this->interviewN8nService->sendNextStepEmail($candidatePipelineStageId);
            return $this->responseJSON($result, 'success', 200);
        } catch (\Exception $e) {
            return $this->responseJSON($e->getMessage(), 'failure', 400);
        }
    }
}
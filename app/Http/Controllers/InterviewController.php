<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateInterviewRequest;
use App\Services\InterviewService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class InterviewController extends Controller
{
    public function __construct(
        private readonly InterviewService $interviewService
    ) {
    }

    public function getInterviewsByJobId(int $jobId): JsonResponse
    {
        try {
            $interviews = $this->interviewService->getInterviewsByJobId($jobId);
            return $this->responseJSON($interviews, 'success', 200);
        } catch (ModelNotFoundException $e) {
            return $this->responseJSON('Job or interviews not found', 'failure', 404);
        } catch (Exception $e) {
            return $this->responseJSON('Failed to fetch interviews: ' . $e->getMessage(), 'failure', 500);
        }
    }

    public function createInterview(CreateInterviewRequest $request): JsonResponse
    {
        try {
            $interview = $this->interviewService->createInterview($request->validated());
            return $this->responseJSON($interview, 'success', 201);
        } catch (Exception $e) {
            return $this->responseJSON('Interview creation failed: ' . $e->getMessage(), 'failure', 500);
        }
    }

    public function updateInterview(int $id, CreateInterviewRequest $request): JsonResponse
    {
        try {
            $interview = $this->interviewService->updateInterview($id, $request->validated());
            return $this->responseJSON($interview, 'success', 200);
        } catch (ModelNotFoundException $e) {
            return $this->responseJSON('Interview not found', 'failure', 404);
        } catch (Exception $e) {
            return $this->responseJSON('Update failed: ' . $e->getMessage(), 'failure', 500);
        }
    }
}
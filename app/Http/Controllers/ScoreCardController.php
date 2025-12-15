<?php

namespace App\Http\Controllers;

use App\Services\ScorecardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ScorecardController extends Controller
{
    public function __construct(
        private readonly ScorecardService $scorecardService
    ) {}

    public function getScorecardsByInterviewId(int $interviewId): JsonResponse
    {
        try {
            $scorecards = $this->scorecardService->getScorecardsByInterviewId($interviewId);
            return $this->responseJSON($scorecards, 'success', 200);
        } catch (ModelNotFoundException $e) {
            return $this->responseJSON('Interview or scorecards not found', 'failure', 404);
        } catch (\Exception $e) {
            return $this->responseJSON('Failed to fetch scorecards: ' . $e->getMessage(), 'failure', 500);
        }
    }

    public function createScorecardsForInterview(Request $request): JsonResponse
    {
        try {
            $data = $this->validateCreateRequest($request);
            $scorecards = $this->scorecardService->createScorecardsForInterview($data);
            return $this->responseJSON($scorecards, 'success', 201);
        } catch (ValidationException $e) {
            return $this->responseJSON($e->errors(), 'Validation failed', 422);
        } catch (\Exception $e) {
            return $this->responseJSON('Scorecard creation failed: ' . $e->getMessage(), 'failure', 500);
        }
    }

    private function validateCreateRequest(Request $request): array
    {
        return $request->validate([
            'candidate_id' => ['required', 'integer', 'exists:candidates,id'],
            'job_id' => ['required', 'integer', 'exists:jobs,id'],
            'interview_id' => ['required', 'integer', 'exists:interviews,id'],
            'label_names' => ['required', 'array', 'min:1'],
            'label_names.*' => ['required', 'string'],
        ]);
    }
}
<?php

namespace App\Http\Controllers;

use App\Services\ScorecardService;
use App\Http\Requests\CreateScorecardRequest;
use Illuminate\Http\JsonResponse;
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

    public function createScorecardsForInterview(CreateScorecardRequest $request): JsonResponse
    {
        try {
            $scorecards = $this->scorecardService->createScorecardsForInterview($request->validated());
            return $this->responseJSON($scorecards, 'success', 201);
        } catch (\Exception $e) {
            return $this->responseJSON('Scorecard creation failed: ' . $e->getMessage(), 'failure', 500);
        }
    }
}
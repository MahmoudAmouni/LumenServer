<?php

namespace App\Http\Controllers;

use App\Services\ScorecardService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ScorecardController extends Controller
{
    public function __construct(private ScorecardService $service)
    {
    }

    public function getAllScorecards(Request $request)
    {
        try {
            $candidateId = $request->query('candidate_id') ? (int) $request->query('candidate_id') : null;
            $interviewId = $request->query('interview_id') ? (int) $request->query('interview_id') : null;
            $jobId = $request->query('job_id') ? (int) $request->query('job_id') : null;
            $status = $request->query('status');
            return $this->responseJSON($this->service->getAllScorecards($candidateId, $interviewId, $jobId, $status));
        } catch (\Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }

    public function getScorecardById($id)
    {
        try {
            return $this->responseJSON($this->service->getScorecardById((int) $id));
        } catch (\Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }

    public function createScorecard(Request $request)
    {
        try {
            $data = $request->all();
            $scorecard = $this->service->createScorecard($data);
            return $this->responseJSON($scorecard, "success", 201);
        } catch (ValidationException $e) {
            return $this->responseJSON($e->errors(), "failure", 422);
        } catch (\Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }

    public function updateScorecard(Request $request, $id)
    {
        try {
            $data = $request->all();
            $scorecard = $this->service->updateScorecard((int) $id, $data);
            return $this->responseJSON($scorecard);
        } catch (ValidationException $e) {
            return $this->responseJSON($e->errors(), "failure", 422);
        } catch (\Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }
}
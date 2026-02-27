<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateInterviewNotesRequest;
use App\Services\InterviewService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Support\Facades\Log;

class InterviewController extends Controller
{
    public function __construct(
        private readonly InterviewService $interviewService
    ) {
    }
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $result = $this->interviewService->updateInterview($id, $request->all());
            return $this->responseJSON($result, 'success', 200);
        } catch (ModelNotFoundException $e) {
            return $this->responseJSON(null, 'Interview not found', 404);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            return $this->responseJSON(null, 'Update failed', 500);
        }
    }

    public function updateInterviewNotes(UpdateInterviewNotesRequest $request): JsonResponse
    {
        try {

            $candidateId = $request->validated('candidate_id');
            $jobId = $request->validated('job_id');
            $notes = $request->validated('notes', '');

            $result = $this->interviewService->updateInterviewNotesByCandidateAndJob(
                $candidateId,
                $jobId,
                $notes
            );
            
            return $this->responseJSON($result, 'success', 200);
        } catch (ModelNotFoundException $e) {
            return $this->responseJSON(null, 'Interview not found', 404);
        } catch (Exception $e) {
            return $this->responseJSON(null, 'Update failed: ' . $e->getMessage(), 500);
        }
    }
}


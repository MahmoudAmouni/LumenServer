<?php

namespace App\Http\Controllers;

use App\Services\InterviewN8nService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InterviewN8nController extends Controller
{
    public function __construct(private readonly InterviewN8nService $interviewN8nService) {}

    public function sendPostInterviewWorkflow(Request $request, int $interviewId): JsonResponse
    {
        try {
            $request->validate([
                'notes' => ['required', 'string'],
            ]);

            $result = $this->interviewN8nService->sendPostInterviewWorkflow(
                $interviewId,
                $request->input('notes')
            );

            if (!$result['success']) {
                return $this->responseJSON(
                    $result,
                    'failure',
                    400
                );
            }

            return $this->responseJSON(
                $result['data'],
                'success',
                200
            );
        } catch (ValidationException $e) {
            return $this->responseJSON(
                ['errors' => $e->errors()],
                'failure',
                422
            );
        } catch (\Exception $e) {
            return $this->responseJSON(
                ['error' => $e->getMessage()],
                'failure',
                500
            );
        }
    }
}

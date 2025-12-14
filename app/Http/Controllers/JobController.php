<?php
namespace App\Http\Controllers;

use App\Services\JobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class JobController extends Controller
{
    public function __construct(
        private readonly JobService $jobService
    ) {}

    public function getJobsByCompanyId(Request $request, int $companyId): JsonResponse
    {
        try {
            $jobs = $this->jobService->getJobsByCompanyId($request, $companyId);
            return $this->responseJSON($jobs, 'success', 200);
        } catch (ModelNotFoundException $e) {
            return $this->responseJSON('Company or jobs not found', 'failure', 404);
        } catch (\Exception $e) {
            return $this->responseJSON(null, 'Failed to fetch jobs: ' . $e->getMessage(), 500);
        }
    }

    public function createJob(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            $job = $this->jobService->createJob($data);
            return $this->responseJSON($job, 'success', 201);
        } catch (ValidationException $e) {
            return $this->responseJSON($e->errors(), 'Validation failed', 422);
        } catch (\Exception $e) {
            return $this->responseJSON(null, 'Job creation failed: ' . $e->getMessage(), 500);
        }
    }

    public function updateJob(Request $request, int $id): JsonResponse
    {
        try {
            $data = $request->all();
            $job = $this->jobService->updateJob($id, $data);
            return $this->responseJSON($job, 'success', 200);
        } catch (ModelNotFoundException $e) {
            return $this->responseJSON('Job not found', 'failure', 404);
        } catch (ValidationException $e) {
            return $this->responseJSON($e->errors(), 'Validation failed', 422);
        } catch (\Exception $e) {
            return $this->responseJSON(null, 'Update failed: ' . $e->getMessage(), 500);
        }
    }

    public function deleteJob(int $id): JsonResponse
    {
        try {
            $this->jobService->deleteJob($id);
            return $this->responseJSON(['message' => 'Job deleted successfully'], 'success', 200);
        } catch (ModelNotFoundException $e) {
            return $this->responseJSON('Job not found', 'failure', 404);
        } catch (\Exception $e) {
            return $this->responseJSON(null, 'Delete failed: ' . $e->getMessage(), 500);
        }
    }
}
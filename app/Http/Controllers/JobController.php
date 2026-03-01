<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateJobRequest;
use App\Http\Requests\UpdateJobRequest;
use App\Services\JobService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
        } catch (Exception $e) {
            return $this->handleException($e, 'Fetch jobs');
        }
    }

    public function createJob(CreateJobRequest $request): JsonResponse{
        try {
            $job = $this->jobService->createJob($request->validated());
            return $this->responseJSON($job, 'success', 201);
        } catch (ValidationException $e) {
            return $this->responseJSON($e->errors(), 'Validation failed', 422);
        } catch (Exception $e) {
            return $this->handleException($e, 'Create job');
        }
    }

    public function updateJob(UpdateJobRequest $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate($request->rules());
            $job = $this->jobService->updateJob($id, $validated);
            return $this->responseJSON($job, 'success', 200);
        } catch (ValidationException $e) {
            return $this->responseJSON($e->errors(), 'Validation failed', 422);
        } catch (Exception $e) {
            return $this->handleException($e, 'Update job');
        }
    }

    public function deleteJob(int $id): JsonResponse
    {
        try {
            $this->jobService->deleteJob($id);
            return $this->responseJSON(null, 'success', 204);
        } catch (Exception $e) {
            return $this->handleException($e, 'Delete job');
        }
    }
}
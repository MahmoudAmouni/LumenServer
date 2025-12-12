<?php

namespace App\Http\Controllers;

use App\Services\JobService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class JobController extends Controller
{
    public function __construct(private JobService $service)
    {
    }

    public function getAllJobs(Request $request)
    {
        try {
            $recruiterId = $request->query('recruiter_id') ? (int) $request->query('recruiter_id') : null;
            $companyId = $request->query('company_id') ? (int) $request->query('company_id') : null;
            $status = $request->query('status');
            return $this->responseJSON($this->service->getAllJobs($recruiterId, $companyId, $status));
        } catch (\Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }

    public function getJobById($id)
    {
        try {
            return $this->responseJSON($this->service->getJobById((int) $id));
        } catch (\Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }

    public function createJob(Request $request)
    {
        try {
            $data = $request->all();
            $job = $this->service->createJob($data);
            return $this->responseJSON($job, "success", 201);
        } catch (ValidationException $e) {
            return $this->responseJSON($e->errors(), "failure", 422);
        } catch (\Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }

    public function updateJob(Request $request, $id)
    {
        try {
            $data = $request->all();
            $job = $this->service->updateJob((int) $id, $data);
            return $this->responseJSON($job);
        } catch (ValidationException $e) {
            return $this->responseJSON($e->errors(), "failure", 422);
        } catch (\Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }

    public function deleteJob($id)
    {
        try {
            $this->service->deleteJob((int) $id);
            return $this->responseJSON(null, "success", 204);
        } catch (\Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }
}
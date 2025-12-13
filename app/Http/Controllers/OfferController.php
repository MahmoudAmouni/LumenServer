<?php

namespace App\Http\Controllers;

use App\Services\OfferService;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class OfferController extends Controller
{
    public function __construct(private OfferService $service)
    {
    }

    public function getAllOffers(Request $request)
    {
        try {
            $candidateId = $request->query('candidate_id') ? (int) $request->query('candidate_id') : null;
            $jobId = $request->query('job_id') ? (int) $request->query('job_id') : null;
            $status = $request->query('status');
            $recruiterId = $request->query('recruiter_id') ? (int) $request->query('recruiter_id') : null;
            return $this->responseJSON($this->service->getAllOffers($candidateId, $jobId, $status, $recruiterId));
        } catch (\Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }

    public function getOfferById($id)
    {
        try {
            return $this->responseJSON($this->service->getOfferById((int) $id));
        } catch (ModelNotFoundException $e) {
            return $this->responseJSON($e->getMessage(), "failure", 404);
        } catch (\Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }

    public function createOrUpdateOffer(Request $request, $id = "add")
    {
        try {
            $offerId = ($id === "add") ? null : (int) $id;
            $data = $request->only([
                'candidate_id',
                'job_id',
                'salary',
                'start_date',
                'contract_type',
                'offer_letter_template',
                'status',
                'recruiter_id'
            ]);
            return $this->responseJSON($this->service->createOrUpdateOffer($data, $offerId));
        } catch (ModelNotFoundException $e) {
            return $this->responseJSON($e->getMessage(), "failure", 404);
        } catch (\Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }

    public function deleteOffer($id)
    {
        try {
            $this->service->deleteOffer((int) $id);
            return $this->responseJSON(null, "success", 204);
        } catch (ModelNotFoundException $e) {
            return $this->responseJSON($e->getMessage(), "failure", 404);
        } catch (\Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }

    public function triggerOfferStageWorkflow(Request $request, $candidatePipelineStageId)
    {
        try {
            $result = $this->service->triggerOfferStageWorkflow((int) $candidatePipelineStageId);
            return $this->responseJSON($result);
        } catch (ModelNotFoundException $e) {
            return $this->responseJSON($e->getMessage(), "failure", 404);
        } catch (\Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }
}


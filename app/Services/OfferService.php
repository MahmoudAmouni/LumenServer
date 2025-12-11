<?php

namespace App\Services;

use App\Models\offer;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class OfferService
{
    public function getAllOffers(?int $candidateId = null, ?int $jobId = null, ?string $status = null, ?int $recruiterId = null)
    {
        $query = offer::with(['candidate', 'job', 'recruiter']);

        if ($candidateId !== null) {
            $query->where('candidate_id', $candidateId);
        }

        if ($jobId !== null) {
            $query->where('job_id', $jobId);
        }

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($recruiterId !== null) {
            $query->where('recruiter_id', $recruiterId);
        }

        return $query->get();
    }

    public function getOfferById(int $id)
    {
        $offer = offer::with(['candidate', 'job', 'recruiter'])->find($id);
        if (!$offer) {
            throw new ModelNotFoundException("Offer not found", 404);
        }
        return $offer;
    }

    public function createOrUpdateOffer(array $data, ?int $id = null)
    {
        if ($id === null) {
            $offer = new offer();
        } else {
            $offer = offer::find($id);
            if (!$offer) {
                throw new ModelNotFoundException("Offer not found", 404);
            }
        }
        $offer->candidate_id = $data['candidate_id'] ?? $offer->candidate_id;
        $offer->job_id = $data['job_id'] ?? $offer->job_id;
        $offer->salary = $data['salary'] ?? $offer->salary;
        $offer->start_date = $data['start_date'] ?? $offer->start_date;
        $offer->contract_type = $data['contract_type'] ?? $offer->contract_type;
        $offer->offer_letter_template = $data['offer_letter_template'] ?? $offer->offer_letter_template;
        $offer->status = $data['status'] ?? $offer->status ?? 'draft';
        $offer->recruiter_id = $data['recruiter_id'] ?? $offer->recruiter_id;
        $offer->save();
        return $offer->load(['candidate', 'job', 'recruiter']);
    }

    public function deleteOffer(int $id)
    {
        $offer = offer::find($id);
        if (!$offer) {
            throw new ModelNotFoundException("Offer not found", 404);
        }
        $offer->delete();
        return true;
    }
}


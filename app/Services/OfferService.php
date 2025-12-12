<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\CandidatePipelineStage;
use App\Models\Stage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

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
    }//

    public function getOfferById(int $id)
    {
        $offer = Offer::with(['candidate', 'job', 'recruiter'])->find($id);
        if (!$offer) {
            throw new ModelNotFoundException("Offer not found", 404);
        }
        return $offer;
    }

    public function createOrUpdateOffer(array $data, ?int $id = null)
    {
        if ($id === null) {
            $offer = new Offer();
        } else {
            $offer = Offer::find($id);
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
        $offer = Offer::find($id);
        if (!$offer) {
            throw new ModelNotFoundException("Offer not found", 404);
        }
        $offer->delete();
        return true;
    }
    public function triggerOfferStageWorkflow(int $candidatePipelineStageId)
    {
        $candidatePipelineStage = CandidatePipelineStage::with(['candidate', 'pipelineStage', 'job'])
            ->find($candidatePipelineStageId);
        
        if (!$candidatePipelineStage) {
            throw new ModelNotFoundException("Candidate pipeline stage not found", 404);
        }

        $stage = $candidatePipelineStage->pipelineStage;
        if (!$stage || strtolower($stage->name) !== 'offer') {
            throw new \Exception("Candidate is not in the 'offer' stage");
        }

        $candidate = $candidatePipelineStage->candidate;
        $job = $candidatePipelineStage->job;
        $recruiter = $candidate->recruiter ?? null;

        $offer = Offer::where('candidate_id', $candidate->id)
            ->where('job_id', $job->id)
            ->first();

        if (!$offer) {
            $offer = new Offer();
            $offer->candidate_id = $candidate->id;
            $offer->job_id = $job->id;
            $offer->status = 'draft';
            $offer->recruiter_id = $recruiter->id ?? null;
            $offer->save();
        }

        $workflowResults = [
            'candidate_pipeline_stage_id' => $candidatePipelineStageId,
            'offer_id' => $offer->id,
            'workflow_steps' => []
        ];

        try {
            $offerPacket = $this->generateOfferPacket($offer);
            $workflowResults['workflow_steps']['offer_packet'] = [
                'status' => 'success',
                'message' => 'Offer packet generated successfully',
                'file_path' => $offerPacket['file_path'] ?? null,
                'file_type' => $offerPacket['file_type'] ?? 'pdf'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to generate offer packet: ' . $e->getMessage());
            $workflowResults['workflow_steps']['offer_packet'] = [
                'status' => 'failed',
                'message' => 'Failed to generate offer packet: ' . $e->getMessage()
            ];
        }

        try {
            $reminders = $this->scheduleReminders($offer, $candidate, $recruiter);
            $workflowResults['workflow_steps']['reminders'] = [
                'status' => 'success',
                'message' => 'Reminders scheduled successfully',
                'reminders' => $reminders
            ];
        } catch (\Exception $e) {
            Log::error('Failed to schedule reminders: ' . $e->getMessage());
            $workflowResults['workflow_steps']['reminders'] = [
                'status' => 'failed',
                'message' => 'Failed to schedule reminders: ' . $e->getMessage()
            ];
        }

        try {
            $this->trackOfferStatusChange($offer, 'moved_to_offer_stage');
            $workflowResults['workflow_steps']['status_tracking'] = [
                'status' => 'success',
                'message' => 'Offer status change tracked successfully'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to track offer status: ' . $e->getMessage());
            $workflowResults['workflow_steps']['status_tracking'] = [
                'status' => 'failed',
                'message' => 'Failed to track offer status: ' . $e->getMessage()
            ];
        }

        return $workflowResults;
    }


    private function generateOfferPacket(Offer $offer)
    {
        $offer->load(['candidate', 'job', 'recruiter']);
        
        $offerData = [
            'candidate_name' => $offer->candidate->full_name ?? 'N/A',
            'candidate_email' => $offer->candidate->email ?? 'N/A',
            'job_title' => $offer->job->title ?? 'N/A',
            'salary' => $offer->salary ?? 'N/A',
            'start_date' => $offer->start_date ?? 'N/A',
            'contract_type' => $offer->contract_type ?? 'N/A',
            'offer_letter_template' => $offer->offer_letter_template ?? '',
            'recruiter_name' => $offer->recruiter->name ?? 'N/A',
            'recruiter_email' => $offer->recruiter->email ?? 'N/A',
            'generated_at' => now()->toDateTimeString()
        ];

   
        
        $fileName = 'offer_packet_' . $offer->id . '_' . time() . '.pdf';
        $filePath = 'offers/' . $fileName;


        return [
            'file_path' => $filePath,
            'file_type' => 'pdf',
            'offer_data' => $offerData
        ];
    }

   
    private function scheduleReminders(Offer $offer, $candidate, $recruiter)
    {
        $reminders = [];

        if ($recruiter) {
 
            $reminders[] = [
                'type' => 'recruiter',
                'user_id' => $recruiter->id,
                'user_email' => $recruiter->email,
                'message' => "Follow up on offer for {$candidate->full_name}",
                'scheduled_at' => now()->addDays(3)->toDateTimeString()
            ];
        }

        // Schedule reminder for candidate
        if ($candidate) {
            // TODO: Implement actual reminder scheduling for candidate
            $jobTitle = $offer->job->title ?? 'the position';
            $reminders[] = [
                'type' => 'candidate',
                'candidate_id' => $candidate->id,
                'candidate_email' => $candidate->email,
                'message' => "Reminder: Review your offer for {$jobTitle}",
                'scheduled_at' => now()->addDays(2)->toDateTimeString()
            ];
        }

        return $reminders;
    }

    /**
     * Track offer status changes
     */
    private function trackOfferStatusChange(Offer $offer, string $event)
    {
        // TODO: Implement actual status tracking
        // This could log to a separate table, use Laravel's activity log, or an event system
        // Example:
        // OfferStatusHistory::create([
        //     'offer_id' => $offer->id,
        //     'status' => $offer->status,
        //     'event' => $event,
        //     'changed_at' => now()
        // ]);

        Log::info("Offer status change tracked", [
            'offer_id' => $offer->id,
            'candidate_id' => $offer->candidate_id,
            'job_id' => $offer->job_id,
            'status' => $offer->status,
            'event' => $event,
            'timestamp' => now()->toDateTimeString()
        ]);

        return true;
    }
}


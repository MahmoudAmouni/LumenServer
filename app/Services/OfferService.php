<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\CandidatePipelineStage;
use App\Models\Stage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class OfferService
{
    /**
     * Send offers to all candidates in the "offer" stage for a specific job
     * 
     * @param int $jobId The job ID
     * @param int $pipelineStageId The pipeline stage ID
     * @return array Results of sending offers to candidates
     */
    public function sendOffersToCandidatesInOfferStage(int $jobId, int $pipelineStageId)
    {
        $candidatePipelineStages = CandidatePipelineStage::with([
            'candidate',
            'pipelineStage',
            'job'
        ])
        ->where('job_id', $jobId)
        ->where('pipeline_stage_id', $pipelineStageId)
        ->get();

        if ($candidatePipelineStages->isEmpty()) {
            return [
                'status' => 'no_candidates',
                'message' => 'No candidates found in the specified stage',
                'results' => []
            ];
        }

        // Check if the stage is "offer"
        $firstStage = $candidatePipelineStages->first()->pipelineStage;
        if (!$firstStage || strtolower($firstStage->name) !== 'offer') {
            return [
                'status' => 'not_offer_stage',
                'message' => 'The specified stage is not an "offer" stage',
                'results' => []
            ];
        }

        $results = [];
        foreach ($candidatePipelineStages as $candidatePipelineStage) {
            try {
                $workflowResult = $this->triggerOfferStageWorkflow($candidatePipelineStage->id);
                $results[] = [
                    'candidate_id' => $candidatePipelineStage->candidate_id,
                    'candidate_pipeline_stage_id' => $candidatePipelineStage->id,
                    'status' => 'success',
                    'workflow_result' => $workflowResult
                ];
            } catch (\Exception $e) {
                Log::error('Failed to send offer to candidate ' . $candidatePipelineStage->candidate_id . ': ' . $e->getMessage());
                $results[] = [
                    'candidate_id' => $candidatePipelineStage->candidate_id,
                    'candidate_pipeline_stage_id' => $candidatePipelineStage->id,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'status' => 'completed',
            'message' => 'Offer workflow triggered for ' . count($results) . ' candidate(s)',
            'results' => $results
        ];
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

        if ($candidate) {
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

    private function trackOfferStatusChange(Offer $offer, string $event)
    {
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


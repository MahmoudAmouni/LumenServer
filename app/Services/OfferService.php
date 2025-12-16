<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\CandidatePipelineStage;
use App\Models\Stage;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class OfferService
{
    public function getAllOffers(?int $candidateId = null, ?int $jobId = null, ?string $status = null, ?int $recruiterId = null)
    {
        $query = Offer::with(['candidate', 'job', 'recruiter']);

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
        $this->validateOfferId($id);

        $offer = Offer::with(['candidate', 'job', 'recruiter'])->find($id);
        
        if (!$offer) {
            throw new ModelNotFoundException("Offer not found", 404);
        }
        
        return $offer;
    }

    private function validateOfferId(int $id): void
    {
        $data = ['id' => $id];
        $rules = $this->getOfferIdValidationRules();

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    private function getOfferIdValidationRules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:offers,id'],
        ];
    }

    public function createOrUpdateOffer(array $data, ?int $id = null)
    {
        $isUpdate = $id !== null;
        
        if ($id !== null) {
            $data['id'] = $id;
        }

        $validated = $this->validateOfferData($data, $isUpdate);

        if ($id === null) {
            $offer = new Offer();
        } else {
            $offer = Offer::find($id);
            if (!$offer) {
                throw new ModelNotFoundException("Offer not found", 404);
            }
        }

        $offer->candidate_id = $validated['candidate_id'];
        $offer->job_id = $validated['job_id'];
        $offer->salary = $validated['salary'] ?? $offer->salary;
        $offer->start_date = $validated['start_date'] ?? $offer->start_date;
        $offer->contract_type = $validated['contract_type'] ?? $offer->contract_type;
        $offer->offer_letter_template = $validated['offer_letter_template'] ?? $offer->offer_letter_template;
        $offer->status = $validated['status'] ?? $offer->status ?? 'draft';
        $offer->recruiter_id = $validated['recruiter_id'] ?? $offer->recruiter_id;
        $offer->save();

        return $offer->load(['candidate', 'job', 'recruiter']);
    }

    public function deleteOffer(int $id)
    {
        $this->validateOfferId($id);

        $offer = Offer::find($id);
        
        if (!$offer) {
            throw new ModelNotFoundException("Offer not found", 404);
        }
        
        $offer->delete();
        
        return true;
    }

    public function sendOffersToCandidatesInOfferStage(int $jobId, int $pipelineStageId)
    {
        $this->validateSendOffersInput($jobId, $pipelineStageId);

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

        $firstStage = $candidatePipelineStages->first()->pipelineStage;
        if (!$firstStage || strtolower($firstStage->name) !== 'offer') {
            return [
                'status' => 'not_offer_stage',
                'message' => 'The specified stage is not an "offer" stage',
                'results' => []
            ];
        }

        $results = collect($candidatePipelineStages)
            ->map(function ($candidatePipelineStage) {
                try {
                    $workflowResult = $this->triggerOfferStageWorkflow($candidatePipelineStage->id);
                    return [
                        'candidate_id'                 => $candidatePipelineStage->candidate_id,
                        'candidate_pipeline_stage_id'  => $candidatePipelineStage->id,
                        'status'                       => 'success',
                        'workflow_result'              => $workflowResult,
                    ];
                } catch (Exception $e) {
                    Log::error(
                        'Failed to send offer to candidate ' .
                        $candidatePipelineStage->candidate_id .
                        ': ' . $e->getMessage()
                    );
                    return [
                        'candidate_id'                 => $candidatePipelineStage->candidate_id,
                        'candidate_pipeline_stage_id'  => $candidatePipelineStage->id,
                        'status'                       => 'failed',
                        'error'                        => $e->getMessage(),
                    ];
                }
            })
            ->all();

        return [
            'status' => 'completed',
            'message' => 'Offer workflow triggered for ' . count($results) . ' candidate(s)',
            'results' => $results
        ];
    }

    public function triggerOfferStageWorkflow(int $candidatePipelineStageId)
    {
        $this->validateTriggerWorkflowInput($candidatePipelineStageId);

        $candidatePipelineStage = CandidatePipelineStage::with(['candidate', 'pipelineStage', 'job'])
            ->find($candidatePipelineStageId);
        
        if (!$candidatePipelineStage) {
            throw new ModelNotFoundException("Candidate pipeline stage not found", 404);
        }

        $stage = $candidatePipelineStage->pipelineStage;
        if (!$stage || strtolower($stage->name) !== 'offer') {
            throw new Exception("Candidate is not in the 'offer' stage");
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

        $workflowResults['workflow_steps']['offer_packet'] = $this->generateOfferPacketStep($offer);
        $workflowResults['workflow_steps']['reminders'] = $this->scheduleRemindersStep($offer, $candidate, $recruiter);
        $workflowResults['workflow_steps']['status_tracking'] = $this->trackOfferStatusChangeStep($offer);

        return $workflowResults;
    }

    private function generateOfferPacketStep(Offer $offer)
    {
        try {
            $offerPacket = $this->generateOfferPacket($offer);
            return [
                'status' => 'success',
                'message' => 'Offer packet generated successfully',
                'file_path' => $offerPacket['file_path'] ?? null,
                'file_type' => $offerPacket['file_type'] ?? 'pdf'
            ];
        } catch (Exception $e) {
            Log::error('Failed to generate offer packet: ' . $e->getMessage());
            return [
                'status' => 'failed',
                'message' => 'Failed to generate offer packet: ' . $e->getMessage()
            ];
        }
    }

    private function scheduleRemindersStep(Offer $offer, $candidate, $recruiter)
    {
        try {
            $reminders = $this->scheduleReminders($offer, $candidate, $recruiter);
            return [
                'status' => 'success',
                'message' => 'Reminders scheduled successfully',
                'reminders' => $reminders
            ];
        } catch (Exception $e) {
            Log::error('Failed to schedule reminders: ' . $e->getMessage());
            return [
                'status' => 'failed',
                'message' => 'Failed to schedule reminders: ' . $e->getMessage()
            ];
        }
    }

    private function trackOfferStatusChangeStep(Offer $offer)
    {
        try {
            $this->trackOfferStatusChange($offer, 'moved_to_offer_stage');
            return [
                'status' => 'success',
                'message' => 'Offer status change tracked successfully'
            ];
        } catch (Exception $e) {
            Log::error('Failed to track offer status: ' . $e->getMessage());
            return [
                'status' => 'failed',
                'message' => 'Failed to track offer status: ' . $e->getMessage()
            ];
        }
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

    private function validateOfferData(array $data, bool $isUpdate): array
    {
        $rules = $isUpdate 
            ? $this->getUpdateValidationRules($data)
            : $this->getCreateValidationRules();

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    private function getCreateValidationRules(): array
    {
        return [
            'candidate_id' => ['required', 'integer', 'exists:candidates,id'],
            'job_id' => ['required', 'integer', 'exists:jobs,id'],
            'salary' => ['nullable', 'numeric'],
            'start_date' => ['nullable', 'date'],
            'contract_type' => ['nullable', 'string'],
            'offer_letter_template' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:draft,pending,accepted,rejected'],
            'recruiter_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    private function getUpdateValidationRules(array $data): array
    {
        $baseRules = [
            'id' => ['required', 'integer', 'exists:offers,id'],
            'candidate_id' => ['integer', 'exists:candidates,id'],
            'job_id' => ['integer', 'exists:jobs,id'],
            'salary' => ['nullable', 'numeric'],
            'start_date' => ['nullable', 'date'],
            'contract_type' => ['nullable', 'string'],
            'offer_letter_template' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:draft,pending,accepted,rejected'],
            'recruiter_id' => ['nullable', 'integer', 'exists:users,id'],
        ];

        return array_intersect_key($baseRules, $data);
    }

    private function validateSendOffersInput(int $jobId, int $pipelineStageId): void
    {
        $data = [
            'job_id' => $jobId,
            'pipeline_stage_id' => $pipelineStageId,
        ];
        $rules = $this->getSendOffersValidationRules();

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    private function getSendOffersValidationRules(): array
    {
        return [
            'job_id' => ['required', 'integer', 'exists:jobs,id'],
            'pipeline_stage_id' => ['required', 'integer', 'exists:stages,id'],
        ];
    }

    private function validateTriggerWorkflowInput(int $candidatePipelineStageId): void
    {
        $data = ['candidate_pipeline_stage_id' => $candidatePipelineStageId];
        $rules = $this->getTriggerWorkflowValidationRules();

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    private function getTriggerWorkflowValidationRules(): array
    {
        return [
            'candidate_pipeline_stage_id' => ['required', 'integer', 'exists:candidate_pipeline_stages,id'],
        ];
    }
}

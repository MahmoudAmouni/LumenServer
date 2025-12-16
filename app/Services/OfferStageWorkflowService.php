<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\CandidatePipelineStage;
use App\Models\Stage;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OfferStageWorkflowService
{
    public function checkAndTriggerWorkflowIfOfferStage(int $candidateId, int $jobId, int $pipelineStageId): void
    {
        try {
            $stage = Stage::find($pipelineStageId);
            
            if (!$stage || strtolower($stage->name) !== 'offer') {
                return;
            }

            $candidatePipelineStage = CandidatePipelineStage::where('candidate_id', $candidateId)
                ->where('job_id', $jobId)
                ->where('pipeline_stage_id', $pipelineStageId)
                ->latest('moved_at')
                ->first();

            if ($candidatePipelineStage) {
                $this->triggerWorkflow($candidatePipelineStage->id);
                
                Log::info('Offer stage workflow triggered automatically', [
                    'candidate_id' => $candidateId,
                    'job_id' => $jobId,
                    'pipeline_stage_id' => $pipelineStageId,
                    'candidate_pipeline_stage_id' => $candidatePipelineStage->id,
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to check/trigger offer stage workflow automatically: ' . $e->getMessage(), [
                'candidate_id' => $candidateId,
                'job_id' => $jobId,
                'pipeline_stage_id' => $pipelineStageId,
                'error' => $e->getTraceAsString(),
            ]);
        }
    }

    public function triggerWorkflow(int $candidatePipelineStageId): array
    {
        $candidatePipelineStage = CandidatePipelineStage::with([
            'candidate.recruiter',
            'pipelineStage',
            'job'
        ])->find($candidatePipelineStageId);

        if (!$candidatePipelineStage) {
            return [
                'success' => false,
                'errors' => ['Candidate pipeline stage not found'],
            ];
        }

        $stage = $candidatePipelineStage->pipelineStage;
        if (!$stage || strtolower($stage->name) !== 'offer') {
            return [
                'success' => false,
                'errors' => ['Candidate is not in the "offer" stage'],
            ];
        }

        $candidate = $candidatePipelineStage->candidate;
        $job = $candidatePipelineStage->job;
        $recruiter = $candidate->recruiter;
        
        if (!$recruiter) {
            return [
                'success' => false,
                'errors' => ['Candidate does not have a recruiter assigned'],
            ];
        }

        if (!$candidate || !$job) {
            return [
                'success' => false,
                'errors' => ['Candidate or job not found'],
            ];
        }

        $offer = Offer::with('job')
            ->where('candidate_id', $candidate->id)
            ->where('job_id', $job->id)
            ->first();

        if (!$offer) {
            $offer = new Offer();
            $offer->candidate_id = $candidate->id;
            $offer->job_id = $job->id;
            $offer->status = 'draft';
            $offer->recruiter_id = $recruiter->id ?? null;
            $offer->save();
            $offer->load('job');
        }

        $workflowResults = [
            'candidate_pipeline_stage_id' => $candidatePipelineStageId,
            'candidate_id' => $candidate->id,
            'job_id' => $job->id,
            'offer_id' => $offer->id,
            'candidate_email' => $candidate->email,
            'recruiter_email' => $recruiter->email,
            'steps' => [],
            'errors' => [],
        ];

        DB::beginTransaction();
        try {
            $workflowResults['steps']['offer_packet'] = $this->generateOfferPacketViaN8n($offer, $candidate, $job, $recruiter);

            DB::commit();

            $workflowResults['success'] = true;
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Offer stage workflow failed: ' . $e->getMessage());
            $workflowResults['success'] = false;
            $workflowResults['errors'][] = $e->getMessage();
        }

        return $workflowResults;
    }

    private function generateOfferPacketViaN8n(Offer $offer, $candidate, $job, $recruiter): array
    {
        try {
            $n8nUrl = config('services.n8n.offer_packet_webhook');

            if (!$n8nUrl) {
                return [
                    'status' => 'failed',
                    'error' => 'n8n offer packet webhook not configured',
                ];
            }

            $offerData = [
                'offer_id' => $offer->id,
                'candidate_id' => $offer->candidate_id,
                'job_id' => $offer->job_id,
                'salary' => $offer->salary,
                'start_date' => $offer->start_date,
                'contract_type' => $offer->contract_type,
                'offer_letter_template' => $offer->offer_letter_template ?? '',
                'status' => $offer->status,
                'recruiter_id' => $offer->recruiter_id,
                
                'candidate_name' => $candidate->full_name,
                'candidate_email' => $candidate->email,
                'candidate_phone' => $candidate->phone_number,
                
                'job_title' => $job->title ?? 'N/A',
                'job_description' => $job->description ?? '',
                
                'recruiter_name' => $recruiter->name,
                'recruiter_email' => $recruiter->email,
            ];

            $n8nUrl = config('services.n8n.offer_packet_webhook');

            $res = Http::timeout(180)
                ->post($n8nUrl, $offerData);

            if (!$res->successful()) {
                return [
                    'status' => 'failed',
                    'error' => 'n8n webhook failed',
                    'details' => [
                        'status' => $res->status(),
                        'body' => $res->body(),
                    ],
                ];
            }

            $data = $res->json();

            return [
                'status' => 'success',
                'message' => 'Offer packet generated successfully',
                'file_path' => $data['file_path'] ?? null,
                'file_type' => $data['file_type'] ?? 'pdf',
                'n8n_response' => $data,
            ];
        } catch (Throwable $e) {
            Log::error('Failed to generate offer packet via n8n: ' . $e->getMessage());
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }
}


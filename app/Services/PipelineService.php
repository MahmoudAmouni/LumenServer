<?php

namespace App\Services;

use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Services\StageService;

class PipelineService
{

    public function setupPipelineForJob(int $jobId, string $pipelineName, array $stages)
    {
        $stageService = new StageService();

        // Define Fixed Stages with their properties
        $startStages = [
            ['name' => 'Applied', 'is_interview' => false],
            ['name' => 'Interview', 'is_interview' => true],
        ];

        $endStages = [
            ['name' => 'Offer', 'is_interview' => false],
            ['name' => 'Hired', 'is_interview' => false],
            ['name' => 'Rejected', 'is_interview' => false],
        ];

        $customStages = [];
        foreach ($stages as $stage) {
            $customStages[] = [
                'name' => $stage['name'],
                'is_interview' => $stage['is_interview'] ?? false
            ];
        }

        $allStages = array_merge($startStages, $customStages, $endStages);

        $pipeline = $this->createPipeline([
            'name' => $pipelineName,
            'job_id' => $jobId
        ]);

        // Remove existing stages for this pipeline to ensure order is clean and correct
        PipelineStage::where('pipeline_id', $pipeline->id)->delete();

        $order = 1;
        foreach ($allStages as $stageData) {
            
            $stage = $stageService->createStage($stageData);

            $pipelineStage = new PipelineStage();
            $pipelineStage->pipeline_id = $pipeline->id;
            $pipelineStage->stage_id = $stage->id;
            $pipelineStage->order = $order;
            $pipelineStage->save();

            $order++;
        }

        return $pipeline;
    }
    public function createPipeline(array $data)
    {
        $name = $data['name'];
        $jobId = $data['job_id'];

        $existingPipeline = Pipeline::where('name', $name)
                                    ->where('job_id', $jobId)
                                    ->first();
        
        if ($existingPipeline) {
            return $existingPipeline;
        }

        $pipeline = new Pipeline();
        $pipeline->name = $name;
        $pipeline->job_id = $jobId;
        $pipeline->save();
        
        return $pipeline;
    }

    public function getPipelinesByJobId($jobId)
    {
        return Pipeline::where('job_id', $jobId)
                       ->with('stages') 
                       ->orderBy('id', 'asc')
                       ->get();
    }
}
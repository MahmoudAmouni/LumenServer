<?php

namespace App\Services;

use App\Models\Pipeline;
use App\Models\PipelineStages;
use App\Models\Stage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PipelineService
{
    public function createPipeline(int $jobId, string $jobTitle, array $Stages): Pipeline
    {
        
        $data = [
            'job_id' => $jobId,
            'job_title' => $jobTitle,
            'stages' => $Stages,
        ];
        
        $validator = Validator::make($Stages, [
            'job_id' => ['required', 'integer', 'exists:jobs,id'],
            'job_title' => ['required', 'string'],
            'stages' => ['required', 'array'],
            'stages.*.name' => ['required', 'string'],
        ]);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        $pipeline = new Pipeline();
        $pipeline->job_id = $jobId;
        $pipeline->name = $jobTitle;
        $pipeline->save();

        
        $allStagesData = [];
        $stagesWithOrder = [];
        $order = 1;

        $allStagesData['applied'] = [
            'name' => 'applied',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $allStagesData['interview'] = [
            'name' => 'interview',
            'created_at' => now(),
            'updated_at' => now(),
        ];


        //first stage applied of order 1 set all the stages in array to do insert down
        $stagesWithOrder['applied'] = $order;
        $order++;
        $stagesWithOrder['interview'] = $order;
        foreach ($Stages as $stageData) {
            $order++;
            $stageName = $stageData['name'];
            $allStagesData[$stageName] = [
                'name' => $stageName,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $stagesWithOrder[$stageName] = $order;
        }

        //adding the 3 other default ending stages
        $allStagesData['offer'] = [
            'name' => 'offer',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $stagesWithOrder['offer'] = $order + 1;

        $allStagesData['hired'] = [
            'name' => 'hired',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $stagesWithOrder['hired'] = $order + 2;

        $allStagesData['rejected'] = [
            'name' => 'rejected',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $stagesWithOrder['rejected'] = $order + 3;

        

        $stageNames = array_keys($allStagesData);
        $existingStages = Stage::whereIn('name', $stageNames)
            ->get()
            ->keyBy('name');

        $newStagesData = [];
        $stageIdsWithOrder = [];

        //check if name of stage is there get id if not set it in the array and insert them all together 
        foreach ($allStagesData as $name => $data) {
            if (isset($existingStages[$name])) {
                $existingStage = $existingStages[$name];
                $stageIdsWithOrder[$existingStage->id] = $stagesWithOrder[$name];
            } else {
                $newStagesData[] = $data;
            }
        }

        if (!empty($newStagesData)) {
            Stage::insert($newStagesData);

            $newStages = Stage::whereIn('name', array_column($newStagesData, 'name'))
                ->get()
                ->keyBy('name');

            foreach ($newStages as $name => $stage) {
                $stageIdsWithOrder[$stage->id] = $stagesWithOrder[$name];
            }
        }

        if (!empty($stageIdsWithOrder)) {
            $pivotData = [];
            $now = now();

            foreach ($stageIdsWithOrder as $stageId => $stageOrder) {
                $pivotData[] = [
                    'pipeline_id' => $pipeline->id,
                    'stage_id' => $stageId,
                    'order' => $stageOrder,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            PipelineStages::insert($pivotData);
        }

        $pipeline->load('pipelineStages');

        return $pipeline;
    }
}
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
        $this->validateStages($jobId, $jobTitle, $Stages);

        $pipeline = new Pipeline();
        $pipeline->job_id = $jobId;
        $pipeline->name = $jobTitle;
        $pipeline->save();

        $allStagesData = [];
        $stagesWithOrder = [];
        $order = 1;

        $this->addAllStages($Stages, $allStagesData, $stagesWithOrder, $order);

        $stageNames = array_keys($allStagesData);
        $existingStages = $this->getExistingStagesByName($stageNames);

        $newStagesData = [];
        $stageIdsWithOrder = [];

        //check if name of stage is there get id if not set it in the array and insert them all together
        $this->splitExistingAndNewStages(
            $allStagesData,
            $stagesWithOrder,
            $existingStages,
            $newStagesData,
            $stageIdsWithOrder
        );

        $this->insertNewStagesAndFillOrders($newStagesData, $stagesWithOrder, $stageIdsWithOrder);

        $this->insertPipelineStagesPivot($pipeline->id, $stageIdsWithOrder);

        $pipeline->load('pipelineStages');

        return $pipeline;
    }


    private function addAllStages(array $Stages, array &$allStagesData, array &$stagesWithOrder, int &$order): void
    {

        $now = now();
        $allStagesData['applied'] = [
            'name' => 'applied',
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $allStagesData['interview'] = [
            'name' => 'interview',
            'created_at' => $now,
            'updated_at' => $now,
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
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $stagesWithOrder[$stageName] = $order;
        }

        $allStagesData['offer'] = [
            'name' => 'offer',
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $stagesWithOrder['offer'] = $order + 1;

        $allStagesData['hired'] = [
            'name' => 'hired',
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $stagesWithOrder['hired'] = $order + 2;

        $allStagesData['rejected'] = [
            'name' => 'rejected',
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $stagesWithOrder['rejected'] = $order + 3;
    }


    private function getExistingStagesByName(array $stageNames)
    {
        return Stage::whereIn('name', $stageNames)
            ->get()
            ->keyBy('name');
    }

    private function splitExistingAndNewStages(
        array $allStagesData,
        array $stagesWithOrder,
        $existingStages,
        array &$newStagesData,
        array &$stageIdsWithOrder
    ): void {
        foreach ($allStagesData as $name => $data) {
            if (isset($existingStages[$name])) {
                $existingStage = $existingStages[$name];
                $stageIdsWithOrder[$existingStage->id] = $stagesWithOrder[$name];
            } else {
                $newStagesData[] = $data;
            }
        }
    }

    private function insertNewStagesAndFillOrders(
        array $newStagesData,
        array $stagesWithOrder,
        array &$stageIdsWithOrder
    ): void {
        if (!empty($newStagesData)) {
            Stage::insert($newStagesData);

            $newStages = Stage::whereIn('name', array_column($newStagesData, 'name'))
                ->get()
                ->keyBy('name');

            foreach ($newStages as $name => $stage) {
                $stageIdsWithOrder[$stage->id] = $stagesWithOrder[$name];
            }
        }
    }

    private function insertPipelineStagesPivot(int $pipelineId, array $stageIdsWithOrder): void
    {
        if (!empty($stageIdsWithOrder)) {
            $pivotData = [];
            $now = now();

            foreach ($stageIdsWithOrder as $stageId => $stageOrder) {
                $pivotData[] = [
                    'pipeline_id' => $pipelineId,
                    'stage_id' => $stageId,
                    'order' => $stageOrder,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            PipelineStages::insert($pivotData);
        }
    }



    private function validateStages(int $jobId, string $jobTitle, array $Stages): void
    {
        $data = [
            'job_id' => $jobId,
            'job_title' => $jobTitle,
            'stages' => $Stages,
        ];

        $validator = Validator::make($data, [
            'job_id' => ['required', 'integer', 'exists:jobs,id'],
            'job_title' => ['required', 'string'],
            'stages' => ['required', 'array'],
            'stages.*.name' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }


    public function getPipelineStagesByJobId(int $job_id)
    {
        return Pipeline::where('job_id', $job_id)
            ->with([
                'pipelineStages' => function ($q) {
                    $q->orderBy('order');
                }
            ])
            ->firstOrFail();
    }

}

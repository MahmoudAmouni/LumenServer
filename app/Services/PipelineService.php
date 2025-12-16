<?php

namespace App\Services;

use App\Models\Pipeline;
use App\Models\PipelineStages;
use App\Models\Stage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
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

        $stagesWithOrder['applied'] = $order;
        $order++;
        $stagesWithOrder['interview'] = $order;

        collect($Stages)->each(function ($stageData) use (&$order, &$allStagesData, &$stagesWithOrder, $now) {
            $order++;
            $stageName = strtolower(trim($stageData['name']));
            $allStagesData[$stageName] = [
                'name'       => $stageName,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $stagesWithOrder[$stageName] = $order;
        });

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
        $normalizedNames = array_map('strtolower', $stageNames);
        
        return Stage::whereIn('name', $stageNames)
            ->orWhereIn(DB::raw('LOWER(name)'), $normalizedNames)
            ->get()
            ->keyBy(function ($stage) {
                return strtolower($stage->name);
            });
    }

    private function splitExistingAndNewStages(
        array $allStagesData,
        array $stagesWithOrder,
        $existingStages,
        array &$newStagesData,
        array &$stageIdsWithOrder
    ): void {
        collect($allStagesData)->each(function ($data, $name) use (
            $stagesWithOrder,
            $existingStages,
            &$newStagesData,
            &$stageIdsWithOrder
        ) {
            $normalizedName = strtolower($name);

            if (isset($existingStages[$normalizedName])) {
                $existingStage = $existingStages[$normalizedName];

                if (isset($stagesWithOrder[$normalizedName])) {
                    $stageIdsWithOrder[$existingStage->id] = $stagesWithOrder[$normalizedName];
                } elseif (isset($stagesWithOrder[$name])) {
                    $stageIdsWithOrder[$existingStage->id] = $stagesWithOrder[$name];
                } else {
                    $stageIdsWithOrder[$existingStage->id] = count($stageIdsWithOrder) + 1;
                }
            } else {
                $newStagesData[] = $data;
            }
        });
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
                ->keyBy(function ($stage) {
                    return strtolower($stage->name);
                });

            collect($newStagesData)->each(function ($data) use ($newStages, $stagesWithOrder, &$stageIdsWithOrder) {
                $normalizedName = strtolower($data['name']);
                $stage = $newStages[$normalizedName] ?? null;
                if ($stage && isset($stagesWithOrder[$normalizedName])) {
                    $stageIdsWithOrder[$stage->id] = $stagesWithOrder[$normalizedName];
                } elseif ($stage && isset($stagesWithOrder[$data['name']])) {
                    $stageIdsWithOrder[$stage->id] = $stagesWithOrder[$data['name']];
                }
            });
        }
    }

    private function insertPipelineStagesPivot(int $pipelineId, array $stageIdsWithOrder): void
    {
        if (!empty($stageIdsWithOrder)) {
            $now = now();

            $pivotData = collect($stageIdsWithOrder)
                ->map(function ($stageOrder, $stageId) use ($pipelineId, $now) {
                    return [
                        'pipeline_id' => $pipelineId,
                        'stage_id'    => $stageId,
                        'order'       => $stageOrder,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ];
                })
                ->values()
                ->all();

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

        $rules = $this->getCreateValidationRules();

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    private function getCreateValidationRules(): array
    {
        return [
            'job_id' => ['required', 'integer', 'exists:jobs,id'],
            'job_title' => ['required', 'string'],
            'stages' => ['required', 'array'],
            'stages.*.name' => ['required', 'string'],
        ];
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

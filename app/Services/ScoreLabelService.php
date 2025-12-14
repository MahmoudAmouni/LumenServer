<?php
namespace App\Services;

use App\Models\ScoreLabel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ScoreLabelService
{
    public function createScoreLabels(array $scoreLabels): void
    {
        if (empty($scoreLabels)) {
            return;
        }

        $this->validateScoreLabels($scoreLabels);

        $labelNames = [];
        $labelDataByName = [];
        foreach ($scoreLabels as $label) {
            $name = $label['name'];
            $labelNames[] = $name;
            $labelDataByName[$name] = [
                'name' => $name,
                'max_score' => $label['max_score'],
            ];
        }

        $existingLabels = ScoreLabel::whereIn('name', $labelNames)
            ->get()
            ->keyBy('name');

        $newLabelsData = [];
        $now = now();
        foreach ($labelNames as $name) {
            if (!isset($existingLabels[$name])) {
                $newLabelsData[] = [
                    'name' => $name,
                    'max_score' => $labelDataByName[$name]['max_score'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (!empty($newLabelsData)) {
            ScoreLabel::insert($newLabelsData);
        }
    }

    private function validateScoreLabels(array $scoreLabels): void
    {
        $validator = Validator::make(['score_labels' => $scoreLabels], [
            'score_labels' => ['required', 'array'],
            'score_labels.*.name' => ['required', 'string'],
            'score_labels.*.max_score' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
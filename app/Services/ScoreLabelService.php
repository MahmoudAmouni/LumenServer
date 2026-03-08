<?php
namespace App\Services;

use App\Models\ScoreLabel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ScoreLabelService
{
    public function createScoreLabels(array $scoreLabels)
    {
        if (empty($scoreLabels)) {
            return;
        }

        $labelNames = [];
        foreach ($scoreLabels as $label) {
            $labelNames[] = $label['name'];
        }

        $labelNames = array_unique($labelNames);

        // find labels that exist in the db
        $existingLabels = ScoreLabel::whereIn('name', $labelNames)
            ->pluck('name')
            ->toArray();

        // determine which labels are new
        $newLabelNames = array_diff($labelNames, $existingLabels);

        if (!empty($newLabelNames)) {
            $now = now();
            $insertData = [];
            foreach ($newLabelNames as $name) {
                $insertData[] = [
                    'name' => $name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            ScoreLabel::insert($insertData);
        }
    }
}
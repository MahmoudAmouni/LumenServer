<?php

namespace App\Services;

use App\Models\ScoreLabel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ScoreLabelService
{
    public function getAllScoreLabels()
    {
        return ScoreLabel::with('scorecards')->get();
    }

    public function getScoreLabelById(int $id)
    {
        $scoreLabel = ScoreLabel::with('scorecards')->find($id);

        if (!$scoreLabel) {
            throw new \Exception("Score Label not found");
        }

        return $scoreLabel;
    }

    public function createScoreLabel(string $name): ScoreLabel
    {
        $scoreLabel = new ScoreLabel();
        $scoreLabel->name = $name;
        $scoreLabel->save();
        return $scoreLabel;
    }

    public function createScoreLabels(array $scoreLabels)
    {
        $allScoreLabelsData = $this->prepareScoreLabelsData($scoreLabels);
        $labelNames = array_keys($allScoreLabelsData);
        $existingLabels = $this->getExistingScoreLabels($labelNames);
        $newLabelsData = $this->filterNewScoreLabels($allScoreLabelsData, $existingLabels);

        if (!empty($newLabelsData)) {
            $this->insertNewScoreLabels($newLabelsData);
        }
    }

    public function updateScoreLabel(int $id, array $data): ScoreLabel
    {
        $scoreLabel = ScoreLabel::find($id);

        if (!$scoreLabel) {
            throw new \Exception("Score Label not found");
        }

        $scoreLabel->name = $data['name'] ?? $scoreLabel->name;
        $scoreLabel->save();

        return $scoreLabel;
    }

    public function deleteScoreLabel(int $id)
    {
        $scoreLabel = ScoreLabel::find($id);

        if (!$scoreLabel) {
            throw new \Exception("Score Label not found");
        }

        $scoreLabel->delete();
        return true;
    }

    private function prepareScoreLabelsData(array $scoreLabels): array
    {
        $now = now();

        return collect($scoreLabels)
            ->mapWithKeys(function ($labelData) use ($now) {
                $labelName = $labelData['name'];
                $data = [
                    'name'       => $labelName,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                
                // Only add max_score if the column exists in the database
                if (isset($labelData['max_score'])) {
                    $data['max_score'] = $labelData['max_score'];
                }
                
                return [$labelName => $data];
            })
            ->all();
    }

    private function getExistingScoreLabels(array $labelNames)
    {
        return ScoreLabel::whereIn('name', $labelNames)
            ->get()
            ->keyBy('name');
    }

    private function filterNewScoreLabels(array $allScoreLabelsData, $existingLabels): array
    {
        return collect($allScoreLabelsData)
            ->filter(function ($data, $name) use ($existingLabels) {
                return !isset($existingLabels[$name]);
            })
            ->values()
            ->all();
    }

    private function insertNewScoreLabels(array $newLabelsData): void
    {
        ScoreLabel::insert($newLabelsData);
    }
}

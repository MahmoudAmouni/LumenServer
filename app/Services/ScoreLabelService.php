<?php

namespace App\Services;

use App\Models\ScoreLabel;
//

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

    public function createScoreLabel(string $name, int $maxScore): ScoreLabel
    {
        $scoreLabel = new ScoreLabel();
        $scoreLabel->name = $name;
        $scoreLabel->max_score = $maxScore;
        $scoreLabel->save();
        return $scoreLabel;
    }

    public function createScoreLabels(array $scoreLabels)
    {
        $allScoreLabelsData = [];

        foreach ($scoreLabels as $labelData) {
            $labelName = $labelData['name'];
            $allScoreLabelsData[$labelName] = [
                'name' => $labelName,
                'max_score' => $labelData['max_score'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $labelNames = array_keys($allScoreLabelsData);
        $existingLabels = ScoreLabel::whereIn('name', $labelNames)
            ->get()
            ->keyBy('name');

        $newLabelsData = [];

        foreach ($allScoreLabelsData as $name => $data) {
            if (!isset($existingLabels[$name])) {
                $newLabelsData[] = $data;
            }
        }

        if (!empty($newLabelsData)) {
            ScoreLabel::insert($newLabelsData);
        }
    }

    public function updateScoreLabel(int $id, array $data): ScoreLabel
    {
        $scoreLabel = ScoreLabel::find($id);
        if (!$scoreLabel) {
            throw new \Exception("Score Label not found");
        }
        $scoreLabel->name = $data['name'] ?? $scoreLabel->name;
        $scoreLabel->max_score = $data['max_score'] ?? $scoreLabel->max_score;
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
}
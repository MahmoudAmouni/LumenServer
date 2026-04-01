<?php

namespace App\Services;

use App\Models\Stage;

class StageService
{
    public function createStage(array $data){
        
        $existingStage = Stage::where('name', $data['name'])->first();
        
        if ($existingStage) {
            return $existingStage;
        }

        $stage = new Stage();
        $stage->name = $data['name'];
        $stage->is_interview = $data['is_interview'] ?? false;
        $stage->save();
        
        return $stage;
    }

    public function getAllStages()
    {
        return Stage::all();
    }
}

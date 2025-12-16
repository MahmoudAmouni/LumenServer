<?php

namespace App\Http\Controllers;

use App\Models\Skill;
use App\Models\Stage;
use Exception;
use Illuminate\Http\JsonResponse;

class SkillController extends Controller
{
    public function getAllSkills(): JsonResponse
    {
        try {
            $skills = Skill::orderBy('name')->get();
            $formatted = $skills->map(function ($skill) {
                return [
                    'id' => (string) $skill->id,
                    'name' => $skill->name,
                ];
            });
            return $this->responseJSON($formatted->toArray(), 'success', 200);
        } catch (Exception $e) {
            return $this->responseJSON($e->getMessage(), 'failure', 400);
        }
    }

    public function getAllStages(): JsonResponse
    {
        try {
            $stages = Stage::orderBy('name')->get();
            $formatted = $stages->map(function ($stage) {
                return [
                    'id' => (string) $stage->id,
                    'name' => $stage->name,
                ];
            });
            return $this->responseJSON($formatted->toArray(), 'success', 200);
        } catch (Exception $e) {
            return $this->responseJSON($e->getMessage(), 'failure', 400);
        }
    }
}

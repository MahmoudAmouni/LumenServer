<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
$baseurl = env('APP_URL', 'http://localhost');




Route::post('/login', [AuthController::class, "login"]);
Route::post('/register', [AuthController::class, "register"]);
Route::get('/error', [AuthController::class, "displayError"])->name("login");

Route::group(["prefix" => "v1", "middleware" => "auth:api"], function () {



     Route::get('/pipelines', [CandidateController::class, 'getAllPipelines']);
    Route::get('/pipelines/{id}', [CandidateController::class, 'getPipelineById']);
    Route::get('/pipelines-with-stages', [CandidateController::class, 'getPipelinesWithStages']);
    
    // Pipeline candidates routes
    Route::get('/pipelines/{pipelineId}/candidates', [CandidateController::class, 'getPipelineCandidates']);
    Route::get('/pipelines/{pipelineId}/stages/{stageId}/candidates', [CandidateController::class, 'getPipelineCandidatesByStage']);
    
    // Candidates by job and pipeline stage
    Route::get('/candidates/job/{jobId}/pipeline-stage/{pipelineStageId}', [CandidateController::class, 'getCandidatesByJobIdAndPipelineStage']);
    
    // Candidate pipeline stages CRUD
    Route::get('/candidate-pipeline-stages', [CandidateController::class, 'getAllCandidatePipelineStages']);
    Route::get('/candidate-pipeline-stages/{id}', [CandidateController::class, 'getAllCandidatePipelineStages']);
    Route::post('/candidate-pipeline-stages/add', [CandidateController::class, 'createOrUpdateCandidatePipelineStage']);
    Route::put('/candidate-pipeline-stages/{id}', [CandidateController::class, 'createOrUpdateCandidatePipelineStage']);
    Route::get('/candidate-pipeline-stages/{id}/delete', [CandidateController::class, 'deleteCandidatePipelineStage']);
    Route::post('/candidate-pipeline-stages/{id}/delete', [CandidateController::class, 'deleteCandidatePipelineStage']);


    Route::middleware(['admin'])->group(function () {
    });

    Route::middleware(['recruiter'])->group(function () {
    });

});

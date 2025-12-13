<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\CandidateImportN8nController;
use App\Http\Controllers\InterviewController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\PipelineController;
use Illuminate\Support\Facades\Route;




Route::post('/pipelines', [PipelineController::class, 'store']);
Route::post('/import-excel-n8n', [CandidateImportN8nController::class, 'import']);


Route::post('/login', [AuthController::class, "login"]);
Route::post('/register', [AuthController::class, "register"]);
Route::get('/error', [AuthController::class, "displayError"])->name("login");

Route::group(["prefix" => "v1", "middleware" => "auth:api"], function () {

    Route::post("/update/{id}", [InterviewController::class,"update"]);

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
    
    // Offers CRUD routes
    Route::get('/offers', [OfferController::class, 'getAllOffers']);
    Route::get('/offers/{id}', [OfferController::class, 'getOfferById']);
    Route::post('/offers/add', [OfferController::class, 'createOrUpdateOffer']);
    Route::put('/offers/{id}', [OfferController::class, 'createOrUpdateOffer']);
    Route::get('/offers/{id}/delete', [OfferController::class, 'deleteOffer']);
    Route::post('/offers/{id}/delete', [OfferController::class, 'deleteOffer']);
    
});
<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\InterviewController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\ScorecardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


$baseurl = env('APP_URL', 'http://localhost');




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

    // Job routes
    Route::get('/jobs', [JobController::class, 'getAllJobs']);
    Route::get('/jobs/{id}', [JobController::class, 'getJobById']);
    Route::post('/jobs/add', [JobController::class, 'createJob']);
    Route::put('/jobs/{id}', [JobController::class, 'updateJob']);
    Route::get('/jobs/{id}/delete', [JobController::class, 'deleteJob']);
    Route::post('/jobs/{id}/delete', [JobController::class, 'deleteJob']);

    // Scorecard routes
    Route::get('/scorecards', [ScorecardController::class, 'getAllScorecards']);
    Route::get('/scorecards/{id}', [ScorecardController::class, 'getScorecardById']);
    Route::post('/scorecards/add', [ScorecardController::class, 'createScorecard']);
    Route::put('/scorecards/{id}', [ScorecardController::class, 'updateScorecard']);


    Route::middleware(['admin'])->group(function () {
    });

    Route::middleware(['recruiter'])->group(function () {
    });

});

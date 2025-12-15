<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\CandidateImportN8nController;
use App\Http\Controllers\InterviewController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\InterviewN8nController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\ScorecardController;
use Illuminate\Support\Facades\Route;



Route::post('/import-excel-n8n', [CandidateImportN8nController::class, 'import']);
Route::post('/interviews/summarize-notes/{interviewId}', [InterviewN8nController::class, 'summarizeAndScore']);
Route::post('/interviews/next-step-email/{candidatePipelineStageId}', [InterviewN8nController::class, 'sendNextStepEmail']);


Route::get('/pipelineStages/{job_id}', [PipelineController::class, 'getStagesByJobId']);
Route::post('/login', [AuthController::class, "login"]);
Route::post('/register', [AuthController::class, "register"]);
Route::get('/error', [AuthController::class, "displayError"])->name("login");

// Job routes
Route::get('/companyJobs/{companyId}', [JobController::class, 'getJobsByCompanyId']);
Route::post('/addJob', [JobController::class, 'createJob']);
Route::post('/updateJob/{id}', [JobController::class, 'updateJob']);
Route::post('/deleteJob/{id}', [JobController::class, 'deleteJob']);

// Scorcard routes

Route::get('/scorecards/interview/{interviewId}', [ScorecardController::class, 'getScorecardsByInterviewId']);
Route::post('/scorecards/create-for-interview', [ScorecardController::class, 'createScorecardsForInterview']);

Route::group(["prefix" => "v1", "middleware" => "auth:api"], function () {

    Route::post("/update/{id}", [InterviewController::class,"update"]);

    Route::get('/pipelines', [CandidateController::class, 'getAllPipelines']);
    Route::get('/pipelines/{id}', [CandidateController::class, 'getPipelineById']);
    Route::get('/pipelines-with-stages', [CandidateController::class, 'getPipelinesWithStages']);
    
    // Pipeline candidates routes
    Route::get('/pipelines/{pipelineId}/candidates', [CandidateController::class, 'getPipelineCandidates']);
    Route::get('/pipelines/{pipelineId}/stages/{stageId}/candidates', [CandidateController::class, 'getPipelineCandidatesByStage']);
    
    // Candidates by job and optionally by pipeline stage
    Route::get('/candidates/job/{jobId}', [CandidateController::class, 'getCandidatesByJobIdAndPipelineStage']);
    Route::get('/candidates/job/{jobId}/pipeline-stage/{pipelineStageId}', [CandidateController::class, 'getCandidatesByJobIdAndPipelineStage']);
    
    // Candidate profile
    Route::get('/candidates/{candidateId}/profile', [CandidateController::class, 'getCandidateProfile']);
    
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
    

    Route::middleware(['admin'])->group(function () {
    });

    Route::middleware(['recruiter'])->group(function () {
    });
});
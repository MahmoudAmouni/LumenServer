<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\CandidateImportN8nController;
use App\Http\Controllers\InterviewController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\InterviewN8nController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SkillController;
use Illuminate\Support\Facades\Route;

// Handle OPTIONS preflight requests for all routes
Route::options('{any}', function () {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin')
        ->header('Access-Control-Max-Age', '86400');
})->where('any', '.*');

Route::post("/interviews/summarize-notes", [InterviewN8nController::class, "sendPostInterviewWorkflow"]);
Route::post('/import-excel-n8n', [CandidateImportN8nController::class, 'import']);

Route::get('/pipelineStages/{job_id}', [PipelineController::class, 'getStagesByJobId']);
Route::post('/login', [AuthController::class, "login"]);
Route::post('/register', [AuthController::class, "register"]);
Route::get('/error', [AuthController::class, "displayError"])->name("login");

// Job routes - RESTful (new)
Route::get('/jobs/company/{companyId}', [JobController::class, 'getJobsByCompanyId']);
Route::post('/jobs', [JobController::class, 'createJob']);
Route::put('/jobs/{id}', [JobController::class, 'updateJob']);
Route::delete('/jobs/{id}', [JobController::class, 'deleteJob']);

// Job routes - Legacy (backward compatibility)
Route::get('/companyJobs/{companyId}', [JobController::class, 'getJobsByCompanyId']);
Route::post('/addJob', [JobController::class, 'createJob']);
Route::post('/updateJob/{id}', [JobController::class, 'updateJob']);
Route::post('/deleteJob/{id}', [JobController::class, 'deleteJob']);

Route::group(["prefix" => "v1", "middleware" => "auth:api"], function () {

    Route::post("/logout", [AuthController::class, "logout"]);
    Route::post("/interviews/update/{id}", [InterviewController::class,"update"]);
    Route::post("/interviews/update-notes", [InterviewController::class,"updateInterviewNotes"]);

    // Company routes
    Route::get('/companies', [CompanyController::class, 'getAllCompanies']);
    Route::post('/companies', [CompanyController::class, 'createCompany']);
    
    // User routes
    Route::get('/users', [UserController::class, 'getAllUsers']);
    Route::get('/users/company/{companyId}', [UserController::class, 'getUsersByCompany']);
    Route::post('/users', [UserController::class, 'createUser']);

    Route::get('/pipelines', [CandidateController::class, 'getAllPipelines']);
    Route::get('/pipelines/{id}', [CandidateController::class, 'getPipelineById']);
    Route::get('/pipelinesWithStages', [CandidateController::class, 'getPipelinesWithStages']);
    
    // Skills and Stages routes
    Route::get('/skills', [SkillController::class, 'getAllSkills']);
    Route::get('/stages', [SkillController::class, 'getAllStages']);
    
    // Pipeline candidates routes
    Route::get('/pipelines/{pipelineId}/candidates', [CandidateController::class, 'getPipelineCandidates']);
    Route::get('/pipelines/{pipelineId}/stages/{stageId}/candidates', [CandidateController::class, 'getPipelineCandidatesByStage']);
    
    // Candidates by job and optionally by pipeline stage
    Route::get('/candidates/job/{jobId}', [CandidateController::class, 'getCandidatesByJobIdAndPipelineStage']);
    Route::get('/candidates/job/{jobId}/pipeline-stage/{pipelineStageId}', [CandidateController::class, 'getCandidatesByJobIdAndPipelineStage']);
    
    // Create a single candidate
    Route::post('/candidates', [CandidateController::class, 'createCandidate']);
    
    // Candidate profile
    Route::get('/candidates/{candidateId}/profile', [CandidateController::class, 'getCandidateProfile']);
    
    // Update candidate stage
    Route::post('/candidates/{candidateId}/update-stage', [CandidateController::class, 'updateCandidateStage']);
    
    // Candidate pipeline stages CRUD
    Route::get('/candidate-pipeline-stages', [CandidateController::class, 'getAllCandidatePipelineStages']);
    Route::get('/candidate-pipeline-stages/{id}', [CandidateController::class, 'getAllCandidatePipelineStages']);
    Route::post('/candidate-pipeline-stages/add', [CandidateController::class, 'createOrUpdateCandidatePipelineStage']);
    Route::post('/candidate-pipeline-stages/{id}', [CandidateController::class, 'createOrUpdateCandidatePipelineStage']);
    Route::post('/candidate-pipeline-stages/{id}/delete', [CandidateController::class, 'deleteCandidatePipelineStage']);
    
    // Offers CRUD routes
    Route::get('/offers', [OfferController::class, 'getAllOffers']);
    Route::get('/offers/{id}', [OfferController::class, 'getOfferById']);
    Route::post('/offers/add', [OfferController::class, 'createOrUpdateOffer']);
    Route::post('/offers/{id}', [OfferController::class, 'createOrUpdateOffer']);
    Route::post('/offers/{id}/delete', [OfferController::class, 'deleteOffer']);
});
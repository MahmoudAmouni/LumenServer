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

Route::group(["prefix" => "v1"], function (){

    Route::post('/login', [AuthController::class, "login"]);
    Route::post('/register', [AuthController::class, "register"]);

    Route::post('/import-excel-n8n', [CandidateImportN8nController::class, 'import']);

    Route::get('/pipelineStages/{job_id}', [PipelineController::class, 'getStagesByJobId']);

    Route::get('/error', [AuthController::class, "displayError"])->name("login");



    Route::get('/companyJobs/{companyId}', [JobController::class, 'getJobsByCompanyId']);
    Route::post('/addJob', [JobController::class, 'createJob']);
    Route::post('/updateJob/{id}', [JobController::class, 'updateJob']);
    Route::post('/deleteJob/{id}', [JobController::class, 'deleteJob']);

    Route::group(["prefix" => "auth", "middleware" => "auth:api"], function (){

        Route::group(["prefix" => "jobs"] , function(){
            Route::get('/company/{companyId}', [JobController::class, 'getJobsByCompanyId']);
            Route::post('/create', [JobController::class, 'createJob']);
            Route::put('/update/{id}', [JobController::class, 'updateJob']);
            Route::delete('/delete/{id}', [JobController::class, 'deleteJob']);
        });


        Route::post("/interviews/summarize-notes/{interviewId}", [InterviewN8nController::class, "summarizeAndScoreInterview"]);

        Route::post("/logout", [AuthController::class, "logout"]);
        Route::post("/interviews/update/{id}", [InterviewController::class,"update"]);
        Route::post("/interviews/update-notes", [InterviewController::class,"updateInterviewNotes"]);

        Route::get('/companies', [CompanyController::class, 'getAllCompanies']);
        Route::post('/companies', [CompanyController::class, 'createCompany']);
        
        Route::get('/users', [UserController::class, 'getAllUsers']);
        Route::get('/users/company/{companyId}', [UserController::class, 'getUsersByCompany']);
        Route::post('/users', [UserController::class, 'createUser']);

        Route::get('/pipelines', [CandidateController::class, 'getAllPipelines']);
        Route::get('/pipelines/{id}', [CandidateController::class, 'getPipelineById']);
        Route::get('/pipelinesWithStages', [CandidateController::class, 'getPipelinesWithStages']);
        
        Route::get('/skills', [SkillController::class, 'getAllSkills']);
        Route::get('/stages', [SkillController::class, 'getAllStages']);
        
        Route::get('/pipelines/{pipelineId}/candidates', [CandidateController::class, 'getPipelineCandidates']);
        Route::get('/pipelines/{pipelineId}/stages/{stageId}/candidates', [CandidateController::class, 'getPipelineCandidatesByStage']);
        
        Route::get('/candidates/job/{jobId}', [CandidateController::class, 'getCandidatesByJobIdAndPipelineStage']);
        Route::get('/candidates/job/{jobId}/pipeline-stage/{pipelineStageId}', [CandidateController::class, 'getCandidatesByJobIdAndPipelineStage']);
        
        Route::post('/candidates', [CandidateController::class, 'createCandidate']);
        
        Route::get('/candidates/{candidateId}/profile', [CandidateController::class, 'getCandidateProfile']);
        
        Route::post('/candidates/{candidateId}/update-stage', [CandidateController::class, 'updateCandidateStage']);
        
        Route::get('/candidate-pipeline-stages', [CandidateController::class, 'getAllCandidatePipelineStages']);
        Route::get('/candidate-pipeline-stages/{id}', [CandidateController::class, 'getAllCandidatePipelineStages']);
        Route::post('/candidate-pipeline-stages/add', [CandidateController::class, 'createOrUpdateCandidatePipelineStage']);
        Route::post('/candidate-pipeline-stages/{id}', [CandidateController::class, 'createOrUpdateCandidatePipelineStage']);
        Route::post('/candidate-pipeline-stages/{id}/delete', [CandidateController::class, 'deleteCandidatePipelineStage']);
        
        Route::get('/offers', [OfferController::class, 'getAllOffers']);
        Route::get('/offers/{id}', [OfferController::class, 'getOfferById']);
        Route::post('/offers/add', [OfferController::class, 'createOrUpdateOffer']);
        Route::post('/offers/{id}', [OfferController::class, 'createOrUpdateOffer']);
        Route::post('/offers/{id}/delete', [OfferController::class, 'deleteOffer']);
        
        Route::get('/jobs/company/{companyId}', [JobController::class, 'getJobsByCompanyId']);

    });
    Route::post('/ask-question', [UserController::class, 'askQuestion']);
});
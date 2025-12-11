<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
 protected $fillable = [
    'title',
    'level',
    'location',
    'remote',
    'description',
    'hiring_manager_id',
    'company_id',
];

public function hiringManager()
{
    return $this->belongsTo(User::class, 'hiring_manager_id');
}

public function company()
{
    return $this->belongsTo(CompanyName::class, 'company_id');
}

public function candidateJobs()
{
    return $this->hasMany(CandidateJob::class, 'job_id');
}

public function pipelines()
{
    return $this->hasMany(Pipeline::class, 'job_id');
}

public function jobSkills()
{
    return $this->hasMany(JobSkill::class, 'job_id');
}

public function candidatePipelineStages()
{
    return $this->hasMany(CandidatePipelineStage::class, 'job_id');
}

public function interviews()
{
    return $this->hasMany(Intreview::class, 'job_id');
}

public function copilotQueries()
{
    return $this->hasMany(CopilotQuery::class, 'job_id');
}

public function scorecards()
{
    return $this->hasMany(Scorecard::class, 'job_id');
}
}

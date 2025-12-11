<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    use HasFactory;
   protected $fillable = [
    'recruiter_id', 
    'full_name',
    'email',
    'phone_number',
    'level',
    'github_url',
    'linkedin_url',
    'cv_path',
    'age',
    'location',

];

public function recruiter()
{
    return $this->belongsTo(User::class, 'recruiter_id');
}

public function candidateJobs()
{
    return $this->hasMany(CandidateJob::class, 'candidate_id');
}

public function candidatePipelineStages()
{
    return $this->hasMany(CandidatePipelineStage::class, 'candidate_id');
}

public function documents()
{
    return $this->hasMany(Document::class, 'candidate_id');
}

public function interviews()
{
    return $this->hasMany(Interview::class, 'candidate_id');
}

public function offers()
{
    return $this->hasMany(Offer::class, 'candidate_id');
}

public function scorecards()
{
    return $this->hasMany(Scorecard::class, 'candidate_id');
}

public function copilotQueries()
{
    return $this->hasMany(CopilotQuery::class, 'candidate_id');
}
}
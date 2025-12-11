<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
   protected $fillable = [
    'user_id',
    'full_name',
    'email',
    'phone',
    'level',
    'portfolio_url',
    'github_url',
    'linkedin_url',
    'cv_path',
];

public function user()
{
    return $this->belongsTo(User::class, 'user_id');
}

public function scorecards()
{
    return $this->hasMany(Scorecard::class, 'candidate_id');
}

public function copilotQueries()
{
    return $this->hasMany(CopilotQuery::class, 'candidate_id');
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
    return $this->hasMany(Intreview::class, 'candidate_id');
}

public function offers()
{
    return $this->hasMany(offer::class, 'candidate_id');
}

}
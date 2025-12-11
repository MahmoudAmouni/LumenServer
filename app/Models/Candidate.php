<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
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

public function user()
{
    return $this->belongsTo(User::class, 'recruiter_id');
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
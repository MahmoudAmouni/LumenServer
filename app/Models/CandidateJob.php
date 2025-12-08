<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateJob extends Model
{
   protected $fillable = [
    'candidate_id',
    'job_id',
    'applied_at',
    'source',
    'added_by_recruiter_id',
    'cover_letter',
];

public function candidate()
{
    return $this->belongsTo(Candidate::class, 'candidate_id');
}

public function job()
{
    return $this->belongsTo(Job::class, 'job_id');
}

public function addedByRecruiter()
{
    return $this->belongsTo(User::class, 'added_by_recruiter_id');
}
}

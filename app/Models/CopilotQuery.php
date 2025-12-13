<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CopilotQuery extends Model
{
    use HasFactory;
   protected $fillable = [
    'candidate_id',
    'job_id',
    'query_text',
    'response_text',
    'recruiter_id',
    'citation_text',
    'source',
];

public function candidate()
{
    return $this->belongsTo(Candidate::class, 'candidate_id');
}

public function job()
{
    return $this->belongsTo(Job::class, 'job_id');
}

public function recruiter()
{
    return $this->belongsTo(User::class, 'recruiter_id');
}
}

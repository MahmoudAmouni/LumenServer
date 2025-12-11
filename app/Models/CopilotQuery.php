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
    'query_by_recruiter_id',
    'citation_text',
    'source_id',
];

public function candidate()
{
    return $this->belongsTo(Candidate::class, 'candidate_id');
}

public function job()
{
    return $this->belongsTo(Job::class, 'job_id');
}

public function queryByRecruiter()
{
    return $this->belongsTo(User::class, 'query_by_recruiter_id');
}
}

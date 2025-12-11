<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scorecard extends Model
{
 protected $fillable = [
    'candidate_id',
    'interview_id',
    'evaluator_id',
    'status',
    'overall_recommendation',
    'summary',
    'evaluation_criteria',
    'scorerate_id',
    'job_id',
    'scorelabel_id',
];

public function candidate()
{
    return $this->belongsTo(Candidate::class, 'candidate_id');
}

public function interview()
{
    return $this->belongsTo(Intreview::class, 'interview_id');
}

public function evaluator()
{
    return $this->belongsTo(User::class, 'evaluator_id');
}

public function scorelabel()
{
    return $this->belongsTo(scorelabel::class, 'scorelabel_id');
}

public function job()
{
    return $this->belongsTo(Job::class, 'job_id');
}
}

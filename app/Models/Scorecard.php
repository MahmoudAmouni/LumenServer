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
}

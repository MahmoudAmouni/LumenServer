<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Intreview extends Model
{
   protected $fillable = [
    'candidate_id',
    'hiring_manager_id',
    'job_id',
    'interview_type',
    'stage',
    'scheduled_at',
    'completed_at',
    'duration_minutes',
    'notes',
];

public function candidate()
{
    return $this->belongsTo(Candidate::class, 'candidate_id');
}

public function hiringManager()
{
    return $this->belongsTo(User::class, 'hiring_manager_id');
}

public function job()
{
    return $this->belongsTo(Job::class, 'job_id');
}
}

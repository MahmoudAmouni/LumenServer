<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidatePipelineStage extends Model
{
    use HasFactory;
    
 protected $fillable = [
    'candidate_id',
    'pipeline_stage_id',
    'job_id',
    'moved_at',
    'notes',
];
    
    protected $casts = [
        'moved_at' => 'datetime',
    ];

public function candidate()
{
    return $this->belongsTo(Candidate::class, 'candidate_id');
}

public function pipelineStage()
{
    return $this->belongsTo(Stage::class, 'pipeline_stage_id');
}

public function job()
{
    return $this->belongsTo(Job::class, 'job_id');
}
}

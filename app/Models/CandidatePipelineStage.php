<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidatePipelineStage extends Model
{
 protected $fillable = [
    'candidate_id',
    'pipeline_stage_id',
    'job_id',
    'moved_at',
    'notes',
];

public function candidate()
{
    return $this->belongsTo(Candidate::class, 'candidate_id');
}

public function pipelineStage()
{
    return $this->belongsTo(PipelineStage::class, 'pipeline_stage_id');
}

public function job()
{
    return $this->belongsTo(Job::class, 'job_id');
}
}

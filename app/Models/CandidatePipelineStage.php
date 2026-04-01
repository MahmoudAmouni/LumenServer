<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Candidate;
use App\Models\PipelineStage;
use App\Models\Job;

class CandidatePipelineStage extends Model
{
    use HasFactory;

    protected $table = 'candidate_pipeline_stages';
    
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
        return $this->belongsTo(PipelineStage::class, 'pipeline_stage_id');
    }

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function stage()
    {
        return $this->hasOneThrough(Stage::class, PipelineStage::class, 'stage_id', 'id', 'pipeline_stage_id', 'id');
    }
}

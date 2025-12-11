<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stage extends Model
{
    use HasFactory;
    
    protected $table = 'pipeline_stages';
    
    protected $fillable = [
        'name',
        'order',
    ];

    public function candidatePipelineStages()
    {
        return $this->hasMany(CandidatePipelineStage::class, 'pipeline_stage_id');
    }

    public function pipelineStages()
    {
        return $this->hasMany(PipelineStages::class, 'pipeline_stage_id');
    }

    public function pipelines()
    {
        return $this->hasManyThrough(Pipeline::class, PipelineStages::class, 'pipeline_stage_id', 'id', 'id', 'pipeline_id');
    }
}


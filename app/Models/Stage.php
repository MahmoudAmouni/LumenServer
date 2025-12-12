<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PipelineStages;

class Stage extends Model
{
    use HasFactory;
    
    protected static function newFactory()
    {
        return \Database\Factories\StageFactory::new();
    }
    
    protected $table = 'stages';
    
    protected $fillable = [
        'name',
    ];

    public function candidatePipelineStages()
    {
        return $this->hasMany(CandidatePipelineStage::class, 'pipeline_stage_id');
    }

    public function pipelineStages()
    {
        return $this->hasMany(PipelineStages::class, 'stage_id');
    }

    public function pipelines()
    {
        return $this->hasManyThrough(Pipeline::class, PipelineStages::class, 'stage_id', 'id', 'id', 'pipeline_id');
    }
}


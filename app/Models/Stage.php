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
        'is_interview',
    ];

    public function candidates()
    {
        return $this->hasManyThrough(Candidate::class, PipelineStages::class, 'stage_id', 'id', 'id', 'pipeline_id')->distinct();
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


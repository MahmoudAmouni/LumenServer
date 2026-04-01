<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PipelineStage;

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

    public function pipelineStages()
    {
        return $this->hasMany(PipelineStage::class, 'stage_id');
    }

    public function pipelines()
    {
        return $this->hasManyThrough(Pipeline::class, PipelineStage::class, 'stage_id', 'id', 'id', 'pipeline_id');
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PipelineStage;
use App\Models\Stage;
use App\Models\Job;

class Pipeline extends Model
{
    use HasFactory;
    protected $table = 'pipelines';
    
    protected $fillable = [
        'job_id',
        'name',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function pipelineStages()
    {
        return $this->hasMany(PipelineStage::class, 'pipeline_id');
    }

    public function stages()
    {
        return $this->hasManyThrough(Stage::class, PipelineStage::class, 'pipeline_id', 'id', 'id', 'stage_id')->orderBy('pipeline_stages.order', 'asc');
    }
}

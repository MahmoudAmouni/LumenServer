<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pipeline extends Model
{
  protected $fillable = [
    'job_id',
    'name',
];

public function job()
{
    return $this->belongsTo(Job::class, 'job_id');
}

public function stages()
{
    return $this->hasMany(PipelineStage::class, 'pipeline_id')->orderBy('order');
}

public function pipelineStages()
{
    return $this->belongsToMany(PipelineStage::class, 'pipeline_pipeline_stages', 'pipeline_id', 'pipeline_stage_id');
}
}

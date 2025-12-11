<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stage extends Model
{
  protected $fillable = [
  
    'name',
    'order',
];

public function pipeline()
{
    return $this->belongsTo(Pipeline::class, 'pipeline_id');
}

public function candidatePipelineStages()
{
    return $this->hasMany(CandidatePipelineStage::class, 'pipeline_stage_id');
}

public function pipelines()
{
    return $this->belongsToMany(Pipeline::class, 'pipeline_pipeline_stages', 'pipeline_stage_id', 'pipeline_id');
}

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PipelineStage extends Model
{
  protected $fillable = [
    'pipeline_id',
    'name',
    'order',
];

public function pipeline()
{
    return $this->belongsTo(Pipeline::class, 'pipeline_id');
}
}

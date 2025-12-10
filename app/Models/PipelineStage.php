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


}

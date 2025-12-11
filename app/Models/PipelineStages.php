<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PipelineStages extends Model
{
    use HasFactory;
    
    protected $table = 'pipeline_pipeline_stages';
    
    protected $fillable = [
        'pipeline_stage_id',
        'pipeline_id',
    ];

    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class, 'pipeline_id');
    }

    public function stage()
    {
        return $this->belongsTo(Stage::class, 'pipeline_stage_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pipeline;
use App\Models\Stage;
use App\Models\CandidatePipelineStage;

class PipelineStage extends Model
{
    use HasFactory;
    
    protected $table = 'pipeline_stages';
    
    protected $fillable = [
        'stage_id',
        'pipeline_id',
        'order',
    ];

    public function pipeline()
    {
        return $this->belongsTo(Pipeline::class, 'pipeline_id');
    }

    public function stage()
    {
        return $this->belongsTo(Stage::class, 'stage_id');
    }

    public function candidatePipelineStages()
    {
        return $this->hasMany(CandidatePipelineStage::class, 'pipeline_stage_id');
    }
}

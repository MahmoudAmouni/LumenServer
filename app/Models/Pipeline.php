<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PipelineStages;

class Pipeline extends Model
{
    use HasFactory;
    
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
        return $this->hasMany(PipelineStages::class, 'pipeline_id');
    }

    public function stages()
    {
        return $this->hasManyThrough(Stage::class, PipelineStages::class, 'pipeline_id', 'id', 'id', 'stage_id')->orderBy('order');
    }
}

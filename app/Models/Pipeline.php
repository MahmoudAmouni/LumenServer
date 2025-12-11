<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function stages()
    {
        return $this->belongsToMany(Stage::class, 'pipeline_pipeline_stages', 'pipeline_id', 'pipeline_stage_id')->orderBy('order');
    }
}

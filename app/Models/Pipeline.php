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
}

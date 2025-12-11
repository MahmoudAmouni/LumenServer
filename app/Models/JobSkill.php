<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobSkill extends Model
{
    use HasFactory;
  protected $fillable = [
    'job_id',
    'skill_id',
    'Type',
];

public function job()
{
    return $this->belongsTo(Job::class, 'job_id');
}

public function skill()
{
    return $this->belongsTo(Skill::class, 'skill_id');
}
}

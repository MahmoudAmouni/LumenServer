<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
  protected $fillable = [
    'name',
];

public function jobSkills()
{
    return $this->hasMany(JobSkill::class, 'skill_id');
}

}

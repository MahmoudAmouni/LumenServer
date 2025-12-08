<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
   protected $fillable = [
    'user_id',
    'full_name',
    'email',
    'phone',
    'level',
    'portfolio_url',
    'github_url',
    'linkedin_url',
    'cv_path',
];

public function user()
{
    return $this->belongsTo(User::class, 'user_id');
}

}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Intreview extends Model
{
    use HasFactory;
   protected $fillable = [
    'candidate_id',
    'interviewer_id',
    'scorecard_id',
    'notes',
    'duration',
    'scheduled_at',
    'status',
];

public function candidate()
{
    return $this->belongsTo(Candidate::class, 'candidate_id');
}

public function interviewer()
{
    return $this->belongsTo(User::class, 'interviewer_id');
}
<<<<<<< HEAD

public function scorecard()
{
    return $this->belongsTo(Scorecard::class, 'scorecard_id');
}

public function scorecards()
{
    return $this->hasMany(Scorecard::class, 'interview_id');
}
=======
>>>>>>> 7b572f4ed1bd6b6017fd2b52646c99ce98ad0b82
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    use HasFactory;
    
    protected $table = 'interviews';
    
    protected $fillable = [
        'candidate_id',
        'interviewer_id',
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

    public function scorecards()
    {
        return $this->hasMany(Scorecard::class, 'interview_id');
    }
}

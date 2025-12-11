<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scorecard extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'candidate_id',
        'interview_id',
        'status',
        'scorerate_id',
        'job_id',
        'scorelabel_id',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class, 'candidate_id');
    }

    public function interview()
    {
        return $this->belongsTo(Interview::class, 'interview_id');
    }

    public function scorelabel()
    {
        return $this->belongsTo(ScoreLabel::class, 'scorelabel_id');
    }

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }
}

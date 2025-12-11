<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class offer extends Model
{
    use HasFactory;
 protected $fillable = [
        'candidate_id',
        'job_id',
        'salary',
        'start_date',
        'contract_type',
        'offer_letter_template',
        'status',
        'recruiter_id',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class, 'candidate_id');
    }
    
    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }
    
    public function recruiter()
    {
        return $this->belongsTo(User::class, 'recruiter_id');
    }

}

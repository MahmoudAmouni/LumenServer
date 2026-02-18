<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use  HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'type_id',
       'company_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $dates = [];

    protected $casts = [
        'password' => 'hashed',
    ];


    public function userType()
    {
        return $this->belongsTo(UserType::class, 'type_id');
    }

    public function company()
    {
        return $this->belongsTo(CompanyName::class, 'company_id');
    }

    public function jobsAsRecruiter()
    {
        return $this->hasMany(Job::class, 'recruiter_id');
    }

    public function candidateJobsAdded()
    {
        return $this->hasMany(CandidateJob::class, 'recruiter_id');
    }

    public function interviewsAsInterviewer()
    {
        return $this->hasMany(Interview::class, 'interviewer_id');
    }

    public function offersAsRecruiter()
    {
        return $this->hasMany(Offer::class, 'recruiter_id');
    }


    public function copilotQueriesAsRecruiter()
    {
        return $this->hasMany(CopilotQuery::class, 'recruiter_id');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

}

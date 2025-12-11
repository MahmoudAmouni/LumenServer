<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'type_id',
        'company_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function userType()
    {
        return $this->belongsTo(UserType::class, 'type_id');
    }

public function company()
{
    return $this->belongsTo(CompanyName::class, 'company_id');
}

public function candidate()
{
    return $this->hasOne(Candidate::class, 'user_id');
}

public function jobsAsHiringManager()
{
    return $this->hasMany(Job::class, 'hiring_manager_id');
}

public function candidateJobsAdded()
{
    return $this->hasMany(CandidateJob::class, 'added_by_recruiter_id');
}

public function interviewsAsHiringManager()
{
    return $this->hasMany(Intreview::class, 'hiring_manager_id');
}

public function offersAsRecruiter()
{
    return $this->hasMany(offer::class, 'recruiter_id');
}

public function scorecardsAsEvaluator()
{
    return $this->hasMany(Scorecard::class, 'evaluator_id');
}

public function copilotQueriesAsRecruiter()
{
    return $this->hasMany(CopilotQuery::class, 'query_by_recruiter_id');
}

}

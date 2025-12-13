<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use  HasFactory, Notifiable;

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

    protected $dates = [];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

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

/**
 * Get the identifier that will be stored in the subject claim of the JWT.
 *
 * @return mixed
 */

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }


/**
 * Return a key value array, containing any custom claims to be added to the JWT.
 *
 * @return array
 */
public function getJWTCustomClaims()
{
    return [];
}

}

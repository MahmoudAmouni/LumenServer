<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
 protected $fillable = [
    'title',
    'level',
    'location',
    'remote',
    'description',
    'hiring_manager_id',
    'company_id',
];

public function hiringManager()
{
    return $this->belongsTo(User::class, 'hiring_manager_id');
}

public function company()
{
    return $this->belongsTo(CompanyName::class, 'company_id');
}
}

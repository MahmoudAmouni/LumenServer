<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class offer extends Model
{
 protected $fillable = [
        'candidate_id',
        'base_salary',
        'bonus',
        'start_date',
        'contract_type',
        'offer_letter_template',
        'status',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class, 'candidate_id');
    }

}

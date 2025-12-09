<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class scorelabel extends Model
{
    protected $fillable = [
        'label',
        'max_score',
    ];
  public function scorecards(){
    return $this->hasMany(Scorecard::class, 'scorelabel_id');
}

}

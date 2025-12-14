<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScoreLabel extends Model
{
    use HasFactory;
    
    protected $table = 'score_labels';
    
    protected $fillable = [
        'name'
    ];
    
    public function scorecards()
    {
        return $this->hasMany(Scorecard::class, 'scorelabel_id');
    }
}

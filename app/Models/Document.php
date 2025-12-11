<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
   protected $fillable = [
    'candidate_id',
    'type',
    'name',
    'file_path',
    'mime_type',
    'extracted_text',
    'notes',
];

public function candidate()
{
    return $this->belongsTo(Candidate::class, 'candidate_id');
}

public function chunks()
{
    return $this->hasMany(DocumentChunk::class, 'document_id');
}
}

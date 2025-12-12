<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentChunk extends Model
{
    use HasFactory;
   protected $fillable = [
    'document_id',
    'chunk_text',
    'embedding',
    'chunk_index',
    'page_number',
    'section',
];

public function document()
{
    return $this->belongsTo(Document::class, 'document_id');
}
}

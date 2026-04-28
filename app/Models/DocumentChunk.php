<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pgvector\Laravel\HasNeighbors;

class DocumentChunk extends Model
{
    use HasNeighbors;

    protected $fillable = [
        'document_id', 'chunk_index', 'content', 'embedding',
    ];

    // Không cast embedding — pgvector nhận string format '[x,y,z,...]' trực tiếp
    // Việc cast 'array' sẽ double-encode thành JSON string gây lỗi

    public function document(): BelongsTo
    {
        return $this->belongsTo(FamilyDocument::class, 'document_id');
    }
}

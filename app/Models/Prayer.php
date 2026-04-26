<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prayer extends Model
{
    protected $fillable = [
        'user_id', 'memorial_event_id', 'title', 'content', 'ai_generated',
    ];

    protected function casts(): array
    {
        return [
            'ai_generated' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function memorialEvent(): BelongsTo
    {
        return $this->belongsTo(MemorialEvent::class);
    }
}

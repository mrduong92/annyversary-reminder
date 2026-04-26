<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Recipient extends Model
{
    protected $fillable = [
        'user_id', 'name', 'phone', 'notify_days_before', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'notify_days_before' => 'array',
            'is_active'          => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function memorialEvents(): BelongsToMany
    {
        return $this->belongsToMany(MemorialEvent::class, 'event_recipient')
                    ->withPivot('notify_days_before');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // notify_days_before hiệu lực cho một event cụ thể
    // Dùng pivot override nếu có, fallback về default của recipient
    public function effectiveDays(?array $pivotDays = null): array
    {
        return $pivotDays ?? $this->notify_days_before ?? [1];
    }
}

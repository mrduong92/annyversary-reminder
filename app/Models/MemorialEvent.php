<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemorialEvent extends Model
{
    protected $fillable = [
        'user_id', 'name', 'relationship', 'lunar_day', 'lunar_month',
        'date_type', 'solar_date_next', 'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'solar_date_next' => 'date',
            'is_active'       => 'boolean',
            'lunar_day'       => 'integer',
            'lunar_month'     => 'integer',
        ];
    }

    public function isLunar(): bool { return $this->date_type === 'lunar'; }
    public function isSolar(): bool { return $this->date_type === 'solar'; }

    public function dateLabel(): string
    {
        return $this->isLunar()
            ? "{$this->lunar_day}/{$this->lunar_month} âm"
            : "{$this->lunar_day}/{$this->lunar_month} dương";
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(Recipient::class, 'event_recipient')
                    ->withPivot('notify_days_before');
    }

    public function prayers(): HasMany
    {
        return $this->hasMany(Prayer::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

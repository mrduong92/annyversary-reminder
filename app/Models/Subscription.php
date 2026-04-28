<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'user_id', 'plan', 'amount', 'note', 'activated_by', 'starts_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at'  => 'datetime',
            'expires_at' => 'datetime',
            'amount'     => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function planLabel(): string
    {
        return match ($this->plan) {
            'mini'    => 'Mini (100k/năm)',
            'premium' => 'Premium (200k/năm)',
            default   => 'Free',
        };
    }
}

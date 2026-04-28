<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'phone', 'email', 'password',
        'subscription_plan', 'subscription_expires_at', 'phone_verified_at',
        'zns_count_this_year', 'zns_count_reset_year',
        'ai_prayer_count_this_month', 'ai_message_count_today', 'ai_message_reset_date',
    ];

    protected $attributes = [
        'subscription_plan'          => 'free',
        'zns_count_this_year'        => 0,
        'ai_prayer_count_this_month' => 0,
        'ai_message_count_today'     => 0,
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'phone_verified_at'       => 'datetime',
            'subscription_expires_at' => 'datetime',
            'ai_message_reset_date'   => 'date',
            'password'                => 'hashed',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────

    public function familyGroups(): HasMany    { return $this->hasMany(FamilyGroup::class); }
    public function memorialEvents(): HasMany  { return $this->hasMany(MemorialEvent::class); }
    public function recipients(): HasMany      { return $this->hasMany(Recipient::class); }
    public function prayers(): HasMany         { return $this->hasMany(Prayer::class); }
    public function familyShares(): HasMany    { return $this->hasMany(FamilyShare::class); }
    public function familyDocuments(): HasMany { return $this->hasMany(FamilyDocument::class); }
    public function notificationLogs(): HasMany{ return $this->hasMany(NotificationLog::class); }
    public function subscriptions(): HasMany   { return $this->hasMany(Subscription::class); }

    public function defaultFamilyGroup(): ?FamilyGroup
    {
        return $this->familyGroups()->where('is_default', true)->first()
            ?? $this->familyGroups()->first();
    }

    // ── Plan helpers ──────────────────────────────────────────────

    public function isFree(): bool    { return ($this->subscription_plan ?? 'free') === 'free' || $this->isExpired(); }
    public function isMini(): bool    { return $this->subscription_plan === 'mini' && ! $this->isExpired(); }
    public function isPremium(): bool { return $this->subscription_plan === 'premium' && ! $this->isExpired(); }
    public function isPaid(): bool    { return ! $this->isFree(); }

    public function isExpired(): bool
    {
        return $this->subscription_plan !== 'free'
            && $this->subscription_expires_at !== null
            && $this->subscription_expires_at->isPast();
    }

    public function planLabel(): string
    {
        if ($this->isExpired()) return 'Free (hết hạn)';
        return match ($this->subscription_plan) {
            'mini'    => 'Mini',
            'premium' => 'Premium',
            default   => 'Free',
        };
    }
}

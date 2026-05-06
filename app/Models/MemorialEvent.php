<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemorialEvent extends Model
{
    // Nhãn tiếng Việt theo event_type
    public const TYPE_LABELS = [
        'anniversary_of_death' => 'Ngày giỗ',
        'ancestor_anniversary' => 'Giỗ tổ',
        'birthday'             => 'Sinh nhật',
        'anniversary'          => 'Kỷ niệm',
        'event'                => 'Sự kiện',
    ];

    public const DEFAULT_TYPE = 'anniversary_of_death';

    protected $fillable = [
        'user_id', 'family_group_id', 'family_member_id',
        'title', 'event_type',
        'lunar_day', 'lunar_month', 'date_type', 'solar_date_next', 'notes', 'is_active',
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

    // ── Accessors delegating to FamilyMember (backward compat) ──────────────

    public function getNameAttribute(): ?string
    {
        return $this->familyMember?->name;
    }

    public function getPronounAttribute(): ?string
    {
        return $this->familyMember?->pronoun;
    }

    public function getRelationshipAttribute(): ?string
    {
        return $this->familyMember?->relationship;
    }

    // ── Display ──────────────────────────────────────────────────────────────

    /**
     * Tên hiển thị ưu tiên theo thứ tự:
     * 1. Thành viên gia phả (có pronoun → "Cụ Nguyễn Văn A")
     * 2. Title tự do ("Ngày giỗ Ông nội")
     */
    public function displayName(): string
    {
        $m = $this->familyMember;
        if ($m) {
            return $m->pronoun ? "{$m->pronoun} {$m->name}" : $m->name;
        }
        return $this->title ?? '';
    }

    public function isLunar(): bool { return $this->date_type === 'lunar'; }
    public function isSolar(): bool { return $this->date_type === 'solar'; }
    public function isGios(): bool  { return ($this->event_type ?? self::DEFAULT_TYPE) === self::DEFAULT_TYPE; }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->event_type ?? self::DEFAULT_TYPE] ?? 'Sự kiện';
    }

    public function dateLabel(): string
    {
        return $this->isLunar()
            ? "{$this->lunar_day}/{$this->lunar_month} âm"
            : "{$this->lunar_day}/{$this->lunar_month} dương";
    }

    // ── Relations ────────────────────────────────────────────────────────────

    public function user(): BelongsTo         { return $this->belongsTo(User::class); }
    public function familyMember(): BelongsTo { return $this->belongsTo(FamilyMember::class); }

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(Recipient::class, 'event_recipient')
                    ->withPivot('notify_days_before');
    }

    public function prayers(): HasMany         { return $this->hasMany(Prayer::class); }
    public function notificationLogs(): HasMany { return $this->hasMany(NotificationLog::class); }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->with('familyMember');
    }
}

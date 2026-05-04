<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class FamilyMember extends Model
{
    protected $fillable = [
        'family_group_id', 'user_id', 'name', 'pronoun', 'relationship', 'gender',
        'birth_year', 'death_year', 'death_day', 'death_month', 'death_date_type', 'notes',
        'is_alive',
    ];

    protected function casts(): array
    {
        return [
            'birth_year'  => 'integer',
            'death_year'  => 'integer',
            'death_day'   => 'integer',
            'death_month' => 'integer',
            'is_alive'    => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Mỗi khi thành viên thay đổi → đánh dấu cây đã cập nhật → invalidate download unlock cũ
        static::saved(function (FamilyMember $member) {
            FamilyGroup::where('id', $member->family_group_id)
                ->update(['tree_updated_at' => now()]);
        });

        static::deleted(function (FamilyMember $member) {
            FamilyGroup::where('id', $member->family_group_id)
                ->update(['tree_updated_at' => now()]);
        });

        static::saved(function (FamilyMember $member) {
            if (!$member->is_alive && $member->death_day && $member->death_month) {
                $lunar = app(\App\Services\LunarCalendarService::class);
                $type  = $member->death_date_type ?? 'lunar';
                
                $solarNext = $type === 'solar'
                    ? $lunar->nextSolarOccurrence($member->death_day, $member->death_month)
                    : $lunar->nextOccurrence($member->death_day, $member->death_month);

                $event = $member->memorialEvents()->first();
                if ($event) {
                    $event->update([
                        'lunar_day'       => $member->death_day,
                        'lunar_month'     => $member->death_month,
                        'date_type'       => $type,
                        'solar_date_next' => $solarNext,
                    ]);
                } else {
                    $member->memorialEvents()->create([
                        'user_id'         => $member->user_id,
                        'family_group_id' => $member->family_group_id,
                        'lunar_day'       => $member->death_day,
                        'lunar_month'     => $member->death_month,
                        'date_type'       => $type,
                        'solar_date_next' => $solarNext,
                        'is_active'       => true,
                    ]);
                }
            } elseif ($member->is_alive) {
                // Nếu được cập nhật thành "Còn sống", tự động xóa các ngày giỗ liên quan
                $member->memorialEvents()->delete();
            }
        });
    }

    public function familyGroup(): BelongsTo
    {
        return $this->belongsTo(FamilyGroup::class);
    }

    /**
     * Link ngược nhanh: member → event qua family_members.memorial_event_id
     * Dùng trong genealogy view để hiển thị ngày giỗ của thành viên.
     */
    public function memorialEvent(): BelongsTo
    {
        return $this->belongsTo(MemorialEvent::class);
    }

    /**
     * Tất cả ngày giỗ của thành viên này (qua memorial_events.family_member_id).
     * Thường chỉ có 1 nhưng cho phép nhiều.
     */
    public function memorialEvents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MemorialEvent::class);
    }

    /** HasOne convenience: ngày giỗ đầu tiên của member này */
    public function derivedEvent(): HasOne
    {
        return $this->hasOne(MemorialEvent::class);
    }

    public function children(): \Illuminate\Database\Eloquent\Collection
    {
        $ids = DB::table('family_relationships')
            ->where('member_id', $this->id)->where('type', 'parent_child')
            ->pluck('related_member_id');

        return static::whereIn('id', $ids)->orderBy('birth_year')->get();
    }

    public function parents(): \Illuminate\Database\Eloquent\Collection
    {
        $ids = DB::table('family_relationships')
            ->where('related_member_id', $this->id)->where('type', 'parent_child')
            ->pluck('member_id');

        return static::whereIn('id', $ids)->get();
    }

    public function spouses(): \Illuminate\Database\Eloquent\Collection
    {
        $ids = DB::table('family_relationships')
            ->where('type', 'spouse')
            ->where(fn ($q) => $q->where('member_id', $this->id)->orWhere('related_member_id', $this->id))
            ->get()
            ->map(fn ($r) => $r->member_id == $this->id ? $r->related_member_id : $r->member_id);

        return static::whereIn('id', $ids)->get();
    }

    public function isAlive(): bool   { return $this->is_alive; }
    public function genderIcon(): string
    {
        return match ($this->gender) { 'male' => '♂', 'female' => '♀', default => '' };
    }
    public function lifespan(): string
    {
        if ($this->birth_year && $this->death_year) return "{$this->birth_year}–{$this->death_year}";
        if ($this->birth_year && !$this->is_alive) return "sinh {$this->birth_year} (Đã mất)";
        if ($this->birth_year) return "sinh {$this->birth_year}";
        if (!$this->is_alive) return "(Đã mất)";
        return '';
    }
}

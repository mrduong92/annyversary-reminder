<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FamilyGroup extends Model
{
    protected $fillable = ['user_id', 'name', 'color', 'is_default', 'remind_full_moon', 'remind_first_day', 'tree_updated_at'];

    protected function casts(): array
    {
        return [
            'is_default'       => 'boolean',
            'remind_full_moon'       => 'boolean',
            'remind_first_day'  => 'boolean',
            'tree_updated_at'  => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function memorialEvents(): HasMany
    {
        return $this->hasMany(MemorialEvent::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(Recipient::class);
    }

    public function prayers(): HasMany
    {
        return $this->hasMany(Prayer::class);
    }

    public function familyShares(): HasMany
    {
        return $this->hasMany(FamilyShare::class);
    }

    public function familyDocuments(): HasMany
    {
        return $this->hasMany(FamilyDocument::class);
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function downloadUnlocks(): HasMany
    {
        return $this->hasMany(DownloadUnlock::class);
    }

    /** Kiểm tra user có unlock download hợp lệ không (cây chưa bị sửa sau lần unlock gần nhất) */
    public function hasValidUnlock(int $userId): bool
    {
        return (bool) $this->validUnlock($userId);
    }

    /** Lấy unlock hợp lệ gần nhất của user cho group này */
    public function validUnlock(int $userId): ?DownloadUnlock
    {
        $treeUpdatedAt = $this->tree_updated_at;

        $query = $this->downloadUnlocks()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->latest();

        if ($treeUpdatedAt) {
            $query->where('tree_snapshot_at', '>=', $treeUpdatedAt);
        }

        return $query->first();
    }

    /** Lấy share link duy nhất của group, tạo mới nếu chưa có */
    public function getOrCreateShare(): FamilyShare
    {
        return $this->familyShares()->first()
            ?? $this->familyShares()->create([
                'user_id'    => $this->user_id,
                'name'       => $this->name,
                'is_active'  => true,
            ]);
    }

    public function activeShare(): ?FamilyShare
    {
        return $this->familyShares()->where('is_active', true)->where('type', 'chatbot')->first();
    }

    /** Tạo hoặc lấy share link cho cây gia phả (view-only, không cần auth) */
    public function getOrCreateTreeShare(): FamilyShare
    {
        return $this->familyShares()->where('type', 'tree')->first()
            ?? $this->familyShares()->create([
                'user_id'   => $this->user_id,
                'type'      => 'tree',
                'name'      => 'Cây gia phả ' . $this->name,
                'is_active' => true,
            ]);
    }

    public function activeTreeShare(): ?FamilyShare
    {
        return $this->familyShares()->where('type', 'tree')->where('is_active', true)->first();
    }
}

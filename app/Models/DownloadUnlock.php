<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DownloadUnlock extends Model
{
    protected $fillable = [
        'user_id',
        'family_group_id',
        'tree_snapshot_at',
        'template_id',
        'file_path',
        'status',
        'amount',
        'payment_note',
    ];

    protected function casts(): array
    {
        return [
            'tree_snapshot_at' => 'datetime',
            'amount'           => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function familyGroup(): BelongsTo
    {
        return $this->belongsTo(FamilyGroup::class);
    }

    /** Unlock này còn hiệu lực không (cây chưa bị sửa sau khi unlock) */
    public function isValid(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $group = $this->familyGroup;
        if (! $group?->tree_updated_at) {
            return true; // cây chưa bao giờ được sửa
        }

        return $this->tree_snapshot_at && $this->tree_snapshot_at->gte($group->tree_updated_at);
    }
}

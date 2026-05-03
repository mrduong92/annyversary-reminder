<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrintOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'template_id',
        'member_ids',
        'shipping_name',
        'shipping_phone',
        'shipping_address',
        'shipping_city',
        'shipping_district',
        'shipping_ward',
        'pdf_path',
        'status',
        'tracking_number',
        'shipping_company',
        'shipping_fee',
        'notes',
        'total_price',
    ];

    protected $casts = [
        'member_ids' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Template config mapping will be handled in service, no eloquent relation needed

    public function members()
    {
        return $this->belongsToMany(FamilyMember::class);
    }

    public function scopeSearch($query)
    {
        return $query->where('user_id', auth()->id());
    }

    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'cancelled');
    }
}

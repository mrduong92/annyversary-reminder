<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrintTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'preview_image',
        'price',
        'is_free',
        'is_active',
        'width',
        'height',
        'padding',
        'show_title',
        'title_text',
        'show_footer',
        'footer_text',
        'background_pattern',
        'view_name',
    ];

    protected $casts = [
        'is_free' => 'boolean',
        'is_active' => 'boolean',
        'show_title' => 'boolean',
        'show_footer' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function orders()
    {
        return $this->hasMany(PrintOrder::class);
    }
}

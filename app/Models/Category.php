<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    // Kolom yang boleh diisi massal
    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'is_active',
    ];

    // Tipe data khusus
    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Auto-generate slug dari name
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function ($category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'description',
        'daily_rate',
        'image',
        'is_active',
    ];

    protected $casts = [
        'daily_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    // Relasi ke Category
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Relasi ke Brand
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    // Relasi ke ProductUnit (akan dibuat nanti)
    public function units(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }
}
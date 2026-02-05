<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DocumentType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'is_required',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function customerDocuments(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }

    public function customerCategories(): BelongsToMany
    {
        return $this->belongsToMany(CustomerCategory::class, 'customer_category_document_type');
    }

    public static function getRequiredTypes(?int $categoryId = null)
    {
        $query = self::where('is_active', true)
            ->where('is_required', true)
            ->orderBy('sort_order');

        if ($categoryId) {
            $query->whereHas('customerCategories', function ($q) use ($categoryId) {
                $q->where('customer_categories.id', $categoryId);
            });
        }

        return $query->get();
    }

    public static function getActiveTypes(?int $categoryId = null)
    {
        $query = self::where('is_active', true)
            ->orderBy('sort_order');

        if ($categoryId) {
            $query->whereHas('customerCategories', function ($q) use ($categoryId) {
                $q->where('customer_categories.id', $categoryId);
            });
        }

        return $query->get();
    }
}
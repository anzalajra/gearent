<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public static function getRequiredTypes()
    {
        return self::where('is_active', true)
            ->where('is_required', true)
            ->orderBy('sort_order')
            ->get();
    }

    public static function getActiveTypes()
    {
        return self::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}
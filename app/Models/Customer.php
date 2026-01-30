<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'id_card_number',
        'id_card_image',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relasi ke Rental (akan dibuat nanti)
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }
}
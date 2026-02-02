<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'id_number',
        'id_type',
        'password',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function getActiveRentals()
    {
        return $this->rentals()
            ->whereIn('status', [Rental::STATUS_PENDING, Rental::STATUS_ACTIVE, Rental::STATUS_LATE_PICKUP, Rental::STATUS_LATE_RETURN])
            ->orderBy('start_date', 'desc')
            ->get();
    }

    public function getPastRentals()
    {
        return $this->rentals()
            ->whereIn('status', [Rental::STATUS_COMPLETED, Rental::STATUS_CANCELLED])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
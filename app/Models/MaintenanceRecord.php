<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceRecord extends Model
{
    protected $fillable = [
        'product_unit_id',
        'technician_id',
        'title',
        'description',
        'cost',
        'date',
        'status',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'date' => 'date',
    ];

    public function productUnit()
    {
        return $this->belongsTo(ProductUnit::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}

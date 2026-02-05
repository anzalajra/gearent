<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NavigationMenu extends Model
{
    protected $fillable = [
        'name',
        'handle',
        'items',
    ];

    protected $casts = [
        'items' => 'array',
    ];
}

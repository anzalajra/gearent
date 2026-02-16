<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'account_id',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}

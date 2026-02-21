<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'code',
        'name',
        'type',
        'subtype',
        'is_sub_account',
        'is_active',
        'description',
        'balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_sub_account' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function items()
    {
        return $this->hasMany(JournalEntryItem::class);
    }

    /**
     * Recalculate balance based on journal entry items
     */
    public function recalculateBalance(): void
    {
        $debit = $this->items()->sum('debit');
        $credit = $this->items()->sum('credit');
        
        // Normal Balance Logic
        // Asset, Expense: Debit - Credit
        // Liability, Equity, Revenue: Credit - Debit
        if (in_array($this->type, ['asset', 'expense'])) {
            $this->balance = $debit - $credit;
        } else {
            $this->balance = $credit - $debit;
        }
        
        $this->saveQuietly();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class Expense extends FinanceTransaction
{
    protected $table = 'finance_transactions';

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('expense', function (Builder $builder) {
            $builder->where('type', self::TYPE_EXPENSE)
                    ->whereNull('reference_type');
        });

        static::creating(function ($expense) {
            $expense->type = self::TYPE_EXPENSE;
        });
    }
}

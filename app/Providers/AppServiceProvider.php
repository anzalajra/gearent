<?php

namespace App\Providers;

use App\Models\Rental;
use App\Observers\RentalObserver;
use Illuminate\Support\ServiceProvider;
use App\Models\Cart;
use App\Policies\CartPolicy;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Rental::observe(RentalObserver::class);
    
        Gate::policy(Cart::class, CartPolicy::class);
    }
}
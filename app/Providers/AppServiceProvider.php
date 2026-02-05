<?php

namespace App\Providers;

use App\Models\Rental;
use App\Models\Setting;
use App\Observers\RentalObserver;
use Illuminate\Support\Facades\View;
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

        View::composer('pdf.*', function ($view) {
            $settings = Setting::where('key', 'like', 'doc_%')
                ->pluck('value', 'key')
                ->toArray();
            
            $view->with('doc_settings', $settings);
        });
    }
}
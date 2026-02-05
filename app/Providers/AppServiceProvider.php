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

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                if (Setting::get('notification_email_enabled')) {
                    $mailConfig = [];
                    
                    if ($host = Setting::get('mail_host')) $mailConfig['mail.mailers.smtp.host'] = $host;
                    if ($port = Setting::get('mail_port')) $mailConfig['mail.mailers.smtp.port'] = $port;
                    if ($encryption = Setting::get('mail_encryption')) $mailConfig['mail.mailers.smtp.encryption'] = $encryption;
                    if ($username = Setting::get('mail_username')) $mailConfig['mail.mailers.smtp.username'] = $username;
                    if ($password = Setting::get('mail_password')) $mailConfig['mail.mailers.smtp.password'] = $password;
                    if ($fromAddress = Setting::get('mail_from_address')) $mailConfig['mail.from.address'] = $fromAddress;
                    if ($fromName = Setting::get('mail_from_name')) $mailConfig['mail.from.name'] = $fromName;

                    if (!empty($mailConfig)) {
                        config($mailConfig);
                    }
                }
            }
        } catch (\Exception $e) {
            // Settings table might not exist yet during migration
        }
    }
}
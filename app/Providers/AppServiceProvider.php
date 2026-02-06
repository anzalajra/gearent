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

        // Inject Theme Colors
        View::composer(['layouts.app', 'layouts.frontend', 'layouts.guest'], function ($view) {
            $primaryColor = \Filament\Support\Colors\Color::Blue; // Default
            
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                    $themePreset = Setting::get('theme_preset', 'default');
                    $themeColor = Setting::get('theme_color');

                    if ($themePreset === 'custom' && $themeColor) {
                        $primaryColor = \Filament\Support\Colors\Color::hex($themeColor);
                    } elseif ($themePreset && $themePreset !== 'default') {
                         $colors = [
                            'slate' => \Filament\Support\Colors\Color::Slate,
                            'gray' => \Filament\Support\Colors\Color::Gray,
                            'zinc' => \Filament\Support\Colors\Color::Zinc,
                            'neutral' => \Filament\Support\Colors\Color::Neutral,
                            'stone' => \Filament\Support\Colors\Color::Stone,
                            'red' => \Filament\Support\Colors\Color::Red,
                            'orange' => \Filament\Support\Colors\Color::Orange,
                            'amber' => \Filament\Support\Colors\Color::Amber,
                            'yellow' => \Filament\Support\Colors\Color::Yellow,
                            'lime' => \Filament\Support\Colors\Color::Lime,
                            'green' => \Filament\Support\Colors\Color::Green,
                            'emerald' => \Filament\Support\Colors\Color::Emerald,
                            'teal' => \Filament\Support\Colors\Color::Teal,
                            'cyan' => \Filament\Support\Colors\Color::Cyan,
                            'sky' => \Filament\Support\Colors\Color::Sky,
                            'blue' => \Filament\Support\Colors\Color::Blue,
                            'indigo' => \Filament\Support\Colors\Color::Indigo,
                            'violet' => \Filament\Support\Colors\Color::Violet,
                            'purple' => \Filament\Support\Colors\Color::Purple,
                            'fuchsia' => \Filament\Support\Colors\Color::Fuchsia,
                            'pink' => \Filament\Support\Colors\Color::Pink,
                            'rose' => \Filament\Support\Colors\Color::Rose,
                        ];

                        if (isset($colors[$themePreset])) {
                            $primaryColor = $colors[$themePreset];
                        }
                    }
                }
            } catch (\Exception $e) {
                // Fallback
            }

            $cssVariables = [];
            foreach ($primaryColor as $shade => $value) {
                $cssVariables[] = "--primary-{$shade}: {$value};";
            }
            
            $view->with('themeCssVariables', implode(' ', $cssVariables));
        });

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                // Global App Config
                $siteName = Setting::get('site_name');
                if ($siteName) {
                    config(['app.name' => $siteName]);
                    
                    // Set default mail from name to site name if not explicitly configured
                    if (!Setting::get('mail_from_name')) {
                        config(['mail.from.name' => $siteName]);
                    }
                }

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
<?php

namespace App\Providers;

use App\Models\Rental;
use App\Models\Setting;
use App\Models\FinanceTransaction;
use App\Models\JournalEntryItem;
use App\Observers\FinanceTransactionObserver;
use App\Observers\JournalEntryItemObserver;
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
        FinanceTransaction::observe(FinanceTransactionObserver::class);
        JournalEntryItem::observe(JournalEntryItemObserver::class);
    
        Gate::policy(Cart::class, CartPolicy::class);

        View::composer('pdf.*', function ($view) {
            $settings = Setting::where('key', 'like', 'doc_%')
                ->pluck('value', 'key')
                ->toArray();
            
            $view->with('doc_settings', $settings);
        });

        // Inject Theme Colors
        View::composer(['layouts.app', 'layouts.frontend', 'layouts.guest'], function ($view) {
            $primaryColor = \App\Services\ThemeService::getPrimaryColor();
            
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

                // Apply central mail settings (SMTP server + default from, managed by central admin)
                $centralMailSettings = \App\Filament\Central\Pages\EmailSettings::loadSettings();
                if (! empty($centralMailSettings)) {
                    $mailConfig = [];
                    if (! empty($centralMailSettings['mail_mailer'])) $mailConfig['mail.default'] = $centralMailSettings['mail_mailer'];
                    if (! empty($centralMailSettings['mail_host'])) $mailConfig['mail.mailers.smtp.host'] = $centralMailSettings['mail_host'];
                    if (! empty($centralMailSettings['mail_port'])) $mailConfig['mail.mailers.smtp.port'] = $centralMailSettings['mail_port'];
                    if (isset($centralMailSettings['mail_encryption'])) $mailConfig['mail.mailers.smtp.encryption'] = $centralMailSettings['mail_encryption'] ?: null;
                    if (! empty($centralMailSettings['mail_username'])) $mailConfig['mail.mailers.smtp.username'] = $centralMailSettings['mail_username'];
                    if (! empty($centralMailSettings['mail_password'])) $mailConfig['mail.mailers.smtp.password'] = $centralMailSettings['mail_password'];
                    if (! empty($centralMailSettings['mail_from_address'])) $mailConfig['mail.from.address'] = $centralMailSettings['mail_from_address'];
                    if (! empty($centralMailSettings['mail_from_name'])) $mailConfig['mail.from.name'] = $centralMailSettings['mail_from_name'];

                    if (! empty($mailConfig)) {
                        config($mailConfig);
                    }
                }

                // Override from address/name with tenant-specific sender identity
                if ($fromAddress = Setting::get('mail_from_address')) {
                    config(['mail.from.address' => $fromAddress]);
                }
                if ($fromName = Setting::get('mail_from_name')) {
                    config(['mail.from.name' => $fromName]);
                }
            }
        } catch (\Exception $e) {
            // Settings table might not exist yet during migration
        }

        // Configure Livewire Update Route to support both Central and Tenant domains
        \Livewire\Livewire::setUpdateRoute(function ($handle) {
            return \Illuminate\Support\Facades\Route::post('/livewire/update', $handle)
                ->middleware([
                    'web',
                    \App\Http\Middleware\TenantLivewireMiddleware::class,
                ]);
        });
    }
}
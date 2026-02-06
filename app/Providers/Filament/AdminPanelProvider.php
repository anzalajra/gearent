<?php

namespace App\Providers\Filament;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function register(): void
    {
        parent::register();
    }

    public function panel(Panel $panel): Panel
    {
        $primaryColor = Color::Amber;
        $navigationLayout = 'sidebar';

        try {
            if (Schema::hasTable('settings')) {
                $themePreset = Setting::get('theme_preset', 'default');
                $themeColor = Setting::get('theme_color');
                $navigationLayout = Setting::get('navigation_layout', 'sidebar');

                if ($themePreset === 'custom' && $themeColor) {
                    $primaryColor = Color::hex($themeColor);
                } elseif ($themePreset && $themePreset !== 'default') {
                    // Map preset names to Filament Colors
                    $colors = [
                        'slate' => Color::Slate,
                        'gray' => Color::Gray,
                        'zinc' => Color::Zinc,
                        'neutral' => Color::Neutral,
                        'stone' => Color::Stone,
                        'red' => Color::Red,
                        'orange' => Color::Orange,
                        'amber' => Color::Amber,
                        'yellow' => Color::Yellow,
                        'lime' => Color::Lime,
                        'green' => Color::Green,
                        'emerald' => Color::Emerald,
                        'teal' => Color::Teal,
                        'cyan' => Color::Cyan,
                        'sky' => Color::Sky,
                        'blue' => Color::Blue,
                        'indigo' => Color::Indigo,
                        'violet' => Color::Violet,
                        'purple' => Color::Purple,
                        'fuchsia' => Color::Fuchsia,
                        'pink' => Color::Pink,
                        'rose' => Color::Rose,
                    ];

                    if (isset($colors[$themePreset])) {
                        $primaryColor = $colors[$themePreset];
                    }
                }
            }
        } catch (\Exception $e) {
            // Fallback to default
        }

        // Configure the palette plugin to use the calculated primary color
        // This ensures compatibility with the plugin's global theme application
        // and supports all colors, not just those defined in the plugin's config file.
        Config::set('filament-palette.palette.dynamic_theme', [
            'primary' => $primaryColor,
            'warning' => Color::Amber,
            'danger'  => Color::Red,
            'success' => Color::Green,
            'info'    => Color::Blue,
        ]);
        Config::set('filament-palette.default', 'dynamic_theme');

        $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => $primaryColor,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                \App\Filament\Pages\Settings::class,
                \App\Filament\Pages\ProductSetup::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
                \App\Filament\Widgets\StatsOverview::class,
                \App\Filament\Widgets\LatestRentals::class,
                \App\Filament\Widgets\RentalChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);

        if ($navigationLayout === 'top') {
            $panel->topNavigation();
        }

        return $panel
            ->plugins([
                FilamentFullCalendarPlugin::make(),
                \Octopy\Filament\Palette\PaletteSwitcherPlugin::make()
                    ->applyThemeGlobally(true)
                    ->hidden(fn () => true),
            ])
            ->databaseNotifications()
            // Navigation Groups Order - mengatur urutan group di sidebar
            ->navigationGroups([
                'Rentals',
                'Sales',
                'Inventory',
                'Setting',
            ])
            // Sidebar collapsible (opsional - bisa dihapus jika tidak perlu)
            ->sidebarCollapsibleOnDesktop();
    }
}
<?php

namespace App\Services;

use App\Models\Setting;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Schema;

class ThemeService
{
    /**
     * Get the configured primary color for the application.
     *
     * @return array
     */
    public static function getPrimaryColor(): array
    {
        // Default color if settings are not available (Matches Filament Admin default)
        $primaryColor = Color::Amber; 

        try {
            // Check if settings table exists to avoid migration errors
            if (Schema::hasTable('settings')) {
                $themePreset = Setting::get('theme_preset', 'default');
                $themeColor = Setting::get('theme_color');

                if ($themePreset === 'custom' && $themeColor) {
                    $primaryColor = Color::hex($themeColor);
                } elseif ($themePreset && $themePreset !== 'default') {
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
            // Fallback to default if anything goes wrong
        }

        return $primaryColor;
    }
}

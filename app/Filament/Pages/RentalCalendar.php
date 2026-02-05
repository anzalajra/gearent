<?php

namespace App\Filament\Pages;

use BackedEnum;
use UnitEnum;
use App\Filament\Widgets\RentalCalendarWidget;
use Filament\Pages\Page;

class RentalCalendar extends Page
{
    // ikuti signature parent untuk navigationIcon (static, boleh BackedEnum)
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    protected static string|UnitEnum|null $navigationGroup = 'Rentals';
    protected static ?string $navigationLabel = 'Calendar';
    protected static ?string $title = 'Calendar';
    protected static ?int $navigationSort = 2;
    protected static bool $shouldRegisterNavigation = false;

    // <-- NOTE: view is non-static in the parent Page class, jadi harus non-static di sini
    protected string $view = 'filament.pages.rental-calendar';

    protected function getHeaderWidgets(): array
    {
        return [
            RentalCalendarWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}
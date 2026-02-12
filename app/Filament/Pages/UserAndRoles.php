<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;
use BackedEnum;

class UserAndRoles extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = 'Setting';

    protected static ?string $navigationLabel = 'Admins & Roles';

    protected static ?string $title = 'Admins & Roles';

    protected static ?string $slug = 'admins-and-roles';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.user-and-roles';
}

<?php

namespace App\Livewire;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RolesListWidget extends TableWidget
{
    public function table(Table $table): Table
    {
        return RoleResource::table($table)
            ->query(RoleResource::getEloquentQuery());
    }
}

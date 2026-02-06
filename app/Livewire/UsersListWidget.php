<?php

namespace App\Livewire;

use App\Filament\Resources\Users\UserResource;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UsersListWidget extends TableWidget
{
    public function table(Table $table): Table
    {
        return UserResource::table($table)
            ->query(UserResource::getEloquentQuery());
    }
}

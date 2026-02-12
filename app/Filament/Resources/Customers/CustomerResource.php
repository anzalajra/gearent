<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Resources\Customers\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Customers\Tables\CustomersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;
use Illuminate\Database\Eloquent\Builder;

class CustomerResource extends Resource
{
    protected static ?string $model = User::class;
    
    protected static ?string $modelLabel = 'Customer';

    protected static ?string $pluralModelLabel = 'Customers';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNotNull('customer_category_id');
    }

    protected static ?string $recordTitleAttribute = 'name';

    // Navigation Configuration
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';
    
    protected static string|UnitEnum|null $navigationGroup = 'Rentals';
    
    protected static ?int $navigationSort = 4;
    
    protected static ?string $navigationLabel = 'Customers';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_verified', false)->count() ?: null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return static::getModel()::where('is_verified', false)->count() . ' need verification';
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DocumentsRelationManager::class,
            \App\Filament\Resources\Customers\RelationManagers\RentalsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'view' => ViewCustomer::route('/{record}'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function getUrl(?string $name = null, array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null, bool $shouldGuessMissingParameters = false): string
    {
        if ($name === 'edit') {
            return \App\Filament\Resources\Users\UserResource::getUrl('edit', $parameters, $isAbsolute, $panel, $tenant);
        }
        
        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant, $shouldGuessMissingParameters);
    }
    

}
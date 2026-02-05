<?php

namespace App\Filament\Resources\CustomerCategories;

use App\Filament\Resources\CustomerCategories\Pages\CreateCustomerCategory;
use App\Filament\Resources\CustomerCategories\Pages\EditCustomerCategory;
use App\Filament\Resources\CustomerCategories\Pages\ListCustomerCategories;
use App\Filament\Resources\CustomerCategories\Schemas\CustomerCategoryForm;
use App\Filament\Resources\CustomerCategories\Tables\CustomerCategoriesTable;
use App\Models\CustomerCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CustomerCategoryResource extends Resource
{
    protected static ?string $model = CustomerCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Setting';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CustomerCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerCategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerCategories::route('/'),
            'create' => CreateCustomerCategory::route('/create'),
            'edit' => EditCustomerCategory::route('/{record}/edit'),
        ];
    }
}

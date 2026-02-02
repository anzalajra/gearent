<?php

namespace App\Filament\Resources\ProductUnits;

use App\Filament\Resources\ProductUnits\Pages\CreateProductUnit;
use App\Filament\Resources\ProductUnits\Pages\EditProductUnit;
use App\Filament\Resources\ProductUnits\Pages\ListProductUnits;
use App\Filament\Resources\ProductUnits\RelationManagers\KitsRelationManager; // Tambahkan ini
use App\Filament\Resources\ProductUnits\Schemas\ProductUnitForm;
use App\Filament\Resources\ProductUnits\Tables\ProductUnitsTable;
use App\Models\ProductUnit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductUnitResource extends Resource
{
    protected static ?string $model = ProductUnit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'serial_number';

    public static function form(Schema $schema): Schema
    {
        return ProductUnitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductUnitsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            KitsRelationManager::class, // Tambahkan ini
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductUnits::route('/'),
            'create' => CreateProductUnit::route('/create'),
            'edit' => EditProductUnit::route('/{record}/edit'),
        ];
    }
}
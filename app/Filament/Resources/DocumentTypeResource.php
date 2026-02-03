<?php

namespace App\Filament\Resources;

use App\Models\DocumentType;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class DocumentTypeResource extends Resource
{
    protected static ?string $model = DocumentType::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Setting';

    protected static ?string $navigationLabel = 'Document Types';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50),

                Textarea::make('description')
                    ->rows(2),

                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),

                Checkbox::make('is_required')
                    ->label('Required for verification'),

                Checkbox::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('code')
                    ->badge()
                    ->color('gray'),

                IconColumn::make('is_required')
                    ->label('Required')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\DocumentTypeResource\Pages\ListDocumentTypes::route('/'),
            'create' => \App\Filament\Resources\DocumentTypeResource\Pages\CreateDocumentType::route('/create'),
            'edit' => \App\Filament\Resources\DocumentTypeResource\Pages\EditDocumentType::route('/{record}/edit'),
        ];
    }
}
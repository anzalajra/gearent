<?php

namespace App\Filament\Resources\NavigationMenus;

use App\Filament\Resources\NavigationMenus\Pages\CreateNavigationMenu;
use App\Filament\Resources\NavigationMenus\Pages\EditNavigationMenu;
use App\Filament\Resources\NavigationMenus\Pages\ListNavigationMenus;
use App\Models\NavigationMenu;
use BackedEnum;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class NavigationMenuResource extends Resource
{
    protected static ?string $model = NavigationMenu::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static string|UnitEnum|null $navigationGroup = 'Setting';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('handle', \Illuminate\Support\Str::slug($state))),
                TextInput::make('handle')
                    ->required()
                    ->unique(ignoreRecord: true),
                Repeater::make('items')
                    ->schema([
                        TextInput::make('label')->required(),
                        TextInput::make('url')->label('URL')
                            ->placeholder('https://... or /path'),
                        Select::make('target')
                            ->options([
                                '_self' => 'Same Tab',
                                '_blank' => 'New Tab',
                            ])
                            ->default('_self'),
                        Repeater::make('children')
                            ->schema([
                                TextInput::make('label')->required(),
                                TextInput::make('url')->label('URL'),
                                Select::make('target')
                                    ->options([
                                        '_self' => 'Same Tab',
                                        '_blank' => 'New Tab',
                                    ])
                                    ->default('_self'),
                            ])
                            ->collapsible(),
                    ])
                    ->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('handle')->searchable(),
                TextColumn::make('updated_at')->dateTime(),
            ])
            ->filters([
                //
            ]);
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
            'index' => ListNavigationMenus::route('/'),
            'create' => CreateNavigationMenu::route('/create'),
            'edit' => EditNavigationMenu::route('/{record}/edit'),
        ];
    }
}

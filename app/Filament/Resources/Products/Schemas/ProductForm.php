<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Brand;
use App\Models\Category;
use App\Models\CustomerCategory;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->label('Category')
                    ->options(Category::where('is_active', true)->pluck('name', 'id'))
                    ->required()
                    ->searchable(),

                Select::make('brand_id')
                    ->label('Brand')
                    ->options(Brand::where('is_active', true)->pluck('name', 'id'))
                    ->required()
                    ->searchable(),

                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, callable $set) {
                        $set('slug', Str::slug($state ?? ''));
                    }),

                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),

                TextInput::make('daily_rate')
                    ->label('Daily Rate (Rp)')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0),

                Section::make('Product Variations')
                    ->description('Create variations for this product (e.g. 5M, 10M). If defined, these can be assigned to units.')
                    ->schema([
                        Repeater::make('variations')
                            ->relationship('variations')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Variation Name')
                                    ->placeholder('e.g. 5 Meter')
                                    ->required()
                                    ->maxLength(255),
                                
                                TextInput::make('daily_rate')
                                    ->label('Override Daily Rate')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->placeholder('Leave empty to use product rate'),
                            ])
                            ->columns(2)
                            ->addActionLabel('Add Variation')
                            ->grid([
                                'default' => 1,
                                'sm' => 2,
                            ])
                            ->defaultItems(0),
                    ])
                    ->collapsed()
                    ->collapsible(),

                FileUpload::make('image')
                    ->image()
                    ->directory('products'),

                Toggle::make('is_active')
                    ->default(true),

                CheckboxList::make('excludedCustomerCategories')
                    ->label('Hide from Customer Categories')
                    ->relationship('excludedCustomerCategories', 'name')
                    ->options(CustomerCategory::where('is_active', true)->pluck('name', 'id'))
                    ->columns(2)
                    ->helperText('Selected categories will NOT be able to see this product.'),
            ]);
    }
}
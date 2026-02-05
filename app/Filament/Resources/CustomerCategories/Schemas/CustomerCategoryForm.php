<?php

namespace App\Filament\Resources\CustomerCategories\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CustomerCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $state, callable $set) {
                        $set('slug', Str::slug($state));
                    }),

                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                ColorPicker::make('badge_color')
                    ->label('Badge Color')
                    ->nullable(),

                TextInput::make('discount_percentage')
                    ->label('Discount Percentage')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%')
                    ->helperText('This discount will be applied to all rentals for customers in this category.'),

                TagsInput::make('benefits')
                    ->label('Benefits')
                    ->helperText('List the benefits for this category (press Enter to add).'),

                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}

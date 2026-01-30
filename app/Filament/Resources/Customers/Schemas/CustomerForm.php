<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('phone')
                    ->tel()
                    ->required()
                    ->maxLength(20)
                    ->placeholder('08123456789'),

                TextInput::make('id_card_number')
                    ->label('No. KTP')
                    ->maxLength(20)
                    ->placeholder('3201234567890001'),

                FileUpload::make('id_card_image')
                    ->label('Foto KTP')
                    ->image()
                    ->directory('customers/ktp'),

                Textarea::make('address')
                    ->label('Alamat')
                    ->rows(3)
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
<?php

namespace App\Filament\Resources;

use App\Models\Customer;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Customers';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer Information')
                    ->schema([
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
                            ->maxLength(20),

                        TextInput::make('nik')
                            ->label('NIK (No. KTP)')
                            ->maxLength(16)
                            ->minLength(16),

                        Textarea::make('address')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable(),

                TextColumn::make('phone')
                    ->searchable(),

                TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable(),

                TextColumn::make('verification_status')
                    ->label('Verification')
                    ->badge()
                    ->getStateUsing(fn (Customer $record) => $record->getVerificationStatus())
                    ->color(fn (string $state) => match ($state) {
                        'verified' => 'success',
                        'pending' => 'warning',
                        'not_verified' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'verified' => 'Verified',
                        'pending' => 'Pending',
                        'not_verified' => 'Not Verified',
                    }),

                TextColumn::make('rentals_count')
                    ->label('Rentals')
                    ->counts('rentals'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_verified')
                    ->label('Verification Status')
                    ->options([
                        '1' => 'Verified',
                        '0' => 'Not Verified',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\CustomerResource\RelationManagers\DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\CustomerResource\Pages\ListCustomers::route('/'),
            'create' => \App\Filament\Resources\CustomerResource\Pages\CreateCustomer::route('/create'),
            'view' => \App\Filament\Resources\CustomerResource\Pages\ViewCustomer::route('/{record}'),
            'edit' => \App\Filament\Resources\CustomerResource\Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
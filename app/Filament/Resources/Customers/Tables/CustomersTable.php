<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Models\Customer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
<?php

namespace App\Filament\Resources\Quotations\RelationManagers;

use App\Models\Rental;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RentalsRelationManager extends RelationManager
{
    protected static string $relationship = 'rentals';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('rental_code')
            ->columns([
                TextColumn::make('rental_code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('total')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('add_rentals')
                    ->label('Add Rentals')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Select::make('rental_ids')
                            ->label('Select Rentals')
                            ->multiple()
                            ->options(function (RelationManager $livewire) {
                                return Rental::where('customer_id', $livewire->getOwnerRecord()->customer_id)
                                    ->whereNull('quotation_id')
                                    ->whereNull('invoice_id')
                                    ->pluck('rental_code', 'id');
                            })
                            ->required(),
                    ])
                    ->action(function (array $data, RelationManager $livewire) {
                        Rental::whereIn('id', $data['rental_ids'])->update([
                            'quotation_id' => $livewire->getOwnerRecord()->id,
                        ]);
                        
                        $livewire->getOwnerRecord()->recalculate();

                        Notification::make()
                            ->title('Rentals added successfully')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('remove')
                    ->label('Remove')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Rental $record, RelationManager $livewire) {
                        $record->update(['quotation_id' => null]);
                        $livewire->getOwnerRecord()->recalculate();
                        
                        Notification::make()
                            ->title('Rental removed')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                //
            ]);
    }
}

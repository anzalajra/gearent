<?php

namespace App\Filament\Resources\Deliveries\Tables;

use App\Filament\Resources\Deliveries\DeliveryResource;
use App\Models\Delivery;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DeliveriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('delivery_number')
                    ->label('Delivery Number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('rental.rental_code')
                    ->label('Rental')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('rental.customer.name')
                    ->label('Customer')
                    ->searchable(),

                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => Delivery::getTypeColor($state))
                    ->formatStateUsing(fn (string $state): string => $state === 'out' ? 'Keluar' : 'Masuk'),

                TextColumn::make('date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => Delivery::getStatusColor($state))
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('checkedBy.name')
                    ->label('Checked By'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->options(Delivery::getTypeOptions()),
                SelectFilter::make('status')
                    ->options(Delivery::getStatusOptions()),
            ])
            ->recordActions([
                Action::make('process')
                    ->label('Process')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('warning')
                    ->url(fn (Delivery $record) => DeliveryResource::getUrl('process', ['record' => $record]))
                    ->visible(fn (Delivery $record) => $record->status === Delivery::STATUS_DRAFT),

                Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->action(function (Delivery $record) {
                        $record->load(['rental.customer', 'rental.items.productUnit.product', 'items.rentalItem.productUnit', 'items.rentalItemKit.unitKit', 'checkedBy']);
                        
                        $pdf = Pdf::loadView('pdf.delivery-note', ['delivery' => $record]);
                        
                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            $record->delivery_number . '.pdf'
                        );
                    }),

                EditAction::make()
                    ->visible(fn (Delivery $record) => $record->status === Delivery::STATUS_DRAFT),

                DeleteAction::make()
                    ->visible(fn (Delivery $record) => $record->status === Delivery::STATUS_DRAFT),
            ])
            ->recordUrl(fn (Delivery $record) => DeliveryResource::getUrl('process', ['record' => $record]));
    }
}
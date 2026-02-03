<?php

namespace App\Filament\Resources\Rentals\Pages;

use App\Filament\Resources\Rentals\RentalResource;
use App\Models\Delivery;
use App\Models\Rental;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Illuminate\Contracts\Support\Htmlable;

class RentalDocuments extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = RentalResource::class;

    public ?Rental $rental = null;

    public function getView(): string
    {
        return 'filament.resources.rentals.pages.rental-documents';
    }

    public function mount(int|string $record): void
    {
        $this->rental = Rental::with(['customer', 'deliveries'])->findOrFail($record);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Rental Documents - ' . $this->rental->rental_code;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->rental->deliveries()->getQuery())
            ->columns([
                TextColumn::make('delivery_number')
                    ->label('Delivery Number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => Delivery::getTypeColor($state))
                    ->formatStateUsing(fn (string $state): string => $state === 'out' ? 'Keluar (Check-out)' : 'Masuk (Check-in)'),

                TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => Delivery::getStatusColor($state))
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
            ])
            ->actions([
                Action::make('process')
                    ->label(fn (Delivery $record) => $record->type === 'out' ? 'Process Pickup' : 'Process Return')
                    ->icon(fn (Delivery $record) => $record->type === 'out' ? 'heroicon-o-truck' : 'heroicon-o-arrow-uturn-left')
                    ->color(fn (Delivery $record) => $record->type === 'out' ? 'warning' : 'success')
                    ->url(fn (Delivery $record) => $record->type === 'out' 
                        ? RentalResource::getUrl('pickup', ['record' => $this->rental])
                        : RentalResource::getUrl('return', ['record' => $this->rental])
                    )
                    ->visible(fn (Delivery $record) => $record->status !== Delivery::STATUS_CANCELLED),
                
                Action::make('view_delivery')
                    ->label('View Surat Jalan')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (Delivery $record) => \App\Filament\Resources\Deliveries\DeliveryResource::getUrl('process', ['record' => $record])),
            ])
            ->paginated(false);
    }
}

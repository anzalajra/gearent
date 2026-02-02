<?php

namespace App\Filament\Resources\Rentals\Pages;

use App\Filament\Resources\Rentals\RentalResource;
use App\Models\Rental;
use App\Models\RentalItemKit;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class PickupOperation extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = RentalResource::class;

    public Rental $record;

    public function getView(): string
    {
        return 'filament.resources.rentals.pages.pickup-operation';
    }

    public function mount(int|string $record): void
    {
        $this->record = Rental::with(['customer', 'items.productUnit.product', 'items.productUnit.kits'])->findOrFail($record);
        
        // Check if rental can be picked up
        if (!in_array($this->record->getRealTimeStatus(), [Rental::STATUS_PENDING, Rental::STATUS_LATE_PICKUP])) {
            Notification::make()
                ->title('Cannot pickup this rental')
                ->body('This rental is not in pending or late pickup status.')
                ->danger()
                ->send();

            $this->redirect(RentalResource::getUrl('index'));
        }
    }

    public function getTitle(): string|Htmlable
    {
        return 'Pickup Operation - ' . $this->record->rental_code;
    }

    public function getAvailabilityStatus(): array
    {
        $conflicts = $this->record->checkAvailability();
        
        return [
            'available' => empty($conflicts),
            'conflicts' => $conflicts,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->record->items()->getQuery())
            ->columns([
                TextColumn::make('productUnit.product.name')
                    ->label('Product')
                    ->searchable(),

                TextColumn::make('productUnit.serial_number')
                    ->label('Serial Number')
                    ->searchable(),

                TextColumn::make('kits_count')
                    ->label('Kits')
                    ->getStateUsing(fn ($record) => $record->productUnit->kits->count() . ' kits')
                    ->badge()
                    ->color('info'),

                TextColumn::make('days')
                    ->label('Days'),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('kit_check')
                    ->label('Kit Check')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('warning')
                    ->modalHeading(fn ($record) => 'Kit Check - ' . $record->productUnit->serial_number)
                    ->modalWidth('2xl')
                    ->form(function ($record) {
                        $kits = $record->productUnit->kits;
                        
                        if ($kits->isEmpty()) {
                            return [
                                Placeholder::make('no_kits')
                                    ->label('')
                                    ->content('No kits available for this unit.'),
                            ];
                        }

                        return [
                            Repeater::make('kits')
                                ->label('Kit Items')
                                ->schema([
                                    TextInput::make('kit_name')
                                        ->label('Kit Name')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->columnSpan(1),

                                    TextInput::make('serial_number')
                                        ->label('Serial Number')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->columnSpan(1),

                                    Select::make('condition_out')
                                        ->label('Condition Out')
                                        ->options(RentalItemKit::getConditionOptions())
                                        ->required()
                                        ->columnSpan(1),

                                    Textarea::make('notes')
                                        ->label('Notes')
                                        ->rows(1)
                                        ->placeholder('Optional notes')
                                        ->columnSpan(1),
                                ])
                                ->columns(4)
                                ->default(function () use ($kits) {
                                    return $kits->map(function ($kit) {
                                        return [
                                            'kit_id' => $kit->id,
                                            'kit_name' => $kit->name,
                                            'serial_number' => $kit->serial_number ?? '-',
                                            'condition_out' => $kit->condition,
                                            'notes' => '',
                                        ];
                                    })->toArray();
                                })
                                ->addable(false)
                                ->deletable(false)
                                ->reorderable(false),
                        ];
                    })
                    ->action(function ($record, array $data) {
                        // Save kit conditions for pickup
                        foreach ($data['kits'] ?? [] as $index => $kitData) {
                            $kit = $record->productUnit->kits[$index] ?? null;
                            if ($kit) {
                                $record->rentalItemKits()->updateOrCreate(
                                    ['unit_kit_id' => $kit->id],
                                    [
                                        'condition_out' => $kitData['condition_out'],
                                        'notes' => $kitData['notes'],
                                    ]
                                );
                            }
                        }

                        Notification::make()
                            ->title('Kit conditions saved')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->productUnit->kits->count() > 0),
            ])
            ->headerActions([])
            ->paginated(false);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit_rental')
                ->label('Edit Rental')
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->url(fn () => RentalResource::getUrl('edit', ['record' => $this->record])),

            Action::make('validate_pickup')
                ->label('Validate Pickup')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->size('lg')
                ->requiresConfirmation()
                ->modalHeading('Confirm Pickup')
                ->modalDescription('Are you sure the customer has picked up all items? This will change the rental status to Active.')
                ->modalSubmitActionLabel('Yes, Confirm Pickup')
                ->action(function () {
                    $this->record->validatePickup();

                    Notification::make()
                        ->title('Pickup validated successfully')
                        ->body('Rental status changed to Active.')
                        ->success()
                        ->send();

                    $this->redirect(RentalResource::getUrl('index'));
                }),
        ];
    }

    public function getFooterActions(): array
    {
        return [
            Action::make('validate_pickup_bottom')
                ->label('Validate Pickup')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->size('lg')
                ->requiresConfirmation()
                ->modalHeading('Confirm Pickup')
                ->modalDescription('Are you sure the customer has picked up all items? This will change the rental status to Active.')
                ->modalSubmitActionLabel('Yes, Confirm Pickup')
                ->action(function () {
                    $this->record->validatePickup();

                    Notification::make()
                        ->title('Pickup validated successfully')
                        ->body('Rental status changed to Active.')
                        ->success()
                        ->send();

                    $this->redirect(RentalResource::getUrl('index'));
                }),
        ];
    }
}
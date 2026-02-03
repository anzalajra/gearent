<?php

namespace App\Filament\Resources\Rentals\Pages;

use App\Filament\Resources\Rentals\RentalResource;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\Rental;
use App\Models\RentalItemKit;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class ProcessReturn extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = RentalResource::class;

    public ?Rental $rental = null;
    public ?Delivery $delivery = null;

    public function getView(): string
    {
        return 'filament.resources.rentals.pages.return-operation';
    }

    public function mount(int|string $record): void
    {
        $this->rental = Rental::with([
            'customer', 
            'items.productUnit.product', 
            'items.rentalItemKits.unitKit'
        ])->findOrFail($record);

        // Update late status on mount
        $this->rental->checkAndUpdateLateStatus();
        $this->rental->refresh();

        // Always sync deliveries to ensure all kits are present
        $this->rental->createDeliveries();

        // Get delivery in
        $this->delivery = $this->rental->deliveries()
            ->where('type', Delivery::TYPE_IN)
            ->first();

        if (!in_array($this->rental->status, [Rental::STATUS_ACTIVE, Rental::STATUS_LATE_RETURN])) {
            Notification::make()
                ->title('Cannot return this rental')
                ->body('This rental is not in active or late return status.')
                ->danger()
                ->send();

            $this->redirect(RentalResource::getUrl('index'));
        }
    }

    public function getTitle(): string|Htmlable
    {
        return 'Return Operation - ' . $this->rental->rental_code;
    }

    public function allItemsChecked(): bool
    {
        return $this->delivery->items()->where('is_checked', false)->count() === 0;
    }

    public function canValidateReturn(): bool
    {
        return $this->allItemsChecked();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->delivery->items()->getQuery())
            ->columns([
                TextColumn::make('item_name')
                    ->label('Item')
                    ->getStateUsing(function (DeliveryItem $record) {
                        if ($record->rentalItemKit) {
                            return '↳ ' . $record->rentalItemKit->unitKit->name;
                        }
                        return $record->rentalItem->productUnit->product->name;
                    }),

                TextColumn::make('serial_number')
                    ->label('Serial Number')
                    ->getStateUsing(function (DeliveryItem $record) {
                        if ($record->rentalItemKit) {
                            return $record->rentalItemKit->unitKit->serial_number ?? '-';
                        }
                        return $record->rentalItem->productUnit->serial_number;
                    }),

                TextColumn::make('type')
                    ->label('Type')
                    ->getStateUsing(function (DeliveryItem $record) {
                        return $record->rentalItemKit ? 'Kit' : 'Unit';
                    })
                    ->badge()
                    ->color(fn (string $state) => $state === 'Unit' ? 'primary' : 'gray'),

                TextColumn::make('condition')
                    ->label('Condition')
                    ->badge()
                    ->color(fn (?string $state) => $state ? DeliveryItem::getConditionColor($state) : 'gray')
                    ->formatStateUsing(fn (?string $state) => $state ? ucfirst($state) : '-'),

                IconColumn::make('is_checked')
                    ->label('Checked')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('check_item')
                    ->label(fn (DeliveryItem $record) => $record->is_checked ? 'Edit' : 'Check')
                    ->icon(fn (DeliveryItem $record) => $record->is_checked ? 'heroicon-o-pencil' : 'heroicon-o-check')
                    ->color(fn (DeliveryItem $record) => $record->is_checked ? 'gray' : 'warning')
                    ->modalHeading('Check Item')
                    ->modalWidth('md')
                    ->fillForm(function (DeliveryItem $record): array {
                        return [
                            'item_name' => $record->rentalItemKit 
                                ? $record->rentalItemKit->unitKit->name 
                                : $record->rentalItem->productUnit->product->name,
                            'condition' => $record->condition,
                            'is_checked' => $record->is_checked,
                            'notes' => $record->notes,
                        ];
                    })
                    ->form(function (DeliveryItem $record) {
                        return [
                            TextInput::make('item_name')
                                ->label('Item')
                                ->disabled()
                                ->dehydrated(false),

                            Select::make('condition')
                                ->label('Condition')
                                ->options(DeliveryItem::getConditionInOptions())
                                ->required(),

                            Checkbox::make('is_checked')
                                ->label('Mark as Checked'),

                            Textarea::make('notes')
                                ->label('Notes')
                                ->rows(2),
                        ];
                    })
                    ->action(function (DeliveryItem $record, array $data) {
                        $record->update([
                            'condition' => $data['condition'],
                            'is_checked' => $data['is_checked'],
                            'notes' => $data['notes'],
                        ]);

                        // Sync back to RentalItemKit if it's a kit
                        if ($record->rentalItemKit) {
                            $record->rentalItemKit->update([
                                'condition_in' => $data['condition'],
                                'is_returned' => $data['is_checked'],
                            ]);
                        }

                        $this->delivery->refresh();

                        Notification::make()
                            ->title('Item updated')
                            ->success()
                            ->send();
                    }),
            ])
            ->headerActions([])
            ->paginated(false);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action(function () {
                    $this->delivery->load(['rental.customer', 'items.rentalItem.productUnit.product', 'items.rentalItemKit.unitKit', 'checkedBy']);
                    
                    $pdf = Pdf::loadView('pdf.delivery-note', ['delivery' => $this->delivery]);
                    
                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        $this->delivery->delivery_number . '.pdf'
                    );
                }),

            Action::make('rental_documents')
                ->label('Delivery Documents')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->url(fn () => RentalResource::getUrl('documents', ['record' => $this->rental])),

            $this->getValidateReturnAction(),
        ];
    }

    public function getValidateReturnAction(): Action
    {
        return Action::make('validate_return')
            ->label('Validate Return')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->size('lg')
            ->requiresConfirmation()
            ->modalHeading('Confirm Return')
            ->modalDescription('Are you sure all items have been returned? This will change the rental status to Completed.')
            ->modalSubmitActionLabel('Yes, Confirm Return')
            ->disabled(fn () => !$this->allItemsChecked())
            ->action(function () {
                $this->rental->validateReturn();

                // Also complete the delivery
                $this->delivery->complete();

                Notification::make()
                    ->title('Return validated successfully')
                    ->body('Rental status changed to Completed.')
                    ->success()
                    ->send();

                $this->redirect(RentalResource::getUrl('index'));
            });
    }

    public function validateReturnAction(): Action
    {
        return $this->getValidateReturnAction();
    }
}
<?php

namespace App\Filament\Resources\Deliveries\Pages;

use App\Filament\Resources\Deliveries\DeliveryResource;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Hidden;
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

class ProcessDelivery extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = DeliveryResource::class;

    public ?Delivery $delivery = null;

    public function getView(): string
    {
        return 'filament.resources.deliveries.pages.process-delivery';
    }

    public function mount(int|string $record): void
    {
        $this->delivery = Delivery::with([
            'rental.customer',
            'rental.items.productUnit.product',
            'rental.items.rentalItemKits.unitKit',
            'items.rentalItem.productUnit.product',
            'items.rentalItemKit.unitKit',
            'checkedBy',
        ])->findOrFail($record);
    }

    public function getTitle(): string|Htmlable
    {
        $type = $this->delivery->type === 'out' ? 'Check-out' : 'Check-in';
        return $type . ' - ' . $this->delivery->delivery_number;
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
                    ->modalHeading(fn (DeliveryItem $record) => 'Check Item')
                    ->modalWidth('md')
                    ->form(function (DeliveryItem $record) {
                        $conditionOptions = $this->delivery->type === 'out' 
                            ? DeliveryItem::getConditionOptions() 
                            : DeliveryItem::getConditionInOptions();

                        return [
                            TextInput::make('item_name')
                                ->label('Item')
                                ->default(function () use ($record) {
                                    if ($record->rentalItemKit) {
                                        return $record->rentalItemKit->unitKit->name;
                                    }
                                    return $record->rentalItem->productUnit->product->name;
                                })
                                ->disabled()
                                ->dehydrated(false),

                            Select::make('condition')
                                ->label('Condition')
                                ->options($conditionOptions)
                                ->required()
                                ->default($record->condition),

                            Checkbox::make('is_checked')
                                ->label('Mark as Checked')
                                ->default($record->is_checked),

                            Textarea::make('notes')
                                ->label('Notes')
                                ->rows(2)
                                ->default($record->notes),
                        ];
                    })
                    ->action(function (DeliveryItem $record, array $data) {
                        $record->update([
                            'condition' => $data['condition'],
                            'is_checked' => $data['is_checked'],
                            'notes' => $data['notes'],
                        ]);

                        // Jika check-out, update condition_out di rental_item_kits
                        if ($this->delivery->type === 'out' && $record->rentalItemKit) {
                            $record->rentalItemKit->update(['condition_out' => $data['condition']]);
                        }

                        // Jika check-in, update condition_in dan is_returned di rental_item_kits
                        if ($this->delivery->type === 'in' && $record->rentalItemKit) {
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
                    })
                    ->visible(fn () => $this->delivery->status === Delivery::STATUS_DRAFT),
            ])
            ->headerActions([])
            ->paginated(false);
    }

    public function allItemsChecked(): bool
    {
        return $this->delivery->items()->where('is_checked', false)->count() === 0;
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

            $this->getCompleteAction(),
        ];
    }

    public function getCompleteAction(): Action
    {
        return Action::make('complete')
            ->label('Complete Delivery')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->size('lg')
            ->requiresConfirmation()
            ->modalHeading('Complete Delivery')
            ->modalDescription('Are you sure all items have been checked? This will mark the delivery as completed.')
            ->modalSubmitActionLabel('Yes, Complete')
            ->disabled(fn () => !$this->allItemsChecked() || $this->delivery->status === Delivery::STATUS_COMPLETED)
            ->action(function () {
                $this->delivery->complete();

                // Update rental status based on delivery type
                if ($this->delivery->type === 'out') {
                    $this->delivery->rental->update(['status' => 'active']);
                } elseif ($this->delivery->type === 'in') {
                    $this->delivery->rental->validateReturn();
                }

                Notification::make()
                    ->title('Delivery completed')
                    ->success()
                    ->send();

                $this->redirect(DeliveryResource::getUrl('index'));
            });
    }

    public function completeAction(): Action
    {
        return $this->getCompleteAction();
    }
}
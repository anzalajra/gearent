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
            'items.rentalItemKits.unitKit',
            'deliveries.items.rentalItem.productUnit.product',
            'deliveries.items.rentalItemKit.unitKit',
        ])->findOrFail($record);

        // Update late status on mount
        $this->rental->checkAndUpdateLateStatus();
        $this->rental->refresh();

        // Always sync deliveries to ensure all kits are present
        $this->rental->createDeliveries();

        // Get the active delivery (not completed) or the latest one
        $this->delivery = $this->rental->deliveries()
            ->with(['items.rentalItem.productUnit.product', 'items.rentalItemKit.unitKit'])
            ->where('type', Delivery::TYPE_IN)
            ->where('status', '!=', Delivery::STATUS_COMPLETED)
            ->first();

        // If no active delivery found, fallback to the latest one (even if completed)
        if (!$this->delivery) {
            $this->delivery = $this->rental->deliveries()
                ->with(['items.rentalItem.productUnit.product', 'items.rentalItemKit.unitKit'])
                ->where('type', Delivery::TYPE_IN)
                ->latest()
                ->first();
        }

        if (!in_array($this->rental->status, [Rental::STATUS_ACTIVE, Rental::STATUS_LATE_RETURN, Rental::STATUS_PARTIAL_RETURN])) {
            Notification::make()
                ->title('Cannot return this rental')
                ->body('This rental is not in active, partial return, or late return status.')
                ->danger()
                ->send();

            $this->redirect(RentalResource::getUrl('index'));
        }
    }

    public function getTitle(): string|Htmlable
    {
        return 'Return Operation - ' . $this->rental->rental_code;
    }



    public function getMarkAllCheckedAction(): Action
    {
        return Action::make('markAllChecked')
            ->label('Mark All as Checked')
            ->icon('heroicon-o-check-circle')
            ->color('warning')
            ->steps([
                \Filament\Schemas\Components\Wizard\Step::make('Verification')
                    ->description('Please verify that all tools have been checked properly and carefully.')
                    ->schema([
                        \Filament\Schemas\Components\Text::make('I confirm that I have physically checked all items and they are present.'),
                    ]),
                \Filament\Schemas\Components\Wizard\Step::make('Final Confirmation')
                    ->description('This will mark all items as checked.')
                    ->schema([
                        \Filament\Schemas\Components\Text::make('All items and kits will be marked as checked. You can still change the condition per item. Are you sure?'),
                    ]),
            ])
            ->action(function () {
                $items = $this->delivery->items;
                foreach ($items as $record) {
                    // Determine condition
                    $condition = $record->condition;
                    if (! $condition) {
                        if ($record->rentalItemKit) {
                            $condition = $record->rentalItemKit->unitKit->condition ?? 'good';
                        } else {
                            $condition = $record->rentalItem->productUnit->condition ?? 'good';
                        }
                    }
                    
                    $record->update([
                        'is_checked' => true,
                        'condition' => $condition,
                    ]);

                    // Logic from check_item action
                    $isMaintenance = in_array($condition, ['broken', 'lost']);
                    $updates = ['condition' => $condition];
                    
                    if ($isMaintenance) {
                         $updates['notes'] = ($record->rentalItemKit ? $record->rentalItemKit->unitKit->notes : $record->rentalItem->productUnit->notes) . "\n[AUTO] Marked as {$condition} during Return.";
                        
                        if (!$record->rentalItemKit) {
                            $updates['status'] = \App\Models\ProductUnit::STATUS_MAINTENANCE;
                        }
                    }

                    // Sync back to RentalItemKit if it's a kit
                    if ($record->rentalItemKit) {
                        $record->rentalItemKit->update([
                            'condition_in' => $condition,
                            'is_returned' => true,
                        ]);
                        // Update Unit Kit Master
                        $record->rentalItemKit->unitKit->update($updates);
                    } else {
                        // Update Main Unit Master
                        $record->rentalItem->productUnit->update($updates);
                    }
                }

                $this->delivery->refresh();
                
                Notification::make()
                    ->title('All items marked as checked')
                    ->success()
                    ->send();
            });
    }


    public function allItemsChecked(): bool
    {
        return $this->delivery->items->where('is_checked', false)->count() === 0;
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
                        $productName = $record->rentalItem->productUnit->product->name;
                        $variationName = $record->rentalItem->productUnit->variation->name ?? null;
                        return $productName . ($variationName ? ' (' . $variationName . ')' : '');
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
                        $currentCondition = $record->condition;

                        if (! $currentCondition) {
                            if ($record->rentalItemKit) {
                                $currentCondition = $record->rentalItemKit->unitKit->condition ?? null;
                            } else {
                                $currentCondition = $record->rentalItem->productUnit->condition ?? null;
                            }
                        }

                        return [
                            'item_name' => $record->rentalItemKit 
                                ? $record->rentalItemKit->unitKit->name 
                                : $record->rentalItem->productUnit->product->name,
                            'condition' => $currentCondition,
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

                        // SYNC CONDITION TO MASTER DATA
                        $newCondition = $data['condition'];
                        $isMaintenance = in_array($newCondition, ['broken', 'lost']);
                        $updates = ['condition' => $newCondition];
                        
                        if ($isMaintenance) {
                            // Add note about auto maintenance
                            $updates['notes'] = ($record->rentalItemKit ? $record->rentalItemKit->unitKit->notes : $record->rentalItem->productUnit->notes) . "\n[AUTO] Marked as {$newCondition} during Return.";
                            
                            // Only update status for Main Unit, as Kit doesn't have status field
                            if (!$record->rentalItemKit) {
                                $updates['status'] = \App\Models\ProductUnit::STATUS_MAINTENANCE;
                            }
                        }

                        // Sync back to RentalItemKit if it's a kit
                        if ($record->rentalItemKit) {
                            $record->rentalItemKit->update([
                                'condition_in' => $data['condition'],
                                'is_returned' => $data['is_checked'],
                            ]);
                            // Update Unit Kit Master
                            $record->rentalItemKit->unitKit->update($updates);
                        } else {
                            // Update Main Unit Master
                            $record->rentalItem->productUnit->update($updates);
                        }

                        $this->delivery->refresh();

                        Notification::make()
                            ->title('Item updated')
                            ->success()
                            ->send();
                    }),
            ])
            ->headerActions([
                $this->getMarkAllCheckedAction(),
            ])
            ->paginated(false);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\ActionGroup::make([
                Action::make('send_whatsapp_return')
                    ->label('Return Reminder (WhatsApp)')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->visible(fn () => \App\Models\Setting::get('whatsapp_enabled', true))
                    ->disabled(fn () => empty($this->rental->customer->phone))
                    ->tooltip(fn () => empty($this->rental->customer->phone) ? 'Customer phone number is missing' : null)
                    ->url(function () {
                        $rental = $this->rental;
                        $customer = $rental->customer;
                        
                        if (empty($customer->phone)) {
                            return '#';
                        }
                        
                        $pdfLink = \Illuminate\Support\Facades\URL::signedRoute('public-documents.rental.checklist', ['rental' => $rental]);
                        
                        $data = [
                            'customer_name' => $customer->name,
                            'rental_ref' => $rental->rental_code,
                            'return_date' => \Carbon\Carbon::parse($rental->end_date)->format('d M Y H:i'),
                            'link_pdf' => $pdfLink,
                            'company_name' => \App\Models\Setting::get('site_name', 'Gearent'),
                        ];
                        
                        $message = \App\Helpers\WhatsAppHelper::parseTemplate('whatsapp_template_rental_return', $data);
                        
                        return \App\Helpers\WhatsAppHelper::getLink($customer->phone, $message);
                    })
                    ->openUrlInNewTab(),
                
                Action::make('send_email_return')
                    ->label('Return Reminder (Email)')
                    ->icon('heroicon-o-envelope')
                    ->color('gray')
                    ->disabled()
                    ->tooltip('Coming Soon'),
            ])
            ->label('Send')
            ->icon('heroicon-o-paper-airplane')
            ->color('info')
            ->button(),

            \Filament\Actions\ActionGroup::make([
                Action::make('download_checklist')
                    ->label('Download Checklist Form')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->action(function () {
                        $this->rental->load(['customer', 'items.productUnit.product', 'items.rentalItemKits.unitKit']);
                        
                        $pdf = Pdf::loadView('pdf.checklist-form', ['rental' => $this->rental]);
                        
                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            'Checklist-' . $this->rental->rental_code . '.pdf'
                        );
                    }),

                Action::make('download_delivery_note')
                    ->label('Download Delivery Note')
                    ->icon('heroicon-o-truck')
                    ->action(function () {
                        $this->delivery->load(['rental.customer', 'items.rentalItem.productUnit.product', 'items.rentalItemKit.unitKit', 'checkedBy']);
                        
                        $pdf = Pdf::loadView('pdf.delivery-note', ['delivery' => $this->delivery]);
                        
                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            $this->delivery->delivery_number . '.pdf'
                        );
                    }),
            ])
            ->label('Print')
            ->icon('heroicon-o-printer')
            ->color('info')
            ->button(),

            Action::make('rental_documents')
                ->label('Delivery')
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
            ->modalHeading(function () {
                if ($this->allItemsChecked()) {
                    return 'Confirm Return';
                }
                return 'Partial Return Confirmation';
            })
            ->modalDescription(function () {
                if ($this->allItemsChecked()) {
                    return 'Are you sure all items have been returned? This will change the rental status to Completed.';
                }
                return 'Not all items are checked. Do you want to proceed with a Partial Return? This will mark currently checked items as returned and create a new Pending Return for the remaining items. The rental status will be updated to Partial Return.';
            })
            ->modalSubmitActionLabel(function () {
                if ($this->allItemsChecked()) {
                    return 'Yes, Confirm Return';
                }
                return 'Yes, Process Partial Return';
            })
            ->action(function () {
                if ($this->allItemsChecked()) {
                    // FULL RETURN
                    $this->delivery->complete();
                    $this->rental->validateReturn();

                    Notification::make()
                        ->title('Return validated successfully')
                        ->body('Rental status changed to Completed.')
                        ->success()
                        ->send();

                    $this->redirect(RentalResource::getUrl('index'));
                } else {
                    // PARTIAL RETURN
                    // 1. Create new Delivery for unchecked items (remaining items)
                    $newDelivery = Delivery::create([
                        'rental_id' => $this->rental->id,
                        'type' => Delivery::TYPE_IN,
                        'date' => now(),
                        'status' => Delivery::STATUS_DRAFT,
                    ]);

                    // 2. Move unchecked items to new delivery
                    $uncheckedItems = $this->delivery->items()->where('is_checked', false)->get();

                    foreach ($uncheckedItems as $item) {
                        $item->update([
                            'delivery_id' => $newDelivery->id,
                        ]);
                    }

                    // 3. Complete the current delivery (now containing only checked items)
                    $this->delivery->complete();
                    
                    // Update status of returned units
                    foreach ($this->delivery->items as $item) {
                        // Only process main units for status updates (kits don't affect main unit status directly here)
                        if ($item->rental_item_kit_id) {
                            continue;
                        }

                        if ($item->rentalItem && $item->rentalItem->productUnit) {
                            // If broken/lost, set to maintenance
                            if (in_array($item->condition, ['broken', 'lost'])) {
                                $item->rentalItem->productUnit->update(['status' => \App\Models\ProductUnit::STATUS_MAINTENANCE]);
                            } else {
                                // Otherwise refresh status (it will now be seen as returned)
                                $item->rentalItem->productUnit->refreshStatus();
                            }
                        }
                    }
                    
                    // 4. Update rental status to Partial Return
                    // Fetch fresh instance to ensure no stale state overrides the update
                    $freshRental = $this->rental->fresh();
                    $freshRental->update([
                        'status' => Rental::STATUS_PARTIAL_RETURN
                    ]);
                    
                    // Refresh current instance to reflect changes
                    $this->rental->refresh();
                    
                    $finalStatus = $this->rental->status;

                    Notification::make()
                        ->title('Partial Return Processed')
                        ->body("Checked items returned. Remaining items moved to a new return checklist. Rental status updated to: $finalStatus")
                        ->warning()
                        ->send();

                    // Reload page to show the new delivery (which is now the active one)
                    $this->redirect(request()->header('Referer'));
                }
            });
    }

    public function validateReturnAction(): Action
    {
        return $this->getValidateReturnAction();
    }
}
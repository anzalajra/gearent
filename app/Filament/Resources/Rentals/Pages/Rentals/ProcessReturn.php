<?php

namespace App\Filament\Resources\Rentals\Pages;

use App\Filament\Resources\Rentals\RentalResource;
use App\Models\Rental;
use App\Models\RentalItemKit;
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

    public function getView(): string
    {
        return 'filament.resources.rentals.pages.return-operation';
    }

    public function mount(int|string $record): void
    {
        $this->rental = Rental::with(['customer', 'items.productUnit.product', 'items.rentalItemKits.unitKit'])->findOrFail($record);

        // Update late status on mount
        $this->rental->checkAndUpdateLateStatus();
        $this->rental->refresh();

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

    public function isItemReturnChecked($item): bool
    {
        if ($item->rentalItemKits->isEmpty()) {
            return true;
        }

        foreach ($item->rentalItemKits as $kit) {
            if (!$kit->is_returned || !$kit->condition_in) {
                return false;
            }
        }
        return true;
    }

    public function allItemsReturnChecked(): bool
    {
        foreach ($this->rental->items as $item) {
            if (!$this->isItemReturnChecked($item)) {
                return false;
            }
        }
        return true;
    }

    public function canValidateReturn(): bool
    {
        return $this->allItemsReturnChecked();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->rental->items()->getQuery())
            ->columns([
                TextColumn::make('productUnit.product.name')
                    ->label('Product')
                    ->searchable(),

                TextColumn::make('productUnit.serial_number')
                    ->label('Serial Number')
                    ->searchable(),

                TextColumn::make('kits_status')
                    ->label('Kits')
                    ->getStateUsing(fn ($record) => $record->getKitsStatusText())
                    ->badge()
                    ->color(fn ($record) => $record->allKitsReturned() ? 'success' : 'warning'),

                TextColumn::make('days')
                    ->label('Days'),

                IconColumn::make('all_returned')
                    ->label('Returned')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->allKitsReturned())
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('kit_check')
                    ->label(fn ($record) => $this->isItemReturnChecked($record) ? 'Kit Checked' : 'Kit Check')
                    ->icon(fn ($record) => $this->isItemReturnChecked($record) ? 'heroicon-o-check-circle' : 'heroicon-o-exclamation-circle')
                    ->color(fn ($record) => $this->isItemReturnChecked($record) ? 'success' : 'warning')
                    ->modalHeading(fn ($record) => 'Kit Check - ' . $record->productUnit->serial_number)
                    ->modalWidth('2xl')
                    ->form(function ($record) {
                        $rentalItemKits = $record->rentalItemKits;

                        if ($rentalItemKits->isEmpty()) {
                            return [
                                Placeholder::make('no_kits')
                                    ->label('')
                                    ->content('No kits tracked for this rental item.'),
                            ];
                        }

                        return [
                            Repeater::make('kits')
                                ->label('Kit Items')
                                ->schema([
                                    Hidden::make('id'),

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

                                    TextInput::make('condition_out')
                                        ->label('Condition Out')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->columnSpan(1),

                                    Select::make('condition_in')
                                        ->label('Condition In')
                                        ->options(RentalItemKit::getConditionInOptions())
                                        ->required()
                                        ->columnSpan(1),

                                    Checkbox::make('is_returned')
                                        ->label('Returned')
                                        ->default(true)
                                        ->columnSpan(1),

                                    Textarea::make('notes')
                                        ->label('Notes')
                                        ->rows(1)
                                        ->placeholder('Notes if damaged/lost')
                                        ->columnSpan(1),
                                ])
                                ->columns(6)
                                ->itemLabel(fn (array $state): ?string => $state['kit_name'] ?? null)
                                ->default(function () use ($rentalItemKits) {
                                    return $rentalItemKits->map(function ($rentalItemKit) {
                                        return [
                                            'id' => $rentalItemKit->id,
                                            'kit_name' => $rentalItemKit->unitKit->name,
                                            'serial_number' => $rentalItemKit->unitKit->serial_number ?? '-',
                                            'condition_out' => ucfirst($rentalItemKit->condition_out),
                                            'condition_in' => $rentalItemKit->condition_in ?? $rentalItemKit->condition_out,
                                            'is_returned' => $rentalItemKit->is_returned ?? true,
                                            'notes' => $rentalItemKit->notes,
                                        ];
                                    })->toArray();
                                })
                                ->addable(false)
                                ->deletable(false)
                                ->reorderable(false),
                        ];
                    })
                    ->action(function ($record, array $data) {
                        foreach ($data['kits'] ?? [] as $kitData) {
                            if (isset($kitData['id']) && $kitData['id']) {
                                RentalItemKit::where('id', $kitData['id'])
                                    ->update([
                                        'condition_in' => $kitData['condition_in'] ?? null,
                                        'is_returned' => $kitData['is_returned'] ?? false,
                                        'notes' => $kitData['notes'] ?? null,
                                    ]);
                            }
                        }

                        $this->rental->load(['items.rentalItemKits.unitKit']);

                        Notification::make()
                            ->title('Kit return status updated')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->rentalItemKits->count() > 0),
            ])
            ->headerActions([])
            ->paginated(false);
    }

    protected function getHeaderActions(): array
    {
        return [
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
            ->disabled(fn () => !$this->canValidateReturn())
            ->action(function () {
                $this->rental->validateReturn();

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
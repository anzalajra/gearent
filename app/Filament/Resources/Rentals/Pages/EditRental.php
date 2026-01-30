<?php

namespace App\Filament\Resources\Rentals\Pages;

use App\Filament\Resources\Rentals\RentalResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditRental extends EditRecord
{
    protected static string $resource = RentalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('calculate')
                ->label('Calculate Total')
                ->icon('heroicon-m-calculator')
                ->color('info')
                ->action(function () {
                    $items = $this->data['items'] ?? [];
                    $subtotal = 0;

                    foreach ($items as $key => $item) {
                        $dailyRate = (float) ($item['daily_rate'] ?? 0);
                        $days = (int) ($item['days'] ?? 1);
                        $itemSubtotal = $dailyRate * $days;
                        
                        $this->data['items'][$key]['subtotal'] = $itemSubtotal;
                        $subtotal += $itemSubtotal;
                    }

                    $discount = (float) ($this->data['discount'] ?? 0);
                    $total = $subtotal - $discount;

                    $this->data['subtotal'] = $subtotal;
                    $this->data['total'] = $total;

                    Notification::make()
                        ->title('Calculated!')
                        ->body("Subtotal: Rp " . number_format($subtotal, 0, ',', '.') . " | Total: Rp " . number_format($total, 0, ',', '.'))
                        ->success()
                        ->send();
                }),
            
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function afterSave(): void
    {
        $this->record->refresh();
        
        $subtotal = $this->record->items()->sum('subtotal');
        $total = $subtotal - ($this->record->discount ?? 0);

        $this->record->update([
            'subtotal' => $subtotal,
            'total' => $total,
        ]);
    }
}
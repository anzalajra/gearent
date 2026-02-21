<?php

namespace App\Filament\Resources\Rentals\Pages;

use App\Filament\Resources\Rentals\RentalResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateRental extends CreateRecord
{
    protected static string $resource = RentalResource::class;

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
                    $subtotalAfterDiscount = max(0, $subtotal - $discount);
                    
                    // Tax Calculation
                    $taxAmount = 0;
                    $taxRate = 0;
                    if (\App\Models\Setting::get('tax_enabled', false)) {
                        $taxRate = (float) \App\Models\Setting::get('tax_rate', 11);
                        $taxAmount = $subtotalAfterDiscount * ($taxRate / 100);
                    }

                    $total = $subtotalAfterDiscount + $taxAmount;

                    $this->data['subtotal'] = $subtotal;
                    $this->data['ppn_rate'] = $taxRate;
                    $this->data['ppn_amount'] = $taxAmount;
                    $this->data['total'] = $total;

                    Notification::make()
                        ->title('Calculated!')
                        ->body("Subtotal: Rp " . number_format($subtotal, 0, ',', '.') . " | Tax: Rp " . number_format($taxAmount, 0, ',', '.') . " | Total: Rp " . number_format($total, 0, ',', '.'))
                        ->success()
                        ->send();
                }),
            
            $this->getCreateFormAction(),
            $this->getCreateAnotherFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function afterCreate(): void
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
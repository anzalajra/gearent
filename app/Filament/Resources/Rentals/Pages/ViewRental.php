<?php

namespace App\Filament\Resources\Rentals\Pages;

use App\Filament\Resources\Rentals\RentalResource;
use App\Models\Rental;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewRental extends ViewRecord
{
    protected static string $resource = RentalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => $this->record->canBeEdited()),

            DeleteAction::make()
                ->visible(fn () => $this->record->canBeDeleted()),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Rental Information')
                    ->schema([
                        TextEntry::make('rental_code')
                            ->label('Rental Code'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => Rental::getStatusColor($state))
                            ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                        TextEntry::make('customer.name')
                            ->label('Customer'),
                        TextEntry::make('customer.phone')
                            ->label('Phone')
                            ->placeholder('-'),
                        TextEntry::make('start_date')
                            ->dateTime('d M Y H:i'),
                        TextEntry::make('end_date')
                            ->dateTime('d M Y H:i'),
                        TextEntry::make('returned_date')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('total')
                            ->money('IDR'),
                        TextEntry::make('deposit')
                            ->money('IDR'),
                        TextEntry::make('notes')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('cancel_reason')
                            ->label('Cancel Reason')
                            ->placeholder('-')
                            ->visible(fn ($record) => $record->status === Rental::STATUS_CANCELLED)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Rental Items')
                    ->schema([
                        TextEntry::make('items_table')
                            ->label('')
                            ->html()
                            ->getStateUsing(function ($record) {
                                $html = '<table class="w-full text-sm border-collapse">';
                                $html .= '<thead><tr class="border-b border-gray-200 dark:border-gray-700">';
                                $html .= '<th class="text-left p-2 font-medium">Product</th>';
                                $html .= '<th class="text-left p-2 font-medium">Serial Number</th>';
                                $html .= '<th class="text-left p-2 font-medium">Kits</th>';
                                $html .= '<th class="text-left p-2 font-medium">Days</th>';
                                $html .= '<th class="text-right p-2 font-medium">Subtotal</th>';
                                $html .= '</tr></thead><tbody>';
                                
                                foreach ($record->items as $item) {
                                    $kitsCount = $item->rentalItemKits->count();
                                    $html .= '<tr class="border-b border-gray-100 dark:border-gray-800">';
                                    $html .= '<td class="p-2">' . e($item->productUnit->product->name) . '</td>';
                                    $html .= '<td class="p-2">' . e($item->productUnit->serial_number) . '</td>';
                                    $html .= '<td class="p-2">' . $kitsCount . ' kits</td>';
                                    $html .= '<td class="p-2">' . $item->days . '</td>';
                                    $html .= '<td class="p-2 text-right">Rp ' . number_format($item->subtotal, 0, ',', '.') . '</td>';
                                    $html .= '</tr>';
                                }
                                
                                $html .= '</tbody></table>';
                                return $html;
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
<?php

namespace App\Filament\Resources\Rentals\Pages;

use App\Filament\Resources\Rentals\RentalResource;
use App\Models\Rental;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ViewRental extends Page
{
    protected static string $resource = RentalResource::class;

    protected static bool $canCreateAnother = false;

    public ?Rental $rental = null;

    public function getView(): string
    {
        return 'filament.resources.rentals.pages.view-rental';
    }

    public function mount(int|string $record): void
    {
        $this->rental = Rental::with([
            'customer',
            'items.productUnit.product',
            'items.rentalItemKits.unitKit'
        ])->findOrFail($record);
    }

    public function getTitle(): string|Htmlable
    {
        return 'View Rental - ' . $this->rental->rental_code;
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                // Checklist Form PDF
                Action::make('download_checklist')
                    ->label('Download Checklist Form')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('gray')
                    ->action(function () {
                        $this->rental->load(['customer', 'items.productUnit.product', 'items.rentalItemKits.unitKit']);
                        
                        $pdf = Pdf::loadView('pdf.checklist-form', ['rental' => $this->rental]);
                        
                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            'Checklist-' . $this->rental->rental_code . '.pdf'
                        );
                    }),

                // Quotation PDF
                Action::make('download_quotation')
                    ->label('Download Quotation')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->action(function () {
                        $this->rental->load(['customer', 'items.productUnit.product']);
                        
                        $pdf = Pdf::loadView('pdf.quotation', ['rental' => $this->rental]);
                        
                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            'Quotation-' . $this->rental->rental_code . '.pdf'
                        );
                    }),

                // Invoice PDF
                Action::make('download_invoice')
                    ->label('Download Invoice')
                    ->icon('heroicon-o-document-currency-dollar')
                    ->color('gray')
                    ->action(function () {
                        $this->rental->load(['customer', 'items.productUnit.product']);
                        
                        $pdf = Pdf::loadView('pdf.invoice', ['rental' => $this->rental]);
                        
                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            'Invoice-' . $this->rental->rental_code . '.pdf'
                        );
                    }),
            ])
            ->label('Print')
            ->icon('heroicon-o-printer')
            ->color('gray'),

            EditAction::make()
                ->record($this->rental)
                ->visible(fn () => $this->rental->canBeEdited()),

            Action::make('rental_documents')
                ->label('Delivery')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->url(fn () => RentalResource::getUrl('documents', ['record' => $this->rental])),

            Action::make('pickup')
                ->label('Process Pickup')
                ->icon('heroicon-o-truck')
                ->color('success')
                ->url(fn () => RentalResource::getUrl('pickup', ['record' => $this->rental]))
                ->visible(fn () => in_array($this->rental->status, [Rental::STATUS_PENDING, Rental::STATUS_LATE_PICKUP])),

            Action::make('return')
                ->label('Process Return')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->url(fn () => RentalResource::getUrl('return', ['record' => $this->rental]))
                ->visible(fn () => in_array($this->rental->status, [Rental::STATUS_ACTIVE, Rental::STATUS_LATE_RETURN])),

            Action::make('cancel')
                ->label('Cancel')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancel Rental')
                ->modalDescription('Are you sure you want to cancel this rental?')
                ->form([
                    Textarea::make('cancel_reason')
                        ->label('Reason for cancellation')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->rental->cancelRental($data['cancel_reason']);

                    Notification::make()
                        ->title('Rental cancelled')
                        ->success()
                        ->send();
                    
                    $this->redirect(RentalResource::getUrl('view', ['record' => $this->rental]));
                })
                ->visible(fn () => in_array($this->rental->getRealTimeStatus(), [
                    Rental::STATUS_PENDING,
                    Rental::STATUS_LATE_PICKUP,
                ])),

            DeleteAction::make()
                ->record($this->rental)
                ->visible(fn () => in_array($this->rental->status, [
                    Rental::STATUS_CANCELLED,
                    Rental::STATUS_COMPLETED,
                ]))
                ->successRedirectUrl(RentalResource::getUrl('index')),
        ];
    }
}
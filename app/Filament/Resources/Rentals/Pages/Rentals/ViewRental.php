<?php

namespace App\Filament\Resources\Rentals\Pages;

use App\Filament\Resources\Rentals\RentalResource;
use App\Filament\Resources\Quotations\QuotationResource;
use App\Models\Rental;
use App\Models\Quotation;
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
                Action::make('send_whatsapp')
                    ->label('via WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->visible(fn () => \App\Models\Setting::get('whatsapp_enabled', true))
                    ->url(function () {
                        $rental = $this->rental;
                        $customer = $rental->customer;
                        
                        $itemsList = $rental->items->map(function ($item) {
                             return "- " . $item->productUnit->product->name . " (" . $item->productUnit->unit_code . ")";
                        })->join("\n");
                        
                        $pdfLink = \Illuminate\Support\Facades\URL::signedRoute('public-documents.rental.checklist', ['rental' => $rental]);
                        
                        $data = [
                            'customer_name' => $customer->name,
                            'rental_ref' => $rental->rental_code,
                            'items_list' => $itemsList,
                            'pickup_date' => \Carbon\Carbon::parse($rental->start_date)->format('d M Y H:i'),
                            'return_date' => \Carbon\Carbon::parse($rental->end_date)->format('d M Y H:i'),
                            'link_pdf' => $pdfLink,
                            'company_name' => \App\Models\Setting::get('site_name', 'Gearent'),
                        ];
                        
                        $message = \App\Helpers\WhatsAppHelper::parseTemplate('whatsapp_template_rental_detail', $data);
                        
                        return \App\Helpers\WhatsAppHelper::getLink($customer->phone, $message);
                    })
                    ->openUrlInNewTab(),
                
                Action::make('send_email')
                    ->label('via Email')
                    ->icon('heroicon-o-envelope')
                    ->disabled()
                    ->tooltip('Coming Soon'),
            ])
            ->label('Send')
            ->icon('heroicon-o-paper-airplane')
            ->color('info')
            ->button(),

            ActionGroup::make([
                // Checklist Form PDF
                Action::make('download_checklist')
                    ->label('Download Checklist Form')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('gray')
                    ->action(function () {
                        $this->rental->load(['customer', 'items.productUnit.product', 'items.productUnit.kits', 'items.rentalItemKits.unitKit']);
                        
                        $pdf = Pdf::loadView('pdf.checklist-form', ['rental' => $this->rental]);
                        
                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            'Checklist-' . $this->rental->rental_code . '.pdf'
                        );
                    }),

                // Make Quotation
                Action::make('make_quotation')
                    ->label('Make Quotation')
                    ->icon('heroicon-o-document-plus')
                    ->color('success')
                    ->action(function () {
                        $quotation = Quotation::create([
                            'customer_id' => $this->rental->customer_id,
                            'date' => now(),
                            'valid_until' => now()->addDays(7),
                            'status' => Quotation::STATUS_ON_QUOTE,
                            'subtotal' => $this->rental->subtotal,
                            'tax' => 0,
                            'total' => $this->rental->total,
                            'notes' => $this->rental->notes,
                        ]);

                        $this->rental->update(['quotation_id' => $quotation->id]);

                        Notification::make()
                            ->title('Quotation created successfully')
                            ->success()
                            ->send();

                        return redirect()->to(QuotationResource::getUrl('edit', ['record' => $quotation]));
                    })
                    ->visible(function () {
                        // If invoice exists, do not show Make Quotation (level up)
                        if ($this->rental->invoice_id) {
                            return false;
                        }

                        if (!$this->rental->quotation_id) {
                            return true;
                        }
                        
                        $quotation = Quotation::find($this->rental->quotation_id);
                        if (!$quotation) {
                            return true;
                        }

                        return $this->rental->updated_at->gt($quotation->created_at->addMinutes(1));
                    }),

                // Download Quotation
                Action::make('download_quotation')
                    ->label('Download Quotation')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->action(function () {
                        $quotation = Quotation::with(['customer', 'rentals.items.productUnit.product', 'rentals.items.rentalItemKits.unitKit'])->find($this->rental->quotation_id);
                        
                        if (!$quotation) {
                            Notification::make()
                                ->title('Quotation not found')
                                ->danger()
                                ->send();
                            return;
                        }

                        $pdf = Pdf::loadView('pdf.quotation', ['quotation' => $quotation]);
                        
                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            'Quotation-' . $quotation->number . '.pdf'
                        );
                    })
                    ->visible(function () {
                        // If invoice exists, do not show Download Quotation (level up)
                        if ($this->rental->invoice_id) {
                            return false;
                        }

                        if (!$this->rental->quotation_id) {
                            return false;
                        }

                        $quotation = Quotation::find($this->rental->quotation_id);
                        if (!$quotation) {
                            return false;
                        }

                        return !$this->rental->updated_at->gt($quotation->created_at->addMinutes(1));
                    }),

                // Download Invoice
                Action::make('download_invoice')
                    ->label('Download Invoice')
                    ->icon('heroicon-o-document-currency-dollar')
                    ->color('gray')
                    ->action(function () {
                        $invoice = \App\Models\Invoice::with(['customer', 'rentals.items.productUnit.product', 'rentals.items.rentalItemKits.unitKit'])->find($this->rental->invoice_id);
                        
                        if (!$invoice) {
                            Notification::make()
                                ->title('Invoice not found')
                                ->danger()
                                ->send();
                            return;
                        }

                        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice]);
                        
                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            'Invoice-' . $invoice->number . '.pdf'
                        );
                    })
                    ->visible(fn () => !empty($this->rental->invoice_id)),
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

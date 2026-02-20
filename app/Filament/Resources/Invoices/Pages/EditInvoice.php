<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('send_whatsapp_invoice')
                    ->label('Send Invoice (WhatsApp)')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->visible(fn () => \App\Models\Setting::get('whatsapp_enabled', true))
                    ->disabled(fn () => empty($this->getRecord()->customer->phone))
                    ->tooltip(fn () => empty($this->getRecord()->customer->phone) ? 'Customer phone number is missing' : null)
                    ->url(function () {
                        $record = $this->getRecord();
                        $customer = $record->customer;
                        
                        if (empty($customer->phone)) {
                            return '#';
                        }
                        
                        $templateKey = 'whatsapp_template_invoice';
                        
                        $pdfLink = \Illuminate\Support\Facades\URL::signedRoute('public-documents.invoice', ['invoice' => $record]);
                        
                        $data = [
                            'customer_name' => $customer->name,
                            'invoice_ref' => $record->number,
                            'total_amount' => 'Rp ' . number_format($record->total, 0, ',', '.'),
                            'due_date' => $record->due_date ? $record->due_date->format('d M Y') : '-',
                            'link_pdf' => $pdfLink,
                            'company_name' => \App\Models\Setting::get('site_name', 'Gearent'),
                        ];
                        
                        $message = \App\Helpers\WhatsAppHelper::parseTemplate($templateKey, $data);
                        
                        return \App\Helpers\WhatsAppHelper::getLink($customer->phone, $message);
                    })
                    ->openUrlInNewTab(),
                    
                Action::make('send_email_invoice')
                    ->label('Send Invoice (Email)')
                    ->icon('heroicon-o-envelope')
                    ->color('gray')
                    ->disabled()
                    ->tooltip('Coming Soon'),
            ])
            ->label('Send')
            ->icon('heroicon-o-paper-airplane')
            ->color('info')
            ->button(),

            DeleteAction::make(),
        ];
    }
}

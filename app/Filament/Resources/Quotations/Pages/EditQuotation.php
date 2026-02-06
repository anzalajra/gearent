<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Filament\Resources\Quotations\QuotationResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuotation extends EditRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('send_whatsapp_quotation')
                    ->label('Send Quotation (WhatsApp)')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->visible(fn () => \App\Models\Setting::get('whatsapp_enabled', true))
                    ->url(function () {
                        $record = $this->getRecord();
                        $customer = $record->customer;
                        $templateKey = 'whatsapp_template_quotation';
                        
                        $pdfLink = \Illuminate\Support\Facades\URL::signedRoute('public-documents.quotation', ['quotation' => $record]);
                        
                        $data = [
                            'customer_name' => $customer->name,
                            'quotation_ref' => $record->number,
                            'total_amount' => 'Rp ' . number_format($record->total, 0, ',', '.'),
                            'valid_until' => $record->valid_until ? $record->valid_until->format('d M Y') : '-',
                            'link_pdf' => $pdfLink,
                            'company_name' => \App\Models\Setting::get('site_name', 'Gearent'),
                        ];
                        
                        $message = \App\Helpers\WhatsAppHelper::parseTemplate($templateKey, $data);
                        
                        return \App\Helpers\WhatsAppHelper::getLink($customer->phone, $message);
                    })
                    ->openUrlInNewTab(),
                    
                Action::make('send_email_quotation')
                    ->label('Send Quotation (Email)')
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

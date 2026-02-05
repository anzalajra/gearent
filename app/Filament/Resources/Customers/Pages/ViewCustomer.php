<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\CustomerDocument;
use App\Notifications\DocumentVerifiedNotification;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('verify')
                ->label('Verify Customer')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Verify Customer')
                ->modalDescription('Are you sure you want to verify this customer? This will allow them to make rentals.')
                ->visible(fn () => !$this->record->is_verified && $this->record->getVerificationStatus() !== 'not_verified')
                ->action(function () {
                    $this->record->documents()
                        ->where('status', CustomerDocument::STATUS_PENDING)
                        ->update([
                            'status' => CustomerDocument::STATUS_APPROVED,
                            'verified_by' => Auth::id(),
                            'verified_at' => now(),
                        ]);

                    $this->record->verify(Auth::id());

                    Notification::make()
                        ->title('Customer verified successfully')
                        ->success()
                        ->send();
                }),

            Action::make('unverify')
                ->label('Revoke Verification')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Revoke Verification')
                ->modalDescription('Are you sure you want to revoke this customer\'s verification?')
                ->visible(fn () => $this->record->is_verified)
                ->action(function () {
                    $this->record->documents()
                        ->where('status', CustomerDocument::STATUS_APPROVED)
                        ->update([
                            'status' => CustomerDocument::STATUS_PENDING,
                            'verified_by' => null,
                            'verified_at' => null,
                        ]);

                    $this->record->unverify();

                    Notification::make()
                        ->title('Verification revoked')
                        ->success()
                        ->send();
                }),

            EditAction::make(),
        ];
    }
}
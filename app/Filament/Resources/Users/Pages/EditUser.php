<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\CustomerCategory;
use App\Models\CustomerDocument;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

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
                ->visible(fn () => !$this->record->is_verified && $this->record->customer_category_id)
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
                ->visible(fn () => $this->record->is_verified && $this->record->customer_category_id)
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

            Action::make('addAsCustomer')
                ->label('Add as Customer')
                ->icon('heroicon-o-user-plus')
                ->color('info')
                ->visible(fn () => !$this->record->customer_category_id)
                ->form([
                    Select::make('customer_category_id')
                        ->label('Category')
                        ->options(CustomerCategory::where('is_active', true)->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->preload(),
                    TextInput::make('phone')
                        ->tel()
                        ->maxLength(20),
                    TextInput::make('nik')
                        ->label('NIK / KTP')
                        ->maxLength(255),
                    Textarea::make('address')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                ])
                ->action(function (array $data) {
                    $this->record->update($data);

                    Notification::make()
                        ->title('User added as customer successfully')
                        ->success()
                        ->send();
                    
                    return redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                }),

            DeleteAction::make(),
        ];
    }
}

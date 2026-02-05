<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Models\CustomerDocument;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('documentType.name')
                    ->label('Document Type'),

                TextColumn::make('file_name')
                    ->label('File')
                    ->limit(30),

                TextColumn::make('file_size')
                    ->label('Size')
                    ->formatStateUsing(fn ($state) => number_format($state / 1024, 1) . ' KB'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => CustomerDocument::getStatusColor($state)),

                TextColumn::make('verifiedBy.name')
                    ->label('Verified By'),

                TextColumn::make('verified_at')
                    ->label('Verified At')
                    ->dateTime(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (CustomerDocument $record) => Storage::url($record->file_path))
                    ->openUrlInNewTab(),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (CustomerDocument $record) => $record->status === CustomerDocument::STATUS_PENDING)
                    ->action(function (CustomerDocument $record) {
                        $record->approve(Auth::id());

                        $customer = $record->customer;
                        $this->checkAndVerifyCustomer($customer);

                        Notification::make()
                            ->title('Document approved')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->visible(fn (CustomerDocument $record) => $record->status === CustomerDocument::STATUS_PENDING)
                    ->action(function (CustomerDocument $record, array $data) {
                        $record->reject(Auth::id(), $data['rejection_reason']);

                        Notification::make()
                            ->title('Document rejected')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make(),
            ]);
    }

    protected function checkAndVerifyCustomer($customer): void
    {
        $requiredTypes = \App\Models\DocumentType::getRequiredTypes($customer->customer_category_id);
        $allApproved = true;

        foreach ($requiredTypes as $type) {
            $doc = $customer->documents()->where('document_type_id', $type->id)->first();
            if (!$doc || $doc->status !== CustomerDocument::STATUS_APPROVED) {
                $allApproved = false;
                break;
            }
        }

        if ($allApproved && !$customer->is_verified) {
            $customer->verify(Auth::id());
        }
    }
}
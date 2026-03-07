<?php

namespace App\Filament\Central\Resources\TenantResource\Pages;

use App\Filament\Central\Resources\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\Action::make('extend_subscription')
                ->label('Extend 30 Days')
                ->icon('heroicon-o-calendar-days')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('This will extend the subscription by 30 days from the current expiry date (or from today if no date is set).')
                ->action(function () {
                    $current = $this->record->subscription_ends_at ?? now();
                    $this->record->update(['subscription_ends_at' => $current->addDays(30)]);
                }),
            Actions\Action::make('suspend')
                ->label('Suspend')
                ->icon('heroicon-o-pause-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status !== 'suspended')
                ->action(fn () => $this->record->update(['status' => 'suspended'])),
            Actions\Action::make('activate')
                ->label('Activate')
                ->icon('heroicon-o-play-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === 'suspended')
                ->action(fn () => $this->record->update(['status' => 'active'])),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Transform feature_overrides from associative array to repeater format
        $overrides = $this->record->feature_overrides ?? [];
        $items = [];

        if (is_array($overrides)) {
            foreach ($overrides as $feature => $enabled) {
                $items[] = [
                    'feature' => $feature,
                    'enabled' => (bool) $enabled,
                ];
            }
        }

        $data['feature_overrides_form'] = $items;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Transform repeater format back to associative array
        $overrides = [];

        foreach ($data['feature_overrides_form'] ?? [] as $item) {
            if (! empty($item['feature'])) {
                $overrides[$item['feature']] = (bool) ($item['enabled'] ?? false);
            }
        }

        // Store via stancl/tenancy magic attribute (goes into data JSON)
        $this->record->feature_overrides = ! empty($overrides) ? $overrides : null;

        unset($data['feature_overrides_form']);

        return $data;
    }
}

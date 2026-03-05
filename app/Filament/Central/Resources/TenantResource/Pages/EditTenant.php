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

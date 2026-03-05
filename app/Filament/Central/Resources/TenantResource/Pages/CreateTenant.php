<?php

namespace App\Filament\Central\Resources\TenantResource\Pages;

use App\Filament\Central\Resources\TenantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Transform repeater format to associative array for storage
        $overrides = [];

        foreach ($data['feature_overrides_form'] ?? [] as $item) {
            if (! empty($item['feature'])) {
                $overrides[$item['feature']] = (bool) ($item['enabled'] ?? false);
            }
        }

        unset($data['feature_overrides_form']);

        if (! empty($overrides)) {
            $data['data'] = array_merge($data['data'] ?? [], [
                'feature_overrides' => $overrides,
            ]);
        }

        return $data;
    }
}

<?php

namespace App\Filament\Concerns;

use App\Enums\TenantFeature;

trait ChecksTenantFeature
{
    protected static function tenantHasFeature(TenantFeature $feature): bool
    {
        $tenant = tenant();

        if (! $tenant) {
            return true;
        }

        return $tenant->hasFeature($feature);
    }
}

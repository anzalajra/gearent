<?php

namespace App\Filament\Clusters\Finance;

use App\Enums\TenantFeature;
use App\Filament\Concerns\ChecksTenantFeature;
use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class FinanceCluster extends Cluster
{
    use ChecksTenantFeature;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    public static function shouldRegisterNavigation(): bool
    {
        return static::tenantHasFeature(TenantFeature::Finance);
    }

    public static function canAccess(): bool
    {
        return static::tenantHasFeature(TenantFeature::Finance);
    }
}

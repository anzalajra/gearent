<?php
require __DIR__ . '/vendor/autoload.php';

$classes = [
    'Filament\Schemas\Schema',
    'Filament\Tables\Table',
    'Filament\Resources\Resource',
    'Filament\Clusters\Cluster',
    'App\Filament\Clusters\Finance\Resources\FinanceAccountResource',
    'App\Filament\Clusters\Finance\Resources\FinanceAccountResource\Tables\FinanceAccountsTable',
    'App\Filament\Clusters\Finance\Resources\FinanceAccountResource\Pages\ManageAccountLedger',
];

foreach ($classes as $class) {
    if (class_exists($class) || interface_exists($class)) {
        echo "EXISTS: $class\n";
    } else {
        echo "MISSING: $class\n";
    }
}

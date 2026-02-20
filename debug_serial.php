<?php

use App\Models\ProductUnit;
use App\Models\UnitKit;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$serial = 'S-TRIPOD-A'; // The serial mentioned in the case

echo "Searching for Serial: $serial\n";

// 1. Check Product Units
$units = ProductUnit::where('serial_number', $serial)->get();
echo "Found " . $units->count() . " ProductUnits:\n";
foreach ($units as $u) {
    echo "- ID: {$u->id}, Product: {$u->product->name}, Serial: {$u->serial_number}\n";
}

// 2. Check Unit Kits
$kits = UnitKit::where('serial_number', $serial)->get();
echo "Found " . $kits->count() . " UnitKits:\n";
foreach ($kits as $k) {
    echo "- ID: {$k->id}, Parent Unit ID: {$k->unit_id}, Name: {$k->name}, Serial: {$k->serial_number}, Linked Unit ID: " . ($k->linked_unit_id ?? 'NULL') . "\n";
    if ($k->unit) {
        echo "  -> Parent Unit: " . ($k->unit->product->name ?? 'Unknown') . " (" . $k->unit->serial_number . ")\n";
    }
}

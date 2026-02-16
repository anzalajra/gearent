<?php

use Illuminate\Support\Facades\Schema;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$checks = [
    'users' => ['nik', 'npwp', 'is_pkp', 'tax_type'],
    'products' => ['is_taxable', 'price_includes_tax'],
    'invoices' => ['tax_base', 'ppn_rate', 'ppn_amount', 'pph_rate', 'pph_amount'],
    'rentals' => ['tax_base', 'ppn_rate', 'ppn_amount', 'is_taxable'],
];

foreach ($checks as $table => $columns) {
    echo "Table: $table\n";
    foreach ($columns as $column) {
        $exists = Schema::hasColumn($table, $column);
        echo "  - $column: " . ($exists ? "EXISTS" : "MISSING") . "\n";
    }
}

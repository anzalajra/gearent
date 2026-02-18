<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Categories in FinanceTransaction ---\n";
$categories = \App\Models\FinanceTransaction::distinct()->pluck('category')->toArray();
foreach ($categories as $cat) {
    echo "- " . $cat . "\n";
}

echo "\n--- Accounts ---\n";
$accounts = \App\Models\Account::pluck('name', 'code')->toArray();
foreach ($accounts as $code => $name) {
    echo "{$code}: {$name}\n";
}

echo "\n--- Unsynced FinanceTransactions ---\n";
$unsyncedTransactions = \App\Models\FinanceTransaction::doesntHave('journalEntry')->count();
echo "Count: {$unsyncedTransactions}\n";

echo "\n--- Unsynced Invoices ---\n";
$unsyncedInvoices = \App\Models\Invoice::doesntHave('journalEntry')->count();
echo "Count: {$unsyncedInvoices}\n";

echo "\n--- Existing Category Mappings ---\n";
$mappings = \App\Models\CategoryMapping::all();
foreach ($mappings as $m) {
    echo "{$m->category} -> {$m->account->name} ({$m->account->code})\n";
}

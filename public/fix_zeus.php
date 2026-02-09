<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<pre>";
echo "Start Diagnostic & Fix for Zeus...\n";

// 1. Check Vendor File existence manually
$coreProviderPath = __DIR__ . '/../vendor/lara-zeus/core/src/CoreServiceProvider.php';
if (file_exists($coreProviderPath)) {
    echo "[OK] File found: vendor/lara-zeus/core/src/CoreServiceProvider.php\n";
} else {
    echo "[FAIL] CRITICAL ERROR: File not found: $coreProviderPath\n";
    echo "       This means 'lara-zeus/core' is missing from the server.\n";
    echo "       Solution: You must upload the 'vendor' folder again or run composer install successfully.\n\n";
}

// 2. Check if Class exists
if (class_exists('LaraZeus\Core\CoreServiceProvider')) {
    echo "[OK] Class LaraZeus\Core\CoreServiceProvider loaded.\n";
} else {
    echo "[FAIL] Class LaraZeus\Core\CoreServiceProvider NOT loaded.\n";
}

echo "\n---------------------------------------\n";
echo "Attempting to Clear Cache & Discover Packages...\n";
echo "---------------------------------------\n";

// 3. Run Artisan Commands
try {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

    $commands = [
        'view:clear',
        'config:clear',
        'route:clear',
        'package:discover'
    ];

    foreach ($commands as $cmd) {
        echo "Running 'php artisan $cmd' ... ";
        $kernel->call($cmd);
        echo "DONE\n";
        // echo $kernel->output() . "\n"; // Output might be verbose
    }
    
} catch (Exception $e) {
    echo "Error running commands: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\nDone. Please refresh your admin page.";
echo "</pre>";

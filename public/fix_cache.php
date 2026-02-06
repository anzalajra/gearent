<?php
// Simpan file ini di public/fix_cache.php
// Lalu akses via browser: https://warehouse.ftvupi.id/fix_cache.php

$files = [
    __DIR__ . '/../bootstrap/cache/routes-v7.php',
    __DIR__ . '/../bootstrap/cache/config.php',
    __DIR__ . '/../bootstrap/cache/packages.php',
    __DIR__ . '/../bootstrap/cache/services.php',
];

echo "<h3>Cleaning Caches...</h3>";

foreach ($files as $file) {
    if (file_exists($file)) {
        try {
            unlink($file);
            echo "Deleted: " . basename($file) . "<br>";
        } catch (Exception $e) {
            echo "Failed to delete " . basename($file) . ": " . $e->getMessage() . "<br>";
        }
    } else {
        echo "File not found (already clean): " . basename($file) . "<br>";
    }
}

// Juga coba hapus view cache
$viewFiles = glob(__DIR__ . '/../storage/framework/views/*');
foreach ($viewFiles as $file) {
    if (is_file($file) && basename($file) !== '.gitignore') {
        try {
            unlink($file);
        } catch (Exception $e) {
            // ignore
        }
    }
}
echo "View cache cleared.<br>";

echo "<h3>Done! <a href='/'>Go to Home</a></h3>";

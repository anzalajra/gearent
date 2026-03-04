<?php

/**
 * Migration Organizer Script for Multi-Tenant Architecture
 * 
 * This script organizes migrations into central/ and tenant/ folders
 * for stancl/tenancy multi-database setup.
 * 
 * Usage: php database/organize_migrations.php
 * 
 * Dry run (preview only): php database/organize_migrations.php --dry-run
 */

$dryRun = in_array('--dry-run', $argv ?? []);
$migrationsPath = __DIR__ . '/migrations';
$centralPath = $migrationsPath . '/central';
$tenantPath = $migrationsPath . '/tenant';

// Define which migrations should stay in CENTRAL database
// These are typically: users, authentication, tenants, domains, system-level tables
$centralPatterns = [
    // Laravel default central tables
    'create_users_table',
    'create_password_reset',
    'create_personal_access_tokens',
    'create_cache_table',
    'create_jobs_table',
    'create_failed_jobs',
    'create_sessions_table',
    
    // Tenancy tables (MUST be central)
    'create_tenants_table',
    'create_domains_table',
    
    // Permission/Roles (central for admin)
    'create_permission_tables',
    
    // User-related modifications that should be central
    'add_customer_fields_to_users_table',
    'add_tax_fields_to_users_table',
    'add_tax_country_to_users_table',
];

// Migrations to SKIP (don't move, keep in place)
$skipPatterns = [];

echo "===========================================\n";
echo "  Migration Organizer for Multi-Tenancy\n";
echo "===========================================\n\n";

if ($dryRun) {
    echo "*** DRY RUN MODE - No files will be moved ***\n\n";
}

// Create directories if they don't exist
if (!$dryRun) {
    if (!is_dir($centralPath)) {
        mkdir($centralPath, 0755, true);
        echo "Created: database/migrations/central/\n";
    }
    if (!is_dir($tenantPath)) {
        mkdir($tenantPath, 0755, true);
        echo "Created: database/migrations/tenant/\n";
    }
}

// Get all migration files (excluding directories)
$files = array_filter(scandir($migrationsPath), function($file) use ($migrationsPath) {
    return is_file($migrationsPath . '/' . $file) && str_ends_with($file, '.php');
});

$centralMigrations = [];
$tenantMigrations = [];
$skippedMigrations = [];

foreach ($files as $file) {
    $matchedCentral = false;
    $matchedSkip = false;
    
    // Check if should be skipped
    foreach ($skipPatterns as $pattern) {
        if (stripos($file, $pattern) !== false) {
            $matchedSkip = true;
            $skippedMigrations[] = $file;
            break;
        }
    }
    
    if ($matchedSkip) continue;
    
    // Check if should be central
    foreach ($centralPatterns as $pattern) {
        if (stripos($file, $pattern) !== false) {
            $matchedCentral = true;
            $centralMigrations[] = $file;
            break;
        }
    }
    
    // Everything else goes to tenant
    if (!$matchedCentral) {
        $tenantMigrations[] = $file;
    }
}

// Display summary
echo "\n📁 CENTRAL MIGRATIONS (landlord database):\n";
echo str_repeat("-", 50) . "\n";
foreach ($centralMigrations as $file) {
    echo "  ✓ $file\n";
}
echo "  Total: " . count($centralMigrations) . " files\n";

echo "\n📁 TENANT MIGRATIONS (per-tenant database):\n";
echo str_repeat("-", 50) . "\n";
foreach ($tenantMigrations as $file) {
    echo "  → $file\n";
}
echo "  Total: " . count($tenantMigrations) . " files\n";

if (!empty($skippedMigrations)) {
    echo "\n⏭️  SKIPPED MIGRATIONS:\n";
    echo str_repeat("-", 50) . "\n";
    foreach ($skippedMigrations as $file) {
        echo "  - $file\n";
    }
}

// Move files
if (!$dryRun) {
    echo "\n🚀 Moving files...\n\n";
    
    $movedCentral = 0;
    $movedTenant = 0;
    $errors = [];
    
    // Move central migrations
    foreach ($centralMigrations as $file) {
        $source = $migrationsPath . '/' . $file;
        $dest = $centralPath . '/' . $file;
        
        if (rename($source, $dest)) {
            $movedCentral++;
            echo "  ✓ Moved to central/: $file\n";
        } else {
            $errors[] = "Failed to move: $file";
        }
    }
    
    // Move tenant migrations
    foreach ($tenantMigrations as $file) {
        $source = $migrationsPath . '/' . $file;
        $dest = $tenantPath . '/' . $file;
        
        if (rename($source, $dest)) {
            $movedTenant++;
            echo "  → Moved to tenant/: $file\n";
        } else {
            $errors[] = "Failed to move: $file";
        }
    }
    
    echo "\n===========================================\n";
    echo "  Summary\n";
    echo "===========================================\n";
    echo "  Central migrations moved: $movedCentral\n";
    echo "  Tenant migrations moved: $movedTenant\n";
    
    if (!empty($errors)) {
        echo "\n⚠️  Errors:\n";
        foreach ($errors as $error) {
            echo "  - $error\n";
        }
    }
    
    echo "\n📌 Next Steps:\n";
    echo "  1. Update config/tenancy.php migration_parameters:\n";
    echo "     'migration_parameters' => [\n";
    echo "         '--force' => true,\n";
    echo "         '--path' => [database_path('migrations/tenant')],\n";
    echo "         '--realpath' => true,\n";
    echo "     ],\n\n";
    echo "  2. Run central migrations:\n";
    echo "     php artisan migrate --path=database/migrations/central\n\n";
    echo "  3. Create a tenant and run tenant migrations:\n";
    echo "     php artisan tenants:migrate\n\n";
    
} else {
    echo "\n💡 Run without --dry-run to actually move the files.\n";
}

echo "\n✅ Done!\n";

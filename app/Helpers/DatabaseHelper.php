<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseHelper
{
    /**
     * Disable foreign key constraints for the current database driver.
     */
    public static function disableForeignKeyConstraints(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'pgsql') {
            DB::statement('SET session_replication_role = replica;');
        } elseif (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        }
    }

    /**
     * Enable foreign key constraints for the current database driver.
     */
    public static function enableForeignKeyConstraints(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'pgsql') {
            DB::statement('SET session_replication_role = DEFAULT;');
        } elseif (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }
    }

    /**
     * Modify an ENUM column in a database-agnostic way.
     * 
     * @param string $table Table name
     * @param string $column Column name
     * @param array $values Array of allowed values
     * @param string|null $default Default value (null for no default)
     * @param bool $nullable Whether the column is nullable
     */
    public static function modifyEnumColumn(string $table, string $column, array $values, ?string $default = null, bool $nullable = false): void
    {
        $driver = DB::connection()->getDriverName();
        $valuesStr = "'" . implode("', '", $values) . "'";
        
        if ($driver === 'pgsql') {
            // PostgreSQL: Create a new type if not exists, or use VARCHAR with CHECK constraint
            $enumTypeName = "{$table}_{$column}_enum";
            $nullStr = $nullable ? 'NULL' : 'NOT NULL';
            $defaultStr = $default !== null ? "DEFAULT '$default'" : '';
            
            // Drop existing constraint if exists
            $constraintName = "{$table}_{$column}_check";
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$constraintName}");
            
            // Change column type to VARCHAR
            DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE VARCHAR(255)");
            
            // Add check constraint
            $checkValues = "'" . implode("', '", $values) . "'";
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$constraintName} CHECK ({$column} IN ({$checkValues}))");
            
            // Set nullable
            if ($nullable) {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} DROP NOT NULL");
            } else {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} SET NOT NULL");
            }
            
            // Set default
            if ($default !== null) {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} SET DEFAULT '$default'");
            } else {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} DROP DEFAULT");
            }
        } else {
            // MySQL/MariaDB
            $nullStr = $nullable ? 'NULL' : 'NOT NULL';
            $defaultStr = $default !== null ? "DEFAULT '$default'" : '';
            DB::statement("ALTER TABLE {$table} MODIFY COLUMN `{$column}` ENUM({$valuesStr}) {$nullStr} {$defaultStr}");
        }
    }

    /**
     * Get the current database driver name.
     */
    public static function getDriver(): string
    {
        return DB::connection()->getDriverName();
    }

    /**
     * Check if the current driver is PostgreSQL.
     */
    public static function isPostgres(): bool
    {
        return self::getDriver() === 'pgsql';
    }

    /**
     * Check if the current driver is MySQL or MariaDB.
     */
    public static function isMySql(): bool
    {
        return in_array(self::getDriver(), ['mysql', 'mariadb']);
    }
}

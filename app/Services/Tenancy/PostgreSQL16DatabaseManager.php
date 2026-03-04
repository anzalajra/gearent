<?php

declare(strict_types=1);

namespace App\Services\Tenancy;

use Stancl\Tenancy\Contracts\TenantDatabaseManager;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Custom PostgreSQL Database Manager for PostgreSQL 16+ in Docker Alpine.
 * 
 * Fixes common issues:
 * - Uses template0 instead of template1 for clean database creation
 * - Properly handles 'C' collation for Alpine containers
 * - Escapes database names to prevent SQL injection
 */
class PostgreSQL16DatabaseManager implements TenantDatabaseManager
{
    /** @var string */
    protected $connection;

    public function __construct()
    {
        $this->connection = config('tenancy.database.central_connection') ?? 'central';
    }

    /**
     * Create a database for a tenant.
     */
    public function createDatabase(TenantWithDatabase $tenant): bool
    {
        $database = $tenant->database()->getName();
        $charset = config('tenancy.database.charset', 'UTF8');
        $collation = config('tenancy.database.collation', 'C');
        $template = config('tenancy.database.template', 'template0');

        // Escape database name (PostgreSQL uses double quotes for identifiers)
        $escapedDb = '"' . str_replace('"', '""', $database) . '"';

        // Build CREATE DATABASE query for PostgreSQL 16
        $sql = "CREATE DATABASE {$escapedDb}";
        $sql .= " WITH ENCODING '{$charset}'";
        $sql .= " LC_COLLATE '{$collation}'";
        $sql .= " LC_CTYPE '{$collation}'";
        $sql .= " TEMPLATE {$template}";

        return DB::connection($this->connection)->statement($sql);
    }

    /**
     * Delete a tenant's database.
     */
    public function deleteDatabase(TenantWithDatabase $tenant): bool
    {
        $database = $tenant->database()->getName();
        $escapedDb = '"' . str_replace('"', '""', $database) . '"';

        // Terminate active connections before dropping
        $this->terminateConnections($database);

        return DB::connection($this->connection)->statement("DROP DATABASE IF EXISTS {$escapedDb}");
    }

    /**
     * Check if a database exists.
     */
    public function databaseExists(string $name): bool
    {
        return (bool) DB::connection($this->connection)
            ->selectOne("SELECT 1 FROM pg_database WHERE datname = ?", [$name]);
    }

    /**
     * Terminate all connections to a database.
     * Required before dropping a database in PostgreSQL.
     */
    protected function terminateConnections(string $database): void
    {
        // For PostgreSQL 9.2+
        DB::connection($this->connection)->statement("
            SELECT pg_terminate_backend(pg_stat_activity.pid)
            FROM pg_stat_activity
            WHERE pg_stat_activity.datname = ?
            AND pid <> pg_backend_pid()
        ", [$database]);
    }

    /**
     * Set the connection to use for database operations.
     */
    public function setConnection(string $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * Make a tenant database connection config from template.
     */
    public function makeConnectionConfig(array $baseConfig, string $databaseName): array
    {
        $baseConfig['database'] = $databaseName;
        return $baseConfig;
    }
}

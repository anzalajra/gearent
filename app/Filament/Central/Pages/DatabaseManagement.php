<?php

namespace App\Filament\Central\Pages;

use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use BackedEnum;
use UnitEnum;

class DatabaseManagement extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 12;

    protected string $view = 'filament.central.pages.database-management';

    public array $databaseInfo = [];
    public array $tenantDatabases = [];

    public function mount(): void
    {
        $this->loadDatabaseInfo();
    }

    protected function loadDatabaseInfo(): void
    {
        $connection = config('database.default');
        
        $dbName = config("database.connections.{$connection}.database");

        $this->databaseInfo = [
            'driver' => config("database.connections.{$connection}.driver"),
            'host' => config("database.connections.{$connection}.host"),
            'port' => config("database.connections.{$connection}.port"),
            'database' => $dbName,
            'username' => config("database.connections.{$connection}.username"),
            'size' => $this->getDatabaseSize($dbName, config("database.connections.{$connection}.driver")),
        ];

        // Get tenant databases
        $this->tenantDatabases = Tenant::with('domains')
            ->get()
            ->map(function ($tenant) {
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'database' => config('tenancy.database.prefix') . $tenant->id . config('tenancy.database.suffix'),
                    'status' => $tenant->status,
                    'domains' => $tenant->domains->pluck('domain')->join(', '),
                ];
            })
            ->toArray();
    }

    protected function getDatabaseSize(string $dbName, string $driver): string
    {
        try {
            if ($driver === 'pgsql') {
                $result = DB::connection('central')->select('SELECT ROUND(pg_database_size(?) / 1024.0 / 1024.0, 2) AS size', [$dbName]);
            } else {
                $result = DB::connection('central')->select('SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size FROM information_schema.tables WHERE table_schema = ?', [$dbName]);
            }

            return ($result[0]->size ?? 0).' MB';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('migrateCentral')
                ->label('Migrate Central')
                ->icon('heroicon-o-arrow-up-circle')
                ->color('primary')
                ->requiresConfirmation()
                ->modalDescription('Run migrations for the central database?')
                ->action(function () {
                    Artisan::call('migrate', [
                        '--path' => 'database/migrations/central',
                        '--force' => true,
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Central Migration Complete')
                        ->body(Artisan::output())
                        ->send();
                }),

            Action::make('migrateTenants')
                ->label('Migrate All Tenants')
                ->icon('heroicon-o-arrows-up-down')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('Run migrations for all tenant databases?')
                ->action(function () {
                    Artisan::call('tenants:migrate', [
                        '--force' => true,
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Tenant Migration Complete')
                        ->body('All tenant databases have been migrated.')
                        ->send();
                }),

            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->loadDatabaseInfo();

                    Notification::make()
                        ->success()
                        ->title('Refreshed')
                        ->send();
                }),
        ];
    }
}

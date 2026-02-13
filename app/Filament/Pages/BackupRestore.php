<?php

namespace App\Filament\Pages;

use App\Models\BackupHistory;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Livewire\Component;

class BackupRestore extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static string | \UnitEnum | null $navigationGroup = 'Setting';
    protected static ?int $navigationSort = 99;
    protected static ?string $navigationLabel = 'Backup & Restore';
    protected static ?string $title = 'Backup & Restore';
    protected static ?string $slug = 'backup-restore';

    protected string $view = 'filament.pages.backup-restore';

    // Progress tracking properties
    public bool $isProcessing = false;
    public string $currentOperation = '';
    public string $progressMessage = '';
    public int $progressPercent = 0;

    public function table(Table $table): Table
    {
        return $table
            ->query(BackupHistory::query()->latest())
            ->columns([
                TextColumn::make('created_at')->dateTime()->label('Date'),
                TextColumn::make('user.name')->label('User'),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('filename'),
                TextColumn::make('size')->formatStateUsing(fn ($state) => number_format($state / 1024, 2) . ' KB'),
                TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->actions([]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backup')
                ->label('Create Backup')
                ->modalHeading('Select Data to Backup')
                ->disabled(fn() => $this->isProcessing)
                ->form([
                    CheckboxList::make('options')
                        ->options([
                            'products' => 'Products (Items, Categories, Brands)',
                            'rentals' => 'Rentals (Orders, Invoices, Deliveries)',
                            'users' => 'Users & System (Admins, Customers, Settings)',
                        ])
                        ->default(['products', 'rentals', 'users'])
                        ->required(),
                ])
                ->action(function (array $data) {
                    return $this->processBackup($data['options']);
                }),

            Action::make('restore')
                ->label('Restore Data')
                ->color('danger')
                ->disabled(fn() => $this->isProcessing)
                ->modalWidth('2xl')
                ->form([
                    Wizard::make([
                        Step::make('Upload Backup')
                            ->schema([
                                FileUpload::make('backup_file')
                                    ->label('Upload Backup File (.zip)')
                                    ->disk('local')
                                    ->directory('temp-backups')
                                    ->acceptedFileTypes([
                                        'application/zip', 
                                        'application/x-zip-compressed', 
                                        'multipart/x-zip',
                                        'application/x-compressed'
                                    ])
                                    ->required()
                                    ->live(),
                            ]),
                        Step::make('Select Data')
                            ->schema([
                                CheckboxList::make('selected_tables')
                                    ->label('Select Data to Restore')
                                    ->options(function ($get) {
                                        $filePath = $get('backup_file');
                                        if (!$filePath) return [];
                                        return $this->getBackupContents($filePath);
                                    })
                                    ->default(function ($get) {
                                        $filePath = $get('backup_file');
                                        if (!$filePath) return [];
                                        return array_keys($this->getBackupContents($filePath));
                                    })
                                    ->required()
                                    ->columns(2)
                                    ->bulkToggleable(),
                            ]),
                    ])->submitAction(new \Illuminate\Support\HtmlString('<button type="submit" class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-red-600 text-white hover:bg-red-500 focus-visible:ring-red-500/50 dark:bg-red-500 dark:hover:bg-red-400 dark:focus-visible:ring-red-400/50">Restore Selected Data</button>'))
                ])
                ->modalSubmitAction(false)
                ->action(function (array $data) {
                    if (empty($data['selected_tables'])) {
                        Notification::make()->title('No data selected')->warning()->send();
                        return;
                    }
                    $this->processRestore($data['backup_file'], $data['selected_tables']);
                }),
        ];
    }

    protected function getBackupContents($filePath): array
    {
        if (is_array($filePath)) $filePath = reset($filePath);
        if (empty($filePath)) return [];

        // Handle object (e.g., TemporaryUploadedFile)
        if (is_object($filePath) && method_exists($filePath, 'getRealPath')) {
             if (file_exists($filePath->getRealPath())) {
                 return $this->readZipContents($filePath->getRealPath());
             }
             // If file moved, try to use the filename if available, or cast to string
             $filePath = (string) $filePath;
        }

        $fullPath = null;
        $disk = Storage::disk('local');

        // Check various possible locations
        $pathsToCheck = [
            $filePath, // Direct path
            $disk->path($filePath), // Disk path
            storage_path('app/private/' . $filePath),
            storage_path('app/public/' . $filePath),
            storage_path('app/private/temp-backups/' . basename($filePath)),
            storage_path('app/livewire-tmp/' . basename($filePath)),
            storage_path('app/public/livewire-tmp/' . basename($filePath)), // Check public livewire-tmp
            storage_path('app/private/livewire-tmp/' . basename($filePath)),
        ];

        foreach ($pathsToCheck as $path) {
            if (file_exists($path)) {
                $fullPath = $path;
                break;
            }
        }

        if (!$fullPath) {
            Log::warning('Backup file not found in any expected location. Input path: ' . $filePath);
            return [];
        }

        return $this->readZipContents($fullPath);
    }

    protected function readZipContents($fullPath): array
    {
        $tables = [];
        $zip = new ZipArchive;
        if ($zip->open($fullPath) === TRUE) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (pathinfo($filename, PATHINFO_EXTENSION) === 'json' && $filename[0] !== '.') {
                    $tableName = pathinfo($filename, PATHINFO_FILENAME);
                    // Filter out non-table files like backup_info.json
                    if ($tableName !== 'backup_info') {
                        $tables[$tableName] = $tableName;
                    }
                }
            }
            $zip->close();
        } else {
             Log::error('Failed to open zip file: ' . $fullPath);
        }
        return $tables;
    }

    public function processBackup(array $options)
    {
        $this->isProcessing = true;
        $this->currentOperation = 'Creating backup...';
        $this->progressMessage = 'Initializing backup process...';
        $this->progressPercent = 10;
        
        $filename = 'backup-' . date('Y-m-d-H-i-s') . '.zip';
        $zipPath = storage_path('app/private/backups/' . $filename);
        
        // Ensure directory exists
        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            
            $tables = $this->getTablesFromOptions($options);
            $backupInfo = [
                'created_at' => now()->toISOString(),
                'version' => '1.0',
                'tables' => [],
                'total_records' => 0
            ];
            
            $totalTables = count($tables);
            $processedTables = 0;
            
            foreach ($tables as $modelClass) {
                if (!class_exists($modelClass)) continue;
                
                $model = new $modelClass;
                $tableName = $model->getTable();
                
                $this->progressMessage = "Processing table: {$tableName}...";
                $this->progressPercent = 20 + (int)(($processedTables / $totalTables) * 60);
                
                $allRecords = $modelClass::all();
                // Ensure hidden fields (like password) are included in backup
                $hidden = $model->getHidden();
                if (!empty($hidden)) {
                    $allRecords->makeVisible($hidden);
                }
                
                $data = $allRecords->toArray();
                $recordCount = count($data);
                
                $backupInfo['tables'][$tableName] = [
                    'model' => $modelClass,
                    'records' => $recordCount
                ];
                $backupInfo['total_records'] += $recordCount;
                
                $zip->addFromString($tableName . '.json', json_encode($data, JSON_PRETTY_PRINT));
                $processedTables++;
            }
            
            $this->progressMessage = 'Adding backup metadata...';
            $this->progressPercent = 85;
            
            // Add backup metadata
            $zip->addFromString('backup_info.json', json_encode($backupInfo, JSON_PRETTY_PRINT));
            
            $zip->close();
        } else {
            $this->isProcessing = false;
            $this->currentOperation = '';
            $this->progressMessage = '';
            $this->progressPercent = 0;
            
            Notification::make()->title('Backup Failed')->danger()->send();
            return;
        }

        $size = filesize($zipPath);

        $this->progressMessage = 'Saving backup record...';
        $this->progressPercent = 95;

        $backupHistory = BackupHistory::create([
            'user_id' => Auth::id(),
            'type' => implode(', ', $options),
            'filename' => $filename,
            'size' => $size,
            'status' => 'success',
        ]);
        
        $this->progressMessage = 'Finalizing...';
        $this->progressPercent = 100;
        
        // Log the backup operation
        Log::channel('backup-restore')->info('Backup created successfully', [
            'backup_id' => $backupHistory->id,
            'user_id' => Auth::id(),
            'filename' => $filename,
            'size' => $size,
            'tables' => $backupInfo['tables'],
            'total_records' => $backupInfo['total_records']
        ]);

        $this->isProcessing = false;
        $this->currentOperation = '';
        $this->progressMessage = '';
        $this->progressPercent = 0;

        return response()->download($zipPath)->deleteFileAfterSend();
    }

    public function processRestore($filePath, array $selectedTables = [])
    {
        $this->isProcessing = true;
        $this->currentOperation = 'Restoring backup...';
        $this->progressMessage = 'Initializing restore process...';
        $this->progressPercent = 10;
        
        try {
            $fullPath = Storage::disk('local')->path($filePath);

            if (!file_exists($fullPath)) {
                $this->isProcessing = false;
                Notification::make()->title('Backup file not found')->danger()->send();
                return;
            }

            $zip = new ZipArchive;
            if ($zip->open($fullPath) !== TRUE) {
                $this->isProcessing = false;
                Notification::make()->title('Failed to open backup file')->danger()->send();
                return;
            }
            
            // Validate backup file structure
            $backupInfo = $zip->getFromName('backup_info.json');
            if (!$backupInfo) {
                $this->isProcessing = false;
                Notification::make()->title('Invalid backup file format')->danger()->send();
                $zip->close();
                return;
            }
            
            $backupData = json_decode($backupInfo, true);
            if (!$backupData || !isset($backupData['version'])) {
                $this->isProcessing = false;
                Notification::make()->title('Invalid backup file metadata')->danger()->send();
                $zip->close();
                return;
            }
            
            $this->progressMessage = 'Preparing database for restore...';
            $this->progressPercent = 20;
            
            Schema::disableForeignKeyConstraints();
            Model::unguard();
            DB::beginTransaction();

            try {
                $restoredTables = [];
                $totalRecords = 0;
                $totalFiles = $zip->numFiles;
                $processedFiles = 0;
                
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    $tableName = pathinfo($filename, PATHINFO_FILENAME);
                    
                    // Skip metadata and non-selected tables
                    if ($tableName === 'backup_info' || (!empty($selectedTables) && !in_array($tableName, $selectedTables))) {
                        continue;
                    }

                    $this->progressMessage = "Processing table: {$tableName}...";
                    $this->progressPercent = 25 + (int)(($processedFiles / $totalFiles) * 60);

                    // Identify model from table name
                    $modelClass = $this->getModelFromTable($tableName);
                    
                    if ($modelClass) {
                        $json = $zip->getFromIndex($i);
                        $data = json_decode($json, true);
                        
                        if (is_array($data)) {
                            $recordCount = 0;
                            foreach ($data as $record) {
                                // We assume 'id' is the primary key
                                if (isset($record['id'])) {
                                    
                                    // Handle missing password for Users/Customers (fix for restore error 1364)
                                    if (in_array($modelClass, [\App\Models\User::class, \App\Models\Customer::class])) {
                                        if (!isset($record['password']) || empty($record['password'])) {
                                            // Check if record exists in DB
                                            $exists = $modelClass::where('id', $record['id'])->exists();
                                            
                                            if (!$exists) {
                                                // If new record and no password provided, set default 'password'
                                                // Hash for 'password': $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
                                                $record['password'] = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
                                                Log::warning("Restoring {$modelClass} ID {$record['id']} with default password due to missing password in backup.");
                                            }
                                        }
                                    }

                                    $modelClass::updateOrCreate(['id' => $record['id']], $record);
                                    $recordCount++;
                                }
                            }
                            $restoredTables[$tableName] = $recordCount;
                            $totalRecords += $recordCount;
                        }
                    }
                    $processedFiles++;
                }
                
                DB::commit();
                
                $this->isProcessing = false;
                $this->currentOperation = '';
                $this->progressMessage = '';
                $this->progressPercent = 0;
                
                $message = "Restore successful! ";
                $message .= "Tables restored: " . count($restoredTables) . ". ";
                $message .= "Total records: " . $totalRecords . ".";
                
                Notification::make()->title($message)->success()->send();
                
            } catch (\Exception $e) {
                DB::rollBack();
                $this->isProcessing = false;
                Log::error('Restore failed: ' . $e->getMessage());
                Notification::make()->title('Restore Failed: ' . $e->getMessage())->danger()->send();
            } finally {
                Schema::enableForeignKeyConstraints();
                Model::reguard();
                $zip->close();
                // Delete the uploaded file after restore
                Storage::disk('local')->delete($filePath);
            }
            
        } catch (\Exception $e) {
            $this->isProcessing = false;
            Log::error('Restore process error: ' . $e->getMessage());
            Notification::make()->title('Restore process failed: ' . $e->getMessage())->danger()->send();
        }
    }

    protected function getTablesFromOptions(array $options): array
    {
        $map = [
            'products' => [
                \App\Models\Category::class,
                \App\Models\Brand::class,
                \App\Models\Product::class,
                \App\Models\ProductUnit::class,
                \App\Models\UnitKit::class,
            ],
            'rentals' => [
                \App\Models\Rental::class,
                \App\Models\RentalItem::class,
                \App\Models\RentalItemKit::class,
                \App\Models\Delivery::class,
                \App\Models\DeliveryItem::class,
                \App\Models\Quotation::class,
                \App\Models\Invoice::class,
                \App\Models\Cart::class,
            ],
            'users' => [
                \App\Models\User::class,
                \App\Models\CustomerCategory::class,
                \App\Models\DocumentType::class,
                \App\Models\Customer::class,
                \App\Models\CustomerDocument::class,
                \App\Models\NavigationMenu::class,
                \App\Models\Setting::class,
                \Spatie\Permission\Models\Permission::class,
                \Spatie\Permission\Models\Role::class,
            ],
        ];

        $result = [];
        foreach ($options as $option) {
            if (isset($map[$option])) {
                $result = array_merge($result, $map[$option]);
            }
        }
        return array_unique($result);
    }
    
    protected function getModelFromTable(string $tableName): ?string
    {
        $allModels = $this->getTablesFromOptions(['products', 'rentals', 'users']);
        
        foreach ($allModels as $class) {
            if (!class_exists($class)) continue;
            $instance = new $class;
            if ($instance->getTable() === $tableName) {
                return $class;
            }
        }
        
        return null;
    }
}
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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

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
                ->form([
                    CheckboxList::make('options')
                        ->options([
                            'products' => 'Products (Items, Categories, Brands)',
                            'customers' => 'Customers (Profiles, Documents)',
                            'rentals' => 'Rentals (Orders, Invoices, Deliveries)',
                        ])
                        ->default(['products', 'customers', 'rentals'])
                        ->required(),
                ])
                ->action(function (array $data) {
                    return $this->processBackup($data['options']);
                }),

            Action::make('restore')
                ->label('Restore Data')
                ->color('danger')
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
                                    ->options(function (Get $get) {
                                        $filePath = $get('backup_file');
                                        if (!$filePath) return [];
                                        return $this->getBackupContents($filePath);
                                    })
                                    ->default(function (Get $get) {
                                        $filePath = $get('backup_file');
                                        if (!$filePath) return [];
                                        return array_keys($this->getBackupContents($filePath));
                                    })
                                    ->required()
                                    ->columns(2)
                                    ->bulkToggleable(),
                            ]),
                    ])->submitAction(new \Illuminate\Support\HtmlString('<button type="submit" class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-btn-color-danger fi-color-custom fi-btn-style-solid fi-ac-btn-action gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50" style="--c-400:var(--danger-400);--c-500:var(--danger-500);--c-600:var(--danger-600);">Restore Selected Data</button>'))
                ])
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

        $disk = Storage::disk('local');
        $fullPath = null;

        // 1. Check if it's already in the destination path (after save, usually not available in form cycle)
        if ($disk->exists($filePath)) {
            $fullPath = $disk->path($filePath);
        } 
        // 2. Check temporary file path (Livewire TemporaryFileUpload)
        // If $filePath is just a filename or relative path, we need to find the real temp path
        else {
             try {
                // Try to find if it's a TemporaryUploadedFile object or similar in Livewire context
                // But here we only get the state string. 
                // Let's assume the state string IS the path relative to the disk root if it was saved,
                // OR it might be a temporary ID.
                
                // If it's a temporary file, Filament/Livewire usually handles the path resolution internally.
                // However, accessing it via disk might fail if it's still in a tmp dir not covered by the disk configuration.
                
                // Let's try to locate it in the livewire-tmp directory if it's a standard Livewire temp file
                // But FileUpload component with 'directory' param might place it elsewhere.
                
                // CRITICAL FIX: When using 'live()', the file is uploaded to a temporary location.
                // We need to check if the file exists at the absolute path if provided, 
                // or check the storage path.
                
                if (file_exists($filePath)) {
                    $fullPath = $filePath;
                } elseif (file_exists(storage_path('app/private/' . $filePath))) {
                    $fullPath = storage_path('app/private/' . $filePath);
                } elseif (file_exists(storage_path('app/' . $filePath))) {
                     $fullPath = storage_path('app/' . $filePath);
                }
             } catch (\Exception $e) {
                 // ignore
             }
        }

        if (!$fullPath || !file_exists($fullPath)) return [];

        $tables = [];
        $zip = new ZipArchive;
        if ($zip->open($fullPath) === TRUE) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (pathinfo($filename, PATHINFO_EXTENSION) === 'json' && $filename[0] !== '.') {
                    $tableName = pathinfo($filename, PATHINFO_FILENAME);
                    $tables[$tableName] = $tableName;
                }
            }
            $zip->close();
        }
        
        return $tables;
    }

    public function processBackup(array $options)
    {
        $filename = 'backup-' . date('Y-m-d-H-i-s') . '.zip';
        $zipPath = storage_path('app/public/' . $filename);
        
        // Ensure directory exists
        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            
            $tables = $this->getTablesFromOptions($options);
            
            foreach ($tables as $modelClass) {
                if (!class_exists($modelClass)) continue;
                
                $model = new $modelClass;
                $tableName = $model->getTable();
                $data = $modelClass::all()->toArray();
                $zip->addFromString($tableName . '.json', json_encode($data, JSON_PRETTY_PRINT));
            }
            
            $zip->close();
        } else {
             Notification::make()->title('Backup Failed')->danger()->send();
             return;
        }

        $size = filesize($zipPath);

        BackupHistory::create([
            'user_id' => Auth::id(),
            'type' => implode(', ', $options),
            'filename' => $filename,
            'size' => $size,
            'status' => 'success',
        ]);

        return response()->download($zipPath)->deleteFileAfterSend();
    }

    public function processRestore($filePath, array $selectedTables = [])
    {
        $fullPath = Storage::disk('local')->path($filePath);

        $zip = new ZipArchive;
        if ($zip->open($fullPath) === TRUE) {
            
            Schema::disableForeignKeyConstraints();
            Model::unguard();
            DB::beginTransaction();

            try {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    $tableName = pathinfo($filename, PATHINFO_FILENAME);
                    
                    // Skip if not selected
                    if (!empty($selectedTables) && !in_array($tableName, $selectedTables)) {
                        continue;
                    }

                    // Identify model from table name
                    $modelClass = $this->getModelFromTable($tableName);
                    
                    if ($modelClass) {
                        $json = $zip->getFromIndex($i);
                        $data = json_decode($json, true);
                        
                        if (is_array($data)) {
                            foreach ($data as $record) {
                                // We assume 'id' is the primary key
                                $modelClass::updateOrCreate(['id' => $record['id']], $record);
                            }
                        }
                    }
                }
                
                DB::commit();
                Notification::make()->title('Restore Successful')->success()->send();
                
            } catch (\Exception $e) {
                DB::rollBack();
                Notification::make()->title('Restore Failed: ' . $e->getMessage())->danger()->send();
            } finally {
                Schema::enableForeignKeyConstraints();
                Model::reguard();
                $zip->close();
                // Optional: Delete the uploaded file after restore
                Storage::disk('local')->delete($filePath);
            }
            
        } else {
            Notification::make()->title('Failed to open backup file')->danger()->send();
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
            'customers' => [
                \App\Models\CustomerCategory::class,
                \App\Models\DocumentType::class,
                \App\Models\Customer::class,
                \App\Models\CustomerDocument::class,
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
        $allModels = array_merge(
            $this->getTablesFromOptions(['products']),
            $this->getTablesFromOptions(['customers']),
            $this->getTablesFromOptions(['rentals'])
        );
        
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

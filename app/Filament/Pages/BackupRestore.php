<?php

namespace App\Filament\Pages;

use App\Models\BackupHistory;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
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
                ->form([
                    FileUpload::make('backup_file')
                        ->disk('local')
                        ->directory('temp-backups')
                        ->acceptedFileTypes(['application/zip'])
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->processRestore($data['backup_file']);
                }),
        ];
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

    public function processRestore($filePath)
    {
        $fullPath = Storage::disk('local')->path($filePath);

        $zip = new ZipArchive;
        if ($zip->open($fullPath) === TRUE) {
            
            Schema::disableForeignKeyConstraints();
            DB::beginTransaction();

            try {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    $tableName = pathinfo($filename, PATHINFO_FILENAME);
                    
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

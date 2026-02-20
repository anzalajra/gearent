<?php

namespace App\Filament\Resources\ProductUnits\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

use App\Models\Category;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Support\Str;

class KitsRelationManager extends RelationManager
{
    protected static string $relationship = 'kits';

    protected static ?string $title = 'Unit Kits';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Hidden::make('linked_unit_id'),

                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., Battery, Charger, Strap, Lens Cap')
                    ->dehydrated(),

                TextInput::make('serial_number')
                    ->maxLength(255)
                    ->placeholder('Optional serial number')
                    ->dehydrated()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                        // Check if Serial Number matches an existing Product Unit
                        if (filled($state)) {
                            $existingUnit = \App\Models\ProductUnit::where('serial_number', $state)->first();
                            
                            if ($existingUnit) {
                                // Inform user about auto-linking
                                $set('name', $existingUnit->product->name);
                                $set('condition', $existingUnit->condition);
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Existing Unit Found')
                                    ->body("Found unit '{$existingUnit->product->name}' with serial '{$state}'. It will be linked automatically.")
                                    ->success()
                                    ->send();
                                return;
                            }
                        }
                    }),

                Select::make('condition')
                    ->options(\App\Models\UnitKit::getConditionOptions())
                    ->default('excellent')
                    ->required()
                    ->dehydrated(),

                Textarea::make('notes')
                    ->rows(3)
                    ->placeholder('Additional notes about this kit item'),
            ]);
    }
    
    // Helper to ensure Product and Unit exist
    protected function ensureProductAndUnit(array $data): array
    {
        if (!empty($data['name'])) {
            // 1. Ensure Default Category & Brand
            $category = Category::firstOrCreate(
                ['slug' => 'accessories-kits'],
                ['name' => 'Accessories & Kits']
            );
            
            $brand = Brand::firstOrCreate(
                ['slug' => 'generic'],
                ['name' => 'Generic']
            );

            // 2. Find or Create Product
            $productSlug = Str::slug($data['name']);
            // Avoid duplicate slug error by appending random string if needed? 
            // Better: find by name first
            $product = Product::where('name', $data['name'])->first();
            
            if (!$product) {
                // Check if slug exists
                if (Product::where('slug', $productSlug)->exists()) {
                     $productSlug .= '-' . Str::random(4);
                }
                
                $product = Product::create([
                    'name' => $data['name'],
                    'slug' => $productSlug,
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'daily_rate' => 0, // Default 0 for kits
                    'is_active' => true,
                ]);
            }

            // 3. Find or Create Unit
            $serial = $data['serial_number'] ?? ('KIT-' . strtoupper(Str::random(8)));
            $unit = ProductUnit::where('serial_number', $serial)->first();

            if (!$unit) {
                $unit = ProductUnit::create([
                    'product_id' => $product->id,
                    'serial_number' => $serial,
                    'status' => ProductUnit::STATUS_AVAILABLE,
                    'condition' => $data['condition'] ?? 'good',
                ]);
            }

            // 4. Update ALL existing kits with this serial to link to this unit
            // This fixes "ghost" kits that were created before the ProductUnit existed
            \App\Models\UnitKit::where('serial_number', $serial)
                ->whereNull('linked_unit_id')
                ->update(['linked_unit_id' => $unit->id]);

            // 5. Update data to link to this unit
            $data['linked_unit_id'] = $unit->id;
            // Name/Serial will be stored in UnitKit table too as cache/snapshot
        }
        
        return $data;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('serial_number')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('condition')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'excellent' => 'success',
                        'good' => 'info',
                        'fair' => 'warning',
                        'poor' => 'danger',
                        'broken' => 'danger',
                        'lost' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('notes')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->notes)
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        return $this->ensureProductAndUnit($data);
                    }),
                DeleteAction::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        return $this->ensureProductAndUnit($data);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
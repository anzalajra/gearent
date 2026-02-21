<?php

namespace App\Filament\Resources\Warehouses\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use App\Filament\Resources\ProductUnits\Schemas\ProductUnitForm;
use App\Filament\Resources\ProductUnits\Tables\ProductUnitsTable;
use App\Models\ProductUnit;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

use Filament\Actions\Action;

class ProductUnitsRelationManager extends RelationManager
{
    protected static string $relationship = 'productUnits';
    
    protected static ?string $title = 'Stock in Warehouse';

    public function form(Schema $schema): Schema
    {
        return ProductUnitForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return ProductUnitsTable::configure($table)
            ->recordTitleAttribute('serial_number')
            ->headerActions([
                Action::make('associate_unit')
                    ->label('Add Existing Unit')
                    ->icon('heroicon-m-plus')
                    ->form([
                        Select::make('product_unit_id')
                            ->label('Product Unit')
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                return ProductUnit::query()
                                    ->where('serial_number', 'like', "%{$search}%")
                                    ->orWhereHas('product', function ($query) use ($search) {
                                        $query->where('name', 'like', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function ($unit) {
                                        return [$unit->id => "{$unit->product->name} - {$unit->serial_number}"];
                                    });
                            })
                            ->getOptionLabelUsing(function ($value): ?string {
                                $unit = ProductUnit::find($value);
                                return $unit ? "{$unit->product->name} - {$unit->serial_number}" : null;
                            })
                            ->required(),
                    ])
                    ->action(function (array $data, \Livewire\Component $livewire): void {
                        $unit = ProductUnit::find($data['product_unit_id']);
                        if ($unit) {
                            $warehouse = $livewire->getOwnerRecord();
                            $unit->update(['warehouse_id' => $warehouse->id]);
                            
                            \Filament\Notifications\Notification::make()
                                ->title("Unit Added to {$warehouse->name}")
                                ->success()
                                ->send();
                                
                            $livewire->dispatch('refresh');
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DissociateAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

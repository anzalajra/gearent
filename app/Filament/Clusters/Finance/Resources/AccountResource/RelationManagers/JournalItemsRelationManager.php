<?php

namespace App\Filament\Clusters\Finance\Resources\AccountResource\RelationManagers;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\JournalEntryItem;
use BackedEnum;

class JournalItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Transactions';

    protected static string|BackedEnum|null $icon = 'heroicon-o-clipboard-document-list';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // Read-only view mostly
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('journalEntry.date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('journalEntry.reference_number')
                    ->label('Reference')
                    ->searchable(),
                Tables\Columns\TextColumn::make('journalEntry.description')
                    ->label('Description')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('debit')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('credit')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Usually we don't create items directly from here, we create Journal Entries
            ])
            ->recordActions([
                // Link to view the journal entry
                Action::make('view_entry')
                    ->label('View Entry')
                    ->icon('heroicon-o-eye')
                    ->url(fn (JournalEntryItem $record) => \App\Filament\Clusters\Finance\Resources\JournalEntryResource::getUrl('edit', ['record' => $record->journal_entry_id])),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('created_at', 'desc'); // Assuming items have timestamps or sort by journalEntry.date
    }
}

<?php

namespace App\Filament\Clusters\Finance\Resources;

use App\Filament\Clusters\Finance\FinanceCluster;
use App\Filament\Clusters\Finance\Resources\JournalEntryResource\Pages;
use App\Models\JournalEntry;
use App\Models\FinanceTransaction;
use App\Models\Account;
use App\Models\Setting;
use App\Services\JournalService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use BackedEnum;

class JournalEntryResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $navigationLabel = 'Journal Entries';
    
    protected static ?string $modelLabel = 'Journal Entry';
    
    protected static ?string $pluralModelLabel = 'Journal Entries';
    
    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return Setting::get('finance_mode', 'advanced') === 'advanced';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Entry Details')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('reference_number')
                            ->default(fn () => 'JRN-' . date('YmdHis'))
                            ->required()
                            ->unique(ignoreRecord: true),
                        DatePicker::make('date')
                            ->default(now())
                            ->required(),
                        TextInput::make('description')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Journal Items')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Select::make('account_id')
                                    ->label('Account')
                                    ->options(Account::query()->orderBy('code')->get()->mapWithKeys(fn ($account) => [$account->id => "{$account->code} - {$account->name}"]))
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->columnSpanFull(),
                                \Filament\Forms\Components\Placeholder::make('current_balance')
                                    ->label('Current Balance')
                                    ->content(function ($get) {
                                        $accountId = $get('account_id');
                                        if (!$accountId) {
                                            return null;
                                        }
                                        $account = Account::find($accountId);
                                        if (!$account) {
                                            return null;
                                        }
                                        
                                        $balance = number_format($account->balance, 2);
                                        // Link to Account Edit Page
                                        $url = AccountResource::getUrl('edit', ['record' => $accountId]);

                                        return new \Illuminate\Support\HtmlString(
                                            "<a href='{$url}' style='color: #d97706; font-weight: bold;' target='_blank'>Rp {$balance}</a>"
                                        );
                                    })
                                    ->hidden(fn ($get) => !$get('account_id'))
                                    ->columnSpanFull(),
                                TextInput::make('debit')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('Rp')
                                    ->columnSpan(1),
                                TextInput::make('credit')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('Rp')
                                    ->columnSpan(1),
                            ])
                            ->columns(2)
                            ->defaultItems(2)
                            ->addActionLabel('Add Item'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')->date()->sortable(),
                TextColumn::make('reference_number')->searchable(),
                TextColumn::make('description')->limit(50)->searchable(),
                TextColumn::make('items_count')->counts('items')->label('Items'),
                TextColumn::make('total_debit')
                    ->state(fn (JournalEntry $record) => $record->items->sum('debit'))
                    ->money('IDR'),
                TextColumn::make('total_credit')
                    ->state(fn (JournalEntry $record) => $record->items->sum('credit'))
                    ->money('IDR'),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('account')
                    ->label('Filter by Account')
                    ->searchable()
                    ->options(Account::query()->orderBy('code')->get()->mapWithKeys(fn ($account) => [$account->id => "{$account->code} - {$account->name}"]))
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('items', function ($q) use ($data) {
                                $q->where('account_id', $data['value']);
                            });
                        }
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournalEntries::route('/'),
            'create' => Pages\CreateJournalEntry::route('/create'),
            'edit' => Pages\EditJournalEntry::route('/{record}/edit'),
        ];
    }
}

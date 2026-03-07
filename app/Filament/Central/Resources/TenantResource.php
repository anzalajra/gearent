<?php

namespace App\Filament\Central\Resources;

use App\Enums\TenantFeature;
use App\Filament\Central\Resources\TenantResource\Pages;
use App\Models\Domain;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use UnitEnum;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|UnitEnum|null $navigationGroup = 'Tenant Management';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tenant')
                    ->tabs([
                        Tab::make('Business Information')
                            ->icon('heroicon-o-building-storefront')
                            ->schema([
                                Section::make('Tenant Identity')
                                    ->schema([
                                        TextInput::make('id')
                                            ->label('Tenant ID')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255)
                                            ->alphaDash()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn ($state, callable $set) => $set('id', Str::slug($state)))
                                            ->helperText('Unique identifier for the tenant (used for database name)')
                                            ->disabled(fn ($record) => $record !== null),

                                        TextInput::make('name')
                                            ->label('Company Name')
                                            ->required()
                                            ->maxLength(255),

                                        TextInput::make('email')
                                            ->label('Contact Email')
                                            ->email()
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->columns(2),

                                Section::make('Domains')
                                    ->schema([
                                        Repeater::make('domains')
                                            ->relationship()
                                            ->schema([
                                                TextInput::make('domain')
                                                    ->label('Domain')
                                                    ->required()
                                                    ->unique(table: Domain::class, column: 'domain', ignoreRecord: true)
                                                    ->maxLength(255)
                                                    ->helperText('e.g., tenant1.example.com'),
                                            ])
                                            ->columns(1)
                                            ->addActionLabel('Add Domain')
                                            ->defaultItems(0)
                                            ->reorderable(false),
                                    ]),
                            ]),

                        Tab::make('Owner Profile')
                            ->icon('heroicon-o-user-circle')
                            ->schema([
                                Section::make('System Admin User')
                                    ->description('The primary administrator of this tenant.')
                                    ->schema([
                                        Placeholder::make('owner_info')
                                            ->label('')
                                            ->content(function ($record) {
                                                if (! $record) {
                                                    return 'Save the tenant first to view owner profile.';
                                                }

                                                try {
                                                    $dbName = 'tenant_'.$record->id;
                                                    $user = DB::connection('central')
                                                        ->getDoctrineConnection()
                                                        ->getSchemaManager();

                                                    // Query the tenant database directly
                                                    $tenantDb = config('database.connections.tenant');
                                                    $tenantDb['database'] = $dbName;
                                                    config(['database.connections.tenant_lookup' => $tenantDb]);
                                                    DB::purge('tenant_lookup');

                                                    $user = DB::connection('tenant_lookup')
                                                        ->table('users')
                                                        ->where('is_system_admin', true)
                                                        ->first(['name', 'email', 'created_at']);

                                                    DB::purge('tenant_lookup');

                                                    if (! $user) {
                                                        return new HtmlString('<p class="text-gray-500">No system admin user found.</p>');
                                                    }

                                                    return new HtmlString(
                                                        '<div class="space-y-2">'.
                                                        '<p><strong>Name:</strong> '.e($user->name).'</p>'.
                                                        '<p><strong>Email:</strong> '.e($user->email).'</p>'.
                                                        '<p><strong>Created:</strong> '.e($user->created_at).'</p>'.
                                                        '</div>'
                                                    );
                                                } catch (\Throwable $e) {
                                                    return new HtmlString('<p class="text-danger-500">Unable to read tenant database: '.e($e->getMessage()).'</p>');
                                                }
                                            })
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make('Subscription & Status')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Section::make('Subscription')
                                    ->schema([
                                        Select::make('subscription_plan_id')
                                            ->label('Subscription Plan')
                                            ->relationship('subscriptionPlan', 'name')
                                            ->options(SubscriptionPlan::active()->pluck('name', 'id'))
                                            ->searchable()
                                            ->preload()
                                            ->nullable(),

                                        Select::make('status')
                                            ->label('Status')
                                            ->options([
                                                'trial' => 'Trial',
                                                'active' => 'Active',
                                                'inactive' => 'Inactive',
                                                'suspended' => 'Suspended',
                                            ])
                                            ->default('trial')
                                            ->required(),

                                        DateTimePicker::make('trial_ends_at')
                                            ->label('Trial Ends At')
                                            ->nullable(),

                                        DateTimePicker::make('subscription_ends_at')
                                            ->label('Subscription Ends At')
                                            ->nullable(),
                                    ])
                                    ->columns(2),

                                Section::make('Feature Overrides')
                                    ->description('Override fitur bawaan dari Subscription Plan. Biarkan kosong untuk mengikuti pengaturan plan.')
                                    ->schema([
                                        Repeater::make('feature_overrides_form')
                                            ->label('')
                                            ->schema([
                                                Select::make('feature')
                                                    ->label('Fitur')
                                                    ->options(TenantFeature::toOptions())
                                                    ->required()
                                                    ->distinct(),

                                                Toggle::make('enabled')
                                                    ->label('Aktif')
                                                    ->default(true),
                                            ])
                                            ->columns(2)
                                            ->addActionLabel('Tambah Override')
                                            ->defaultItems(0)
                                            ->reorderable(false),
                                    ])
                                    ->collapsed(),

                                Section::make('Additional Data')
                                    ->schema([
                                        KeyValue::make('data')
                                            ->label('Custom Data')
                                            ->keyLabel('Key')
                                            ->valueLabel('Value')
                                            ->addActionLabel('Add Data')
                                            ->nullable(),
                                    ])
                                    ->collapsed(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Company')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('subscriptionPlan.name')
                    ->label('Plan')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trial' => 'warning',
                        'inactive' => 'gray',
                        'suspended' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('domains.domain')
                    ->label('Domains')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList(),

                Tables\Columns\TextColumn::make('subscription_ends_at')
                    ->label('Expires')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'trial' => 'Trial',
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'suspended' => 'Suspended',
                    ]),

                Tables\Filters\SelectFilter::make('subscription_plan_id')
                    ->label('Plan')
                    ->relationship('subscriptionPlan', 'name'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('impersonate')
                        ->label('Access Tenant')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->color('info')
                        ->visible(fn (Tenant $record): bool => $record->domains->isNotEmpty())
                        ->url(fn (Tenant $record): string => route('central.impersonate', $record))
                        ->openUrlInNewTab(),
                    Action::make('suspend')
                        ->label('Suspend')
                        ->icon('heroicon-o-pause-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn (Tenant $record) => $record->update(['status' => 'suspended']))
                        ->visible(fn (Tenant $record): bool => $record->status !== 'suspended'),
                    Action::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-play-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Tenant $record) => $record->update(['status' => 'active']))
                        ->visible(fn (Tenant $record): bool => $record->status === 'suspended'),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('suspend_selected')
                        ->label('Suspend Selected')
                        ->icon('heroicon-o-pause-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => 'suspended'])),
                ]),
            ]);
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
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'view' => Pages\ViewTenant::route('/{record}'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}

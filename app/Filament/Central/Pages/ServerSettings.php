<?php

namespace App\Filament\Central\Pages;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use BackedEnum;
use UnitEnum;

class ServerSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-server-stack';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.central.pages.server-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'app_name' => config('app.name'),
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'app_url' => config('app.url'),
            'db_connection' => config('database.default'),
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'queue_connection' => config('queue.default'),
            'mail_mailer' => config('mail.default'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Application Settings')
                    ->description('Read-only overview of current application configuration')
                    ->schema([
                        TextInput::make('app_name')
                            ->label('Application Name')
                            ->disabled(),

                        TextInput::make('app_env')
                            ->label('Environment')
                            ->disabled(),

                        Toggle::make('app_debug')
                            ->label('Debug Mode')
                            ->disabled(),

                        TextInput::make('app_url')
                            ->label('Application URL')
                            ->disabled(),
                    ])
                    ->columns(2),

                Section::make('Database & Cache')
                    ->schema([
                        TextInput::make('db_connection')
                            ->label('Database Driver')
                            ->disabled(),

                        TextInput::make('cache_driver')
                            ->label('Cache Driver')
                            ->disabled(),

                        TextInput::make('session_driver')
                            ->label('Session Driver')
                            ->disabled(),

                        TextInput::make('queue_connection')
                            ->label('Queue Driver')
                            ->disabled(),
                    ])
                    ->columns(2),

                Section::make('Mail Configuration')
                    ->schema([
                        TextInput::make('mail_mailer')
                            ->label('Mail Driver')
                            ->disabled(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clearCache')
                ->label('Clear Cache')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    Artisan::call('cache:clear');
                    Artisan::call('config:clear');
                    Artisan::call('view:clear');
                    Artisan::call('route:clear');

                    Notification::make()
                        ->success()
                        ->title('Cache Cleared')
                        ->body('All caches have been cleared successfully.')
                        ->send();
                }),

            Action::make('optimizeApp')
                ->label('Optimize')
                ->icon('heroicon-o-bolt')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    Artisan::call('optimize');

                    Notification::make()
                        ->success()
                        ->title('Application Optimized')
                        ->body('Application has been optimized for production.')
                        ->send();
                }),
        ];
    }
}

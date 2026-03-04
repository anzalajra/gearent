<?php

declare(strict_types=1);

namespace App\Filament\Central\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Services\Storage\R2StorageService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use BackedEnum;
use UnitEnum;

class R2StorageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cloud';
    protected static string|UnitEnum|null $navigationGroup = 'System';
    protected static ?string $navigationLabel = 'R2 Storage';
    protected static ?string $title = 'Cloudflare R2 Storage Settings';
    protected static ?int $navigationSort = 100;
    protected string $view = 'filament.central.pages.r2-storage-settings';

    public ?array $data = [];
    public array $healthInfo = [];
    public array $storageStats = [];
    public bool $isConfigured = false;

    public function mount(): void
    {
        $service = app(R2StorageService::class);
        
        $this->isConfigured = $service->isConfigured();
        
        // Get current configuration for display
        $config = $service->getConfiguration();
        
        $this->form->fill([
            'access_key_id' => env('CLOUDFLARE_R2_ACCESS_KEY_ID', ''),
            'secret_access_key' => '',
            'bucket' => env('CLOUDFLARE_R2_BUCKET', ''),
            'endpoint' => env('CLOUDFLARE_R2_ENDPOINT', ''),
            'public_url' => env('CLOUDFLARE_R2_URL', ''),
            'region' => env('CLOUDFLARE_R2_REGION', 'auto'),
            'use_path_style_endpoint' => env('CLOUDFLARE_R2_USE_PATH_STYLE_ENDPOINT', true),
        ]);

        $this->loadHealthAndStats();
    }

    public function loadHealthAndStats(): void
    {
        $service = app(R2StorageService::class);
        
        if ($this->isConfigured) {
            $this->healthInfo = $service->getHealthInfo();
            $this->storageStats = $service->getStorageStats();
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('API Credentials')
                    ->description('Cloudflare R2 API credentials dari dashboard Cloudflare.')
                    ->schema([
                        TextInput::make('access_key_id')
                            ->label('Access Key ID')
                            ->required()
                            ->placeholder('Masukkan R2 Access Key ID'),
                        TextInput::make('secret_access_key')
                            ->label('Secret Access Key')
                            ->password()
                            ->revealable()
                            ->placeholder('Masukkan Secret Access Key (kosongkan jika tidak ingin mengubah)'),
                    ])
                    ->columns(2),

                Section::make('Bucket Configuration')
                    ->description('Konfigurasi bucket R2 untuk penyimpanan file.')
                    ->schema([
                        TextInput::make('bucket')
                            ->label('Bucket Name')
                            ->required()
                            ->placeholder('nama-bucket-anda'),
                        TextInput::make('endpoint')
                            ->label('R2 Endpoint URL')
                            ->required()
                            ->placeholder('https://ACCOUNT_ID.r2.cloudflarestorage.com')
                            ->helperText('Format: https://[ACCOUNT_ID].r2.cloudflarestorage.com'),
                        TextInput::make('public_url')
                            ->label('Public URL (Optional)')
                            ->placeholder('https://files.domain.com')
                            ->helperText('URL publik jika menggunakan custom domain atau R2.dev subdomain'),
                        TextInput::make('region')
                            ->label('Region')
                            ->default('auto')
                            ->helperText('Gunakan "auto" untuk Cloudflare R2'),
                        Toggle::make('use_path_style_endpoint')
                            ->label('Use Path Style Endpoint')
                            ->default(true)
                            ->helperText('Aktifkan untuk kompatibilitas R2'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Konfigurasi')
                ->submit('save'),
            Action::make('test')
                ->label('Test Koneksi')
                ->color('info')
                ->action('testConnection'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        try {
            $envPath = base_path('.env');
            $envContent = File::get($envPath);

            // Update or add env variables
            $envVariables = [
                'CLOUDFLARE_R2_ACCESS_KEY_ID' => $data['access_key_id'],
                'CLOUDFLARE_R2_BUCKET' => $data['bucket'],
                'CLOUDFLARE_R2_ENDPOINT' => $data['endpoint'],
                'CLOUDFLARE_R2_URL' => $data['public_url'] ?? '',
                'CLOUDFLARE_R2_REGION' => $data['region'] ?? 'auto',
                'CLOUDFLARE_R2_USE_PATH_STYLE_ENDPOINT' => $data['use_path_style_endpoint'] ? 'true' : 'false',
            ];

            // Only update secret if provided
            if (!empty($data['secret_access_key'])) {
                $envVariables['CLOUDFLARE_R2_SECRET_ACCESS_KEY'] = $data['secret_access_key'];
            }

            foreach ($envVariables as $key => $value) {
                $envContent = $this->setEnvValue($envContent, $key, $value);
            }

            File::put($envPath, $envContent);

            // Clear config cache
            Cache::forget('config');
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }

            Notification::make()
                ->title('Konfigurasi R2 berhasil disimpan!')
                ->body('Restart aplikasi mungkin diperlukan untuk menerapkan perubahan.')
                ->success()
                ->send();

            $this->isConfigured = true;
            $this->loadHealthAndStats();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal menyimpan konfigurasi')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function testConnection(): void
    {
        $service = app(R2StorageService::class);
        $result = $service->testConnection();

        if ($result['success']) {
            Notification::make()
                ->title('Koneksi Berhasil!')
                ->body($result['message'])
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Koneksi Gagal')
                ->body($result['message'])
                ->danger()
                ->send();
        }

        $this->loadHealthAndStats();
    }

    public function refreshStats(): void
    {
        $this->loadHealthAndStats();
        
        Notification::make()
            ->title('Statistik diperbarui')
            ->success()
            ->send();
    }

    protected function setEnvValue(string $envContent, string $key, string $value): string
    {
        $value = str_replace('"', '\\"', $value);
        
        // Check if key exists
        if (preg_match("/^{$key}=.*/m", $envContent)) {
            // Update existing key
            return preg_replace(
                "/^{$key}=.*/m",
                "{$key}=\"{$value}\"",
                $envContent
            );
        }
        
        // Add new key
        return $envContent . "\n{$key}=\"{$value}\"";
    }

    public static function getNavigationBadge(): ?string
    {
        $service = app(R2StorageService::class);
        return $service->isConfigured() ? null : '!';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}

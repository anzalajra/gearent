# Multi-Tenant Storage dengan Cloudflare R2

Dokumentasi ini menjelaskan cara menggunakan sistem penyimpanan file multi-tenant dengan Cloudflare R2 (S3-compatible storage).

## Arsitektur

Sistem ini menggunakan prefix tenant ID untuk memisahkan file antar tenant:

```
bucket-name/
├── central/                    # File untuk admin central
│   ├── settings/
│   └── backups/
├── tenant_toko-a/              # File untuk tenant toko-a
│   ├── products/
│   ├── documents/
│   └── invoices/
├── tenant_toko-b/              # File untuk tenant toko-b
│   ├── products/
│   ├── documents/
│   └── invoices/
└── ...
```

## Konfigurasi

### 1. Environment Variables

Tambahkan konfigurasi berikut ke file `.env`:

```env
CLOUDFLARE_R2_ACCESS_KEY_ID=your-access-key-id
CLOUDFLARE_R2_SECRET_ACCESS_KEY=your-secret-access-key
CLOUDFLARE_R2_BUCKET=your-bucket-name
CLOUDFLARE_R2_ENDPOINT=https://ACCOUNT_ID.r2.cloudflarestorage.com
CLOUDFLARE_R2_URL=https://files.yourdomain.com
CLOUDFLARE_R2_REGION=auto
CLOUDFLARE_R2_USE_PATH_STYLE_ENDPOINT=true
```

Atau konfigurasi melalui Central Admin di `/central/r2-storage-settings`.

### 2. Mendapatkan Kredensial R2

1. Login ke [Cloudflare Dashboard](https://dash.cloudflare.com)
2. Pilih **R2 Object Storage**
3. Buat bucket baru atau gunakan yang sudah ada
4. Klik **Manage R2 API Tokens** > **Create API Token**
5. Pilih permissions: **Object Read & Write**
6. Salin **Access Key ID** dan **Secret Access Key**
7. Endpoint URL format: `https://[ACCOUNT_ID].r2.cloudflarestorage.com`

## Penggunaan di Filament

### Metode 1: Menggunakan Macro `tenantDirectory`

```php
use Filament\Forms\Components\FileUpload;

public static function form(Form $form): Form
{
    return $form->schema([
        FileUpload::make('image')
            ->tenantDirectory('products')  // Auto prefix dengan tenant ID
            ->image()
            ->imageEditor(),
    ]);
}
```

### Metode 2: Menggunakan Trait `HasTenantStorage`

```php
use App\Filament\Concerns\HasTenantStorage;

class ProductResource extends Resource
{
    use HasTenantStorage;

    public static function form(Form $form): Form
    {
        return $form->schema([
            static::tenantFileUpload('image', 'products'),
            static::tenantDocumentUpload('document', 'documents'),
            static::tenantMultipleFileUpload('gallery', 'gallery', 5),
        ]);
    }
}
```

### Metode 3: Menggunakan TenantFileUpload Component

```php
use App\Filament\Components\TenantFileUpload;

public static function form(Form $form): Form
{
    return $form->schema([
        TenantFileUpload::make('image')
            ->tenantDirectory('products')
            ->image()
            ->imageEditor(),
    ]);
}
```

### Metode 4: Manual dengan TenantStorageService

```php
use App\Services\Storage\TenantStorageService;

// Di Controller atau Service
$storageService = app(TenantStorageService::class);

// Upload file
$path = $storageService->store($uploadedFile, 'products', 'custom-filename.jpg');

// Get URL
$url = $storageService->url($path);

// Get temporary signed URL (60 menit)
$temporaryUrl = $storageService->temporaryUrl($path, now()->addMinutes(60));

// Hapus file
$storageService->delete($path);

// List files
$files = $storageService->files('products');
```

## Central Admin Features

### R2 Storage Settings (`/central/r2-storage-settings`)

- Konfigurasi kredensial R2
- Test koneksi
- Lihat statistik penyimpanan
- Health check

### R2 File Browser (`/central/r2-file-browser`)

- Browse semua file di bucket
- Lihat penyimpanan per tenant
- Download file
- Hapus file/folder
- Navigasi folder

## Service Classes

### TenantStorageService

Service utama untuk operasi file dalam konteks tenant.

```php
// Inject via constructor atau resolve
$service = app(TenantStorageService::class);

// Mengubah tenant (untuk admin central)
$service->forTenant('tenant-id');

// Upload
$service->store($file, 'directory');
$service->put('path/to/file.txt', $contents);

// Read
$service->get('path/to/file.txt');
$service->exists('path/to/file.txt');

// Delete
$service->delete('path/to/file.txt');

// URL
$service->url('path/to/file.txt');
$service->temporaryUrl('path/to/file.txt', now()->addHour());

// List
$service->files('directory');
$service->directories('directory');
```

### R2StorageService

Service untuk admin central mengelola seluruh bucket.

```php
$service = app(R2StorageService::class);

// Connection
$service->testConnection();
$service->getHealthInfo();
$service->isConfigured();

// Statistics
$service->getStorageStats();
$service->getTenantStorageStats();

// Browse
$service->listFiles($directory);
$service->listDirectories($directory);
$service->listAll($directory);

// Operations
$service->deleteFile($path);
$service->deleteDirectory($path);
$service->createDirectory($path);
$service->getTemporaryUrl($path, $minutes);
```

## Migrasi dari Local Storage

Untuk memigrasikan file yang sudah ada dari local storage ke R2:

```php
use Illuminate\Support\Facades\Storage;
use App\Models\Tenant;

// Untuk setiap tenant
Tenant::all()->each(function ($tenant) {
    $tenantId = $tenant->id;
    $localPath = storage_path("app/tenant{$tenantId}");
    
    if (!is_dir($localPath)) {
        return;
    }
    
    // Upload semua file
    $files = Storage::disk('local')->allFiles("tenant{$tenantId}");
    
    foreach ($files as $file) {
        $contents = Storage::disk('local')->get($file);
        $newPath = "tenant_{$tenantId}/" . str_replace("tenant{$tenantId}/", '', $file);
        Storage::disk('r2')->put($newPath, $contents);
    }
});
```

## Flow Diagram

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  Filament Form  │────▶│ TenantStorage    │────▶│  Cloudflare R2  │
│  FileUpload     │     │ Service          │     │  Bucket         │
└─────────────────┘     └──────────────────┘     └─────────────────┘
                               │
                               │ getTenantPrefix()
                               ▼
                        ┌──────────────────┐
                        │ tenant_[ID]/path │
                        └──────────────────┘
```

## Troubleshooting

### Error: Unable to connect to R2

1. Periksa kredensial di `.env`
2. Pastikan endpoint URL benar (gunakan Account ID)
3. Periksa bucket permissions

### Error: Access Denied

1. Pastikan API Token memiliki permission **Object Read & Write**
2. Periksa bucket name benar

### File tidak ter-upload

1. Periksa `php.ini` untuk `upload_max_filesize` dan `post_max_size`
2. Periksa storage disk di config: `config('filesystems.disks.r2')`

### URL file tidak bisa diakses

Untuk file private, gunakan `temporaryUrl()` untuk generate signed URL:

```php
$url = Storage::disk('r2')->temporaryUrl($path, now()->addMinutes(30));
```

Atau setup R2 public bucket / custom domain untuk public access.

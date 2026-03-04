# Cloudflare R2 Storage Structure - Zewalo

## Overview

Sistem penyimpanan Zewalo menggunakan Cloudflare R2 dengan pendekatan multi-tenant. Setiap tenant memiliki folder terpisah dengan prefix `tenant_{tenant_id}`, sementara file sistem central disimpan di folder `central/`.

## Folder Structure

```
bucket-name/
│
├── central/                              # Central admin files
│   ├── backups/                          # System backups
│   │   ├── database/                     # Database backup files
│   │   └── configs/                      # Configuration backups
│   ├── assets/                           # Shared system assets
│   │   ├── logos/                        # System logos
│   │   ├── templates/                    # Email/document templates
│   │   └── defaults/                     # Default images
│   ├── reports/                          # System reports
│   └── exports/                          # Exported data files
│
├── tenant_{tenant-id}/                   # Tenant-specific folder
│   │                                     # Example: tenant_toko-a/
│   │
│   ├── products/                         # Product images
│   │   └── {filename}.{ext}              # Product photos
│   │
│   ├── brands/                           # Brand logos
│   │   └── {filename}.{ext}              # Brand logo images
│   │
│   ├── categories/                       # Category images
│   │   └── {filename}.{ext}              # Category images
│   │
│   ├── customer-documents/               # Customer verification docs
│   │   └── {customer-id}/                # Per-customer folder
│   │       └── {filename}.{ext}          # KTP, SIM, NPWP, etc.
│   │
│   ├── finance/                          # Finance-related files
│   │   ├── bills/                        # Bill proof documents
│   │   │   └── {filename}.{ext}
│   │   ├── expenses/                     # Expense proof documents
│   │   │   └── {filename}.{ext}
│   │   └── transactions/                 # Transaction proof documents
│   │       └── {filename}.{ext}
│   │
│   ├── media/                            # General media library
│   │   ├── images/                       # General images
│   │   ├── videos/                       # Video files
│   │   └── documents/                    # Document files (PDF, etc.)
│   │
│   ├── settings/                         # Tenant settings (PUBLIC disk)
│   │   ├── logo/                         # Store logo (site_logo)
│   │   └── branding/                     # Branding assets
│   │
│   ├── posts/                            # Blog/Post images
│   │   ├── featured/                     # Featured images
│   │   └── content/                      # Content images
│   │
│   ├── banners/                          # Banner images
│   │   ├── homepage/                     # Homepage banners
│   │   └── promotions/                   # Promotional banners
│   │
│   └── exports/                          # Tenant exports
│       ├── reports/                      # Report exports
│       └── data/                         # Data exports
│
├── tenant_toko-b/                        # Another tenant
│   └── ... (same structure)
│
└── tenant_toko-c/                        # Another tenant
    └── ... (same structure)
```

## File Upload Mapping

Berikut adalah mapping lengkap dari setiap form upload ke folder bucket:

| Resource / Form | Field | Bucket Path |
|-----------------|-------|-------------|
| **ProductForm** | `image` | `tenant_{id}/products/` |
| **BrandForm** | `logo` | `tenant_{id}/brands/` |
| **CategoryForm** | `image` | `tenant_{id}/categories/` |
| **ProductSetup** (Brands) | `logo` | `tenant_{id}/brands/` |
| **ProductSetup** (Categories) | `image` | `tenant_{id}/categories/` |
| **BillResource** | `proof_document` | `tenant_{id}/finance/bills/` |
| **ExpenseResource** | `proof_document` | `tenant_{id}/finance/expenses/` |
| **FinanceTransactionForm** | `proof_document` | `tenant_{id}/finance/transactions/` |
| **CustomerDocument** | (upload) | `tenant_{id}/customer-documents/{customer_id}/` |
| **GeneralSettings** | `site_logo` | `public/settings/` (LOCAL) |
| **DocumentLayoutSettings** | `doc_logo` | `public/settings/` (LOCAL) |
| **FinanceSettings** | `digital_certificate` | `local/certificates/` (LOCAL - for security) |

## Path Conventions

### Tenant Paths
```
tenant_{tenant_id}/{category}/{filename}
```

**Examples:**
- `tenant_toko-a/products/product-1.jpg`
- `tenant_toko-a/brands/sony-logo.png`
- `tenant_toko-a/categories/electronics.jpg`
- `tenant_toko-a/customer-documents/123/ktp.pdf`
- `tenant_toko-a/finance/bills/proof-001.jpg`
- `tenant_toko-a/finance/expenses/receipt-001.jpg`
- `tenant_toko-a/finance/transactions/transfer-001.jpg`

### Central Paths
```
central/{category}/{subcategory}/{filename}
```

**Examples:**
- `central/assets/logos/zewalo-logo.png`
- `central/backups/database/backup-2024-01-01.sql.gz`
- `central/reports/monthly/report-january.pdf`

## File Naming Conventions

1. **Products**: `{slug}-{uuid}.{ext}` → `produk-abc-550e8400.jpg`
2. **Brands**: `{brand-slug}-{uuid}.{ext}` → `sony-550e8400.png`
3. **Customer Docs**: `{doctype}-{uuid}.{ext}` → `ktp-550e8400.pdf`
4. **Finance Proofs**: `{type}-{uuid}.{ext}` → `bill-550e8400.jpg`
5. **General**: Use lowercase, hyphens instead of spaces

## Usage Examples

### Storing Files (with TenantStorageService)

```php
use App\Services\Storage\TenantStorageService;

// Inject service
public function __construct(
    protected TenantStorageService $storage
) {}

// Store product image
$path = $this->storage->store($file, 'products', 'product-abc.jpg');
// Result: tenant_toko-a/products/product-abc.jpg

// Store customer document
$path = $this->storage->store($file, 'customer-documents/123');
// Result: tenant_toko-a/customer-documents/123/{filename}

// Get temporary URL (presigned)
$url = $this->storage->temporaryUrl('products/product-abc.jpg', now()->addHour());

// Delete file
$this->storage->delete('products/product-abc.jpg');
```

### Filament Form Integration (Using tenantDirectory Macro)

```php
use Filament\Forms\Components\FileUpload;

// RECOMMENDED: Use tenantDirectory macro (auto sets disk, visibility, prefix)
FileUpload::make('image')
    ->image()
    ->tenantDirectory('products');
// Result: tenant_{current_tenant}/products/{filename}

// For brand logos
FileUpload::make('logo')
    ->image()
    ->tenantDirectory('brands');
// Result: tenant_{current_tenant}/brands/{filename}

// For finance documents
FileUpload::make('proof_document')
    ->tenantDirectory('finance/bills');
// Result: tenant_{current_tenant}/finance/bills/{filename}
```

### Central Admin Operations (with R2StorageService)

```php
use App\Services\Storage\R2StorageService;

// Get tenant storage stats
$r2Service = new R2StorageService();
$stats = $r2Service->getTenantStorageStats();

// List files in tenant directory
$files = $r2Service->listFiles('tenant_toko-a/products');

// Get presigned URL
$url = $r2Service->getTemporaryUrl(
    'tenant_toko-a/products/image.jpg',
    60 // minutes
);
```

## Storage Limits & Best Practices

### Recommended Limits per Tenant
- **Total Storage**: Define in tenant plans (e.g., 1GB, 5GB, 10GB)
- **Single File Size**: Max 100MB (configurable)
- **Images**: Compress before upload (WebP recommended)

### Best Practices
1. Always use `TenantStorageService` for tenant operations (auto-prefixes)
2. Use presigned URLs instead of public URLs for security
3. Implement image compression before upload
4. Set lifecycle rules in R2 Dashboard for temp files (auto-cleanup after 7 days)
5. Use thumbnails for listing, original for detail view

## Security Considerations

1. **Never expose bucket credentials** - Use presigned URLs
2. **Set short expiration** - 1 hour for downloads, 5-15 minutes for uploads
3. **Validate file types** - Restrict to allowed MIME types
4. **Validate tenant access** - Ensure users can only access their tenant's files
5. **Configure CORS** - In Cloudflare Dashboard for browser uploads

## CORS Configuration (Cloudflare Dashboard)

Configure in R2 Bucket Settings → CORS Policy:

```json
[
  {
    "AllowedOrigins": ["https://yourdomain.com", "http://localhost:8000"],
    "AllowedMethods": ["GET", "PUT", "DELETE", "HEAD"],
    "AllowedHeaders": ["Content-Type", "Content-Length", "x-amz-*"],
    "ExposeHeaders": ["ETag", "Content-Length"],
    "MaxAgeSeconds": 3600
  }
]
```

## Environment Variables

```env
CLOUDFLARE_R2_ACCESS_KEY_ID=your-access-key-id
CLOUDFLARE_R2_SECRET_ACCESS_KEY=your-secret-access-key
CLOUDFLARE_R2_BUCKET=your-bucket-name
CLOUDFLARE_R2_ENDPOINT=https://your-account-id.r2.cloudflarestorage.com
CLOUDFLARE_R2_URL=https://your-bucket.your-domain.com
CLOUDFLARE_R2_REGION=auto
CLOUDFLARE_R2_USE_PATH_STYLE_ENDPOINT=true
```

## Related Files

- **TenantStorageService**: `app/Services/Storage/TenantStorageService.php`
- **R2StorageService**: `app/Services/Storage/R2StorageService.php`
- **Filesystem Config**: `config/filesystems.php`
- **R2 Settings Page**: `app/Filament/Central/Pages/R2StorageSettings.php`
- **R2 File Browser**: `app/Filament/Central/Pages/R2FileBrowser.php`

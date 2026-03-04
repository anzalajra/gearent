# Panduan Multi-Tenancy dengan Filament Shield di Zewalo

## Ringkasan Arsitektur

```
┌─────────────────────────────────────────────────────────────────┐
│                     CENTRAL DATABASE (zewalo)                   │
├─────────────────────────────────────────────────────────────────┤
│  - tenants (daftar tenant)                                      │
│  - domains (domain untuk setiap tenant)                         │
│  - users (admin central/superadmin - OPSIONAL)                  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                  TENANT DATABASE (tenant_{id})                  │
├─────────────────────────────────────────────────────────────────┤
│  - users (user per tenant)                                      │
│  - roles (roles per tenant)                                     │
│  - permissions (permissions per tenant)                         │
│  - model_has_roles, model_has_permissions, role_has_permissions │
│  - products, rentals, customers, invoices, dll...               │
└─────────────────────────────────────────────────────────────────┘
```

## Struktur Migrasi

```
database/migrations/
├── 2019_09_15_000010_create_tenants_table.php      # Central
├── 2019_09_15_000020_create_domains_table.php      # Central
├── central/                                         # Backup/Central only
│   ├── 0001_01_01_000000_create_users_table.php    
│   ├── 0001_01_01_000001_create_cache_table.php
│   └── 0001_01_01_000002_create_jobs_table.php
└── tenant/                                          # Per-tenant
    ├── 0001_01_01_000000_create_users_table.php    # Users per tenant
    ├── 0001_01_01_000001_create_cache_table.php
    ├── 0001_01_01_000002_create_jobs_table.php
    ├── 2026_02_06_181140_create_permission_tables.php  # Shield/Spatie
    ├── 2026_01_30_*_create_products_table.php
    └── ... (semua tabel aplikasi)
```

## Langkah Konfigurasi

### 1. Migrasi Central Database

```bash
# Jalankan migrasi untuk tabel tenants & domains
docker compose exec app php artisan migrate --path=database/migrations
```

### 2. Membuat Tenant Baru

```bash
docker compose exec app php artisan tinker
```

```php
// Di dalam tinker:
$tenant = App\Models\Tenant::create(['id' => 'acme']);
$tenant->domains()->create(['domain' => 'acme.localhost']);

// Atau dengan format subdomain
$tenant->domains()->create(['domain' => 'acme']);
```

### 3. Migrasi Tenant Database

Otomatis dijalankan saat tenant dibuat (via TenancyServiceProvider).
Atau manual:

```bash
docker compose exec app php artisan tenants:migrate
```

### 4. Setup Filament Shield di Tenant

```bash
# Untuk setiap tenant, jalankan:
docker compose exec app php artisan tenants:run shield:install --tenants=acme

# Atau untuk semua tenant:
docker compose exec app php artisan tenants:run shield:install
```

### 5. Generate Permissions

```bash
# Generate permissions untuk resources yang ada
docker compose exec app php artisan tenants:run shield:generate --all --tenants=acme
```

## Konfigurasi yang Sudah Dilakukan

### config/tenancy.php
- `central_connection` → 'central'
- `template_tenant_connection` → 'pgsql'
- `migration_parameters.--path` → database/migrations/tenant

### config/database.php
- Connection `central` untuk landlord database
- Connection `tenant` sebagai template (database: null, diset dinamis)

### config/filament-shield.php
Untuk multi-tenancy, pastikan:
```php
'tenant_model' => \App\Models\Tenant::class,
```

## Cara Kerja Filament Shield di Tenant Context

1. **Request masuk** → Domain identification middleware mendeteksi tenant
2. **Tenancy diinisialisasi** → DatabaseTenancyBootstrapper switch connection default ke tenant
3. **Spatie Permission** → Otomatis membaca dari tenant database (karena default connection sudah di-switch)
4. **Filament Shield** → Resource RoleResource membaca dari tenant database

## Troubleshooting

### Permission tidak terload
```bash
# Clear cache permissions
docker compose exec app php artisan tenants:run permission:cache-reset
```

### Roles kosong di panel tenant
1. Pastikan migrasi permission_tables sudah ada di tenant/
2. Run: `php artisan tenants:migrate`
3. Run: `php artisan tenants:run shield:install`

### User tidak bisa login di tenant
1. Pastikan user dibuat di database tenant (bukan central)
2. Assign role: `$user->assignRole('super_admin')`

## Perintah Berguna

```bash
# List semua tenant
docker compose exec app php artisan tenant:list

# Jalankan command di semua tenant
docker compose exec app php artisan tenants:run <command>

# Jalankan command di tenant tertentu
docker compose exec app php artisan tenants:run <command> --tenants=acme

# Migrate fresh di tenant
docker compose exec app php artisan tenants:migrate-fresh

# Seed tenant database
docker compose exec app php artisan tenants:seed
```

## Membuat User Admin di Tenant

```php
// Via tinker
tenancy()->initialize('acme'); // Masuk ke context tenant

$user = App\Models\User::create([
    'name' => 'Admin ACME',
    'email' => 'admin@acme.com',
    'password' => bcrypt('password'),
]);

$user->assignRole('super_admin');
```

Atau buat seeder di `database/seeders/TenantSeeder.php` dan panggil saat tenant dibuat.

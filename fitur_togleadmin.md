# Panduan Langkah-demi-Langkah: Fitur Toggle & Akses Tenant di Central Admin

Dokumen ini berisi urutan langkah (step-by-step) untuk mengimplementasikan fitur akses tenant dan *feature toggles* berdasarkan rencana yang telah disetujui.

## Tahap 1: Persiapan Enum (Daftar Fitur)
1. **Buat file Enum baru**: Buat file `app/Enums/TenantFeature.php`.
2. **Definisikan fitur**: Isi Enum tersebut dengan daftar fitur yang tersedia:
   - `finance` (Finance + Settings + Tax)
   - `deliveries` (Deliveries)
   - `customer` (Customer)
   - `quotation_invoice` (Quotation + Invoice)
   - `promotion` (Promotion)
   - `inventory_qc` (Inventory Maintenance QC)
   - `inventory_warehouse` (Inventory Warehouse)
   - `product_unit` (Product Unit variable)
   - `storefront` (Tenant Storefront)
3. **Tambahkan Helper**: Beri method (misalnya `getLabel()`) untuk mengubah enum menjadi nama yang mudah dibaca (*human-readable*) agar bisa ditampilkan dengan rapi di Filament UI berupa Checkbox atau list.

## Tahap 2: Update Model & Database
1. **Update Model `Tenant.php`**:
   - Tambahkan method `hasFeature(TenantFeature $feature): bool` untuk mengecek apakah suatu fitur aktif bagi tenant tersebut.
   - Logika pengecekan: Pertama, periksa konfigurasi `feature_overrides` di dalam kolom `data` milik tenant. Jika fitur secara eksplisit di-override, gunakan nilai override tersebut. Jika tidak di-override, periksa daftar fitur bawaan dari relasi `SubscriptionPlan` milik tenant.
2. **Update Konfigurasi / Casts**:
   - Pastikan model `Tenant` dan `SubscriptionPlan` dapat menerima array list dari enum fitur dengan benar dalam databasenya tanpa error konversi.

## Tahap 3: Modifikasi Central Admin Panel (Filament)
1. **Update file `SubscriptionPlanResource.php`**:
   - Pada schema form pembuatan dan edit *Subscription Plan*, ubah bagian input "Features" dari yang sebelumnya menggunakan `Repeater` polos menjadi `CheckboxList`.
   - Hubungkan `options` pada CheckboxList tersebut ke `TenantFeature::class` yang telah dibuat pada tahap 1.
2. **Update file `TenantResource.php` (Sistem Feature Overrides)**:
   - Tambahkan *Section* baru di class tabel/form bernama "Feature Overrides".
   - Isi form tersebut dengan input berupa `CheckboxList` yang menampilkan seluruh daftar fitur enum.
   - Atur mekanisme save and load agar input ini tersimpan aman ke dalam mapping JSON/Array di `data['feature_overrides']`.
3. **Update file `TenantResource.php` (Tombol Akses Tenant Tersembunyi)**:
   - Pada method `table()`, cari elemen `Action::make('impersonate')` milik tombol "Access tenant".
   - Ubah logika URL-nya agar tidak hanya menembak URL tujuan, tapi men-generate tautan keamanan masuk *magic link* /*impersonate token* sekali pakai milik server.

## Tahap 4: Mengintegrasikan Login Otomatis (Impersonation)
1. **Konfigurasi Routing Impersonation**:
   - Siapkan route sentral yang bertugas men-generate token dan URL *redirect*. Tautkan metode package impersonate dari bawaannya `stancl/tenancy`. (Sesuai dokumentasi *User Impersonation* dari package tersebut).
2. **Konfigurasi Autologin dalam Aplikasi Admin Tenant**:
   - Siapkan middleware atau route penerima *magic token* yang langsung login-kan admin user utama (atau user pertama) tenant secara paksa (tanpa cek password).
   - Pastikan URL ini melakukan *immediate redirection* setelah otentikasi sukses menuju `/admin`.

## Tahap 5: Menyembunyikan dan Menampilkan Fitur di Tenant Admin Panel
1. **Terapkan Batasan di Setiap Resource Filament Tenant**:
   - Cari dan buka file Filament resources terkait, seperti `FinanceResource.php`, `DeliveryResource.php`, `CustomerResource.php`, dsb.
   - Overwrite method milik Filament `shouldRegisterNavigation()` dan `canAccess(Model $record)` untuk mengaitkan ke method model pada Tahap 2 -> `tenant()->hasFeature(TenantFeature::Finance)`.
   - Contoh hasil: Apabila fitur *Finance* mati, menu terkait *Finance* di sidebar akan langsung hilang dan halaman detail rekamannya tidak bisa diakses sama sekali (Forbidden 403 / 404).
2. **Menyembunyikan Storefront Tenant**:
   - Modifikasi sistem routing antarmuka toko tenant (biasanya pada middleware tenant atau *Storefront Controller*).
   - Apabila pemanggilan `tenant()->hasFeature(TenantFeature::Storefront)` menghasilkan *false*, buat tenant tersebut menampilkan *Error Page* respons 403/404, atau halaman konfirmasi peringatan spesifik: "Etalase toko tidak aktif".

## Tahap 6: Pengujian dan Verifikasi Akhir
1. Buka dan login ke **Central Admin Panel**.
2. Edit salah satu Data Paket (Subscription Plan) **"Plan A"** dan matikan 2 fitur, yaitu *Deliveries* dan *Finance*, sementara fitur *Customer* menyala.
3. Edit detail penyewa toko **"Tenant Z"** yang menggunakan Paket **"Plan A"**. Coba centang tab *Overrides* dengan mengaktifkan paksa *Deliveries* meskipun `Plan A` tidak mengijinkan.
4. Klik tombol **"Access Tenant"** di kolom navigasi Tenant Z, perhatikan flow sampai login masuk sendiri.
5. Periksa sidebar menu Admin dari toko **Tenant Z**: Seharusnya menu *Customer* dan *Deliveries* muncul (karena yang satu bawaan, yang satu kena override). Menu *Finance* harus tetap tersembunyi.

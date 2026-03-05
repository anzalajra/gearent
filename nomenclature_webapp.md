# Zewalo Nomenclature & Naming Convention

Dokumen ini berfungsi sebagai kamus (Glosarium) standar penamaan fitur, komponen, dan wilayah dalam aplikasi Zewalo. Dokumen ini dibuat agar Developer dan AI Assitant (seperti Claude, GPT, atau agent lainnya) memiliki kesepahaman yang presisi saat merujuk pada fitur tertentu, serta mengurangi risiko modifikasi file yang salah karena ambiguitas nama.

## 1. CENTRAL AREA (Domain Utama Zewalo)
*Bagian ini berjalan di domain utama (misal: `zewalo.com` atau `localhost`). Ini adalah pusat kontrol seluruh sistem (SaaS SaaS-nya).*

### 1.1. Central Homepage (Homepage Utama Zewalo)
* **Deskripsi:** Halaman pendaftaran (landing page/company profile) yang dilihat umum saat mengakses domain utama Zewalo. Berfungi untuk marketing, penjelasan fitur, dan harga paket bagi calon pemilik bisnis.
* **File Code Terkait (Contoh):**
    * `routes/web.php` (Route untuk domain central)
    * `resources/views/welcome.blade.php` (Atau blade view terkait landing page)
    * Controller yang menangani landing page (jika ada).

### 1.2. Tenant Registration Wizard (Register Tenant / Mendaftar Toko)
* **Deskripsi:** Alur pendaftaran multi-step bagi calon pemilik bisnis untuk membuat toko/sistem baru di Zewalo. Meliputi pengisian data akun pengguna, informasi toko, dan proses provisioning database serta subdomain.
* **File Code Terkait:**
    * `app/Livewire/RegisterTenant.php` (Logika komponen Livewire)
    * `resources/views/livewire/register-tenant.blade.php` (View komponen Livewire)

### 1.3. Central Admin Panel (Central Admin Zewalo / Superadmin Dashboard)
* **Deskripsi:** Dashboard manajemen utama untuk pemilik aplikasi Zewalo. Tempat untuk melihat daftar semua tenant, mengelola pengguna (secara global), dan menangani pengaturan platform.
* **File Code Terkait:**
    * `app/Providers/Filament/CentralPanelProvider.php` (Konfigurasi Provider Panel)
    * Direktori: `app/Filament/Central/*` (Berisi Resource pendaftaran fitur dashboard pusat, seperti `TenantResource`)

### 1.4. Central Admin Login (Login Central)
* **Deskripsi:** Halaman form login khusus agar Superadmin bisa masuk ke Central Admin Panel.
* **File Code Terkait:**
    * Pengaturan auth pada `CentralPanelProvider.php`
    * View login Filament pusat (di custom view `.blade.php` override Filament, jika dimodifikasi).

---

## 2. TENANT AREA (Subdomain / Domain Bisnis)
*Bagian ini berjalan di domain/subdomain khusus masing-masing toko (misal: `toko-budi.zewalo.com`). Semua data di area ini diatur pada database atau lingkup (scope) yang terisolir untuk Tenant tersebut.*

### 2.1. Tenant Routing / Subdomain (Routing Sub Domain Tenant)
* **Deskripsi:** Mekanisme identifikasi URL yang membedakan satu tenant dengan tenant yang lain.
* **File Code Terkait:**
    * `routes/tenant.php` (Routing yang hanya berlaku di dalam lingkup tenant khusus)
    * `app/Models/Tenant.php` (Model utama untuk entitas bisnis/Tenant)

### 2.2. Tenant Admin Panel (Admin Tenant / Backoffice Toko)
* **Deskripsi:** Dashboard CMS tempat pemilik bisnis (Tenant) atau kasir/staff mereka mengelola produk, penjualan, pengeluaran, karyawan, konfigurasi toko dan laporan mereka sendiri.
* **File Code Terkait:**
    * `app/Providers/Filament/AdminPanelProvider.php` (Konfigurasi Provider Panel internal Tenant)
    * Direktori: `app/Filament/Resources/*` (Atau direktori cluster terkait untuk menu di toko)

### 2.3. Tenant Admin Login (Login Admin Tenant)
* **Deskripsi:** Route atau halaman login form bagi pemilik toko atau staff toko untuk masuk mengelola Tenant Admin Panel mereka masing-masing.
* **File Code Terkait:**
    * `app/Livewire/TenantLogin.php` (Logika kustom jika menggunakan Livewire untuk login tenant)
    * Pengaturan halaman login di `AdminPanelProvider.php`

### 2.4. Tenant Storefront (Homepage Subdomain Tenant / Halaman Depan Toko)
* **Deskripsi:** Halaman depan toko mandiri (katalog/profil toko) yang dapat diakses publik. Ini adalah halaman yang dilihat oleh pelanggan dari pemilik toko tersebut saat mengakses `[nama-toko].zewalo.com`.
* **File Code Terkait:**
    * Route di `routes/tenant.php`
    * Controllers terkait tampilan katalog/produk depan (contoh: `app/Http/Controllers/StorefrontController.php`)
    * Direktori views khusus storefront (misal: `resources/views/storefront/*`)

### 2.5. Customer Portal Login (Login Customer Tenant)
* **Deskripsi:** Halaman form login bagi pelanggan/pembeli (Customer) untuk masuk ke akun mereka di dalam lingkup toko spesifik (untuk melacak resi, melihat pesanan, dst).
* **File Code Terkait:**
    * Routes autentikasi di dalam `routes/tenant.php`
    * Logika autentikasi customer (Guard `customer` atau sejenisnya)
    * View login untuk customer.

### 2.6. Customer Portal Register (Register Customer Tenant)
* **Deskripsi:** Alur registrasi agar seorang pengunjung bisa membuat akun "Customer" pada salah satu Tenant/Toko.
* **File Code Terkait:**
    * Routes registrasi di dalam `routes/tenant.php`
    * View registrasi untuk customer.

---

## 3. ISTILAH STRUKTURAL & DATABASE (Glossary Teknis Tambahan)

* **Superadmin:** User atau akun sistem yang memiliki kewenangan penuh atas aplikasi Zewalo secara keseluruhan (masuk lewat *Central Admin Panel*).
* **Tenant:** Pemilik bisnis / Klien Zewalo yang menyewa dan menjalankan toko di atas sistem ini.
* **Customer:** Pelanggan akhir; konsumen yang membeli barang/berinteraksi di toko milik Tenant.
* **Central Database:** Pangkalan data utama untuk menyimpan data global (daftar user utama, langganan, daftar tenant, dan identifikasi domain utama).
* **Tenant Context / Tenant Database:** Ruang lingkup isolasi data (Entah berupa skema *multi-database* atau *single database dengan `tenant_id`*), yang memastikan data produk/penjualan/staff satu toko tidak bocor ke toko lainnya.

---
**Catatan untuk AI Prompting:** 
Saat menerima instruksi prompt ke depannya, AI wajib mengidentifikasi dan mencocokkan *keyword* yang digunakan user dengan dokumen Kamus ini. Lakukan perubahan code pada lingkup (Namespace, file `.php`/`.blade.php`) secara akurat sesuai porsi Area yang dimaksud (Central vs Tenant).

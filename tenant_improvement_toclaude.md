# Zewalo: Tenant & Routing Improvement Guide

Dokumen ini berisi daftar instruksi dan langkah-langkah detail untuk memperbaiki, mengoptimalkan, dan menambahkan fitur pada alur registrasi serta routing Tenant di aplikasi Zewalo. Instruksi ini dirancang agar dapat dieksekusi secara berurutan.

---

## Masalah 1: Proses Registrasi Tenant Nge-hang (Synchronous Block)
**Konteks:** Pada komponen `app/Livewire/RegisterTenant.php` (Tahap 3), proses pembuatan database tenant, eksekusi migrasi, pembuatan role, dan akun admin berjalan secara *synchronous* dalam satu method `register()`. Ini menahan beban PHP request sehingga UI Livewire terlihat "nge-hang" dan progress tidak update secara berkala.

### Instruksi Perbaikan (Langkah demi Langkah):
1. **Buat Job Background untuk Provisioning:**
   - [ ] Buat file Job Laravel baru, misalnya: `app/Jobs/ProvisionTenantJob.php`.
   - [ ] Pindahkan _logic_ pembuatan domain, migrasi (via `TenantCreated` event atau Job Pipeline Tenancy), pembuatan tabel roles (`Spatie Permission`), akun user Admin, dan inisialisasi `Settings` dari fungsi `register()` di Livewire ke dalam fungsi `handle()` di Job tersebut.
   - [ ] _**Catatan:**_ Pastikan job di-dispatch ke _queue_ (misalnya database/redis) agar berjalan di background.
2. **Setup Progress Tracking:**
   - [ ] Karena proses sekarang berjalan di background, gunakan Cache, Database table monitoring sederhana, atau properti bawaan dari Job Batching bawaan Laravel untuk menyimpan status dari tiap _step_ instalasi (contoh status: `creating_db`, `migrating`, `seeding_admin`, `ready`, `failed`).
   - [ ] Jika menggunakan tabel monitoring, buat struktur untuk mengikat progress job berdasarkan ID/Subdomain pendaftar.
3. **Refactor Livewire `RegisterTenant.php`:**
   - [ ] Ubah method `register()`: Setelah entitas dasar `Tenant` terbentuk di tabel `tenants`, fungsi ini hanya bertugas men-dispatch `ProvisionTenantJob`.
   - [ ] Set status Livewire ke *waiting* atau *processing*, jangan langsung update ke *ready*.
   - [ ] Gunakan fitur `wire:poll` pada view Livewire (Tahap 3) untuk menanyakan/memeriksa status terbaru pembuatan tenant ke server persekian detik.
   - [ ] Update properti `$provisioningProgress` dan `$provisioningStep` di Livewire secara _reactive_ sesuai dengan hasil balikan dari _polling_ status job.
4. **Error Handling pada Job:**
   - [ ] Pastikan block `try-catch` di Job terintegrasi dengan baik. Jika Job gagal di tengah jalan (misal: migrasi crash), ubah status tracking menjadi `failed`.
   - [ ] UI Livewire (via polling) akan memunculkan menu `Gagal Membuat Tenant` & tombol `Coba Lagi` berdasarkan status tersebut (struktur view *failed* sudah tersedia di `register-tenant.blade.php`).

---

## Penambahan Fitur 2: Central Login Portal Tenant
**Konteks:** Saat ini, admin tenant harus tahu pastinya nama domain toko mereka (misal: `toko-kamera.zewalo.test/admin`) untuk mengakses panel Filament. Di landing page sentral (`zewalo.test`), tidak ada form terpadu agar pendaftar lama bisa gampang login.

### Instruksi Implementasi (Langkah demi Langkah):
1. **Buat Halaman & Form Cari Toko/Login Central:**
   - [ ] Buat rute baru di `routes/web.php` (untuk Central Domain) khusus form pencarian tenant, misalnya: `Route::get('/tenant-login', ... )` dan `Route::post('/tenant-login/redirect', ... )`.
   - [ ] Rancangkan view/UI di Landing Page yang meminta input: **Nama Subdomain/Toko** ATAU **Email Admin Pendaftar**.
2. **Pembuatan Logic Pencarian Domain (Controller/Livewire):**
   - [ ] Buat logika di controller atau komponen Livewire terpisah untuk form ini.
   - [ ] Jika user input **Subdomain**: Query ke tabel `domains` (`Domain::where('domain', 'LIKE', $input . '.%')->first()`).
   - [ ] Jika user input **Email**: Query ke tabel `tenants` (`Tenant::where('email', $input)->first()`). Lalu tarik relasi URL domain utamanya.
3. **Eksekusi Redirect:**
   - [ ] Apabila data toko ditemukan, lakukan validasi kelancaran (Misal cek apakah `Tenant` memiliki status aktif/trial, bukan `suspended`).
   - [ ] Lempar (redirect) user secara otomatis ke Form Login Panel Filament milik subdomain tersebut. Contoh struktur *hardcode* url redirect: 
     `return redirect()->away('http://' . $tenantData->domains->first()->domain . '/admin/login');`
   - [ ] Jika data toko tidak ditemukan, berikan alert feedback ke UI form tersebut (Invalid credentials/Store not found).
4. **Pemasangan Tombol Navigasi Landing Page:**
   - [ ] Tambahkan tombol "Masuk Tenant" atau "Login Toko" di _Header/Navbar_ Landing page bawaan Zewalo agar form ini mudah ditemukan pengguna web Central.

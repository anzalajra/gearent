# Checklist Revisi dan Penyempurnaan Fitur Register Tenant & Admin

Dokumen ini berisi daftar bagian-bagian yang harus diperbaiki, disempurnakan, dan dicek kembali pada alur pendaftaran tenant dan panel admin.

## 1. Register Tenant - Tahap 2 (Informasi Bisnis)
**Fokus Utama: Rate Limiting & Keamanan Form**
- [ ] **Modifikasi UX Rate Limiter:** Persingkat waktu tunggu batas percobaan (throttle error *"Terlalu banyak percobaan..."*) menjadi tepat 60 detik.
- [ ] **Pencegahan Spam Klik:** Buat/tambahkan *detector* perlindungan pada tombol submit. Cegah perilaku klik berkali-kali secara brutal (click spam) dari sisi klien (disable tombol dan tampilkan loading).
- [ ] **Auto-block IP untuk Brute-force:** Berikan logika backend untuk menangkal request bertubi-tubi. Jika terdeteksi melakukan *brute-force* atau request spam dari alamat IP yang sama, sistem harus melakukan otomatisasi pemblokiran IP (misalnya menggunakan *middleware rate limiting* kustom atau integrasi firewall/blocking table).

## 2. Register Tenant - Tahap 3 (Proses Setup Selesai)
**Fokus Utama: Peningkatan Kenyamanan UX**
- [ ] **Clickable URL Login:** Ubah teks pesan "Login ke toko-xxx.zewalo.test/admin..." sehingga teks URL admin domain tenant (`toko-xxx.zewalo.test/admin`) dapat di-klik (*clickable link*, menggunakan tag `<a href="...">`).
- [ ] Terapkan hal ini secara dinamis agar link yang di-*generate* sesuai dengan kustom URL *subdomain* atau domain tenant yang baru saja dibuat.

## 3. Tampilan Halaman Login Admin Tenant
**Fokus Utama: Perbaikan Styling / Aset Filament**
- [ ] **Perbaikan UI/CSS yang Hilang:** Selidiki penyebab halaman form login di URL tenant (`http://[tenant].zewalo.test/admin/login`) tampil hanya berupa form HTML polos tanpa *styles*.
- [ ] **Pengecekan Asset Routing:** Pastikan *assets* dari Filament (CSS dan Javascript bawaan web) bisa diakses (dirender/served) dengan baik dari subdomain tenant. Jika menggunakan custom domain routing, pastikan static assets tidak mendapatkan status `404 Not Found`.
- [ ] **Standar Layaknya Filament 4:** Pastikan hasil akhir form login kembali rapi, mulus, dan responsif layaknya desain UI/UX default dari Filament v4.

## 4. Fungsionalitas Login Panel Tenant & Error "Posts"
**Fokus Utama: Penanganan Livewire Error & Database Table**
- [ ] **Perbaikan Form Login Tidak Berfungsi:** Cek kenapa aksi klik tombol "Login" pada halaman authentication tenant tidak merespon/terjadi macet (Livewire Component gagal menembakkan event).
- [ ] **Investigasi Error Exception `42P01` (Relation 'posts' does not exist):**
  - Pada request ke `http://[tenant].zewalo.test/livewire/update`, mucul error query ke tabel `posts` (Pencarian parameter `where "post_type" = page`). 
  - Masalah ini mengindikasikan bahwa tabel `posts` **tidak ditemukan** di database tenant.
- [ ] **Pengecekan Migration Tenant:** Pastikan urutan *migrations* untuk struktur data spesifik tenant (termasuk tabel `posts`) dieksekusi seutuhnya pada saat tahapan pendaftaran (tahap 3: provisioning database).
- [ ] **Review Middleware / Global Query:** Bisa jadi ada view composer, service provider, middleware (`App\Http\Middleware\SecurityHeaders`), atau *navigation* yang langsung melakukan query mengambil halaman dari tabel `posts` tepat sebelum database / relasi table disiapkan sepenuhnya. Tambahkan proteksi apabila relasinya belum terbuat.

## 5. Fungsionalitas Keseluruhan Admin Panel Rental (Sisi Tenant)
**Fokus Utama: Uji Coba End-to-End Pada Panel Tenant**
- [ ] Lakukan skenario simulasi: Login dengan akun admin yang dibuat di Tahap 1 Pendaftaran.
- [ ] **Review Navigasi & Modul:** Telusuri setiap halaman pada admin panel Filament 4 setelah sukses login ke dashboard tenant.
- [ ] **Fungsionalitas Fitur Rental:** Pastikan "Admin Panel Rental" milik tenant beroperasi sebagaimana mestinya — fungsional lengkap (CRUD data penyewaan, inventaris rent, status transaksi).
- [ ] **Isolasi Data (Multi-tenancy):** Lakukan verifikasi bahwa semua pergerakan data benar-benar tersimpan ke database/skema milik tenant yang sedang aktif dan tidak tercampur (bocor) lintas tenant lainnya.

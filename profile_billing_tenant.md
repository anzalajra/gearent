# Rencana Implementasi: Profile, Business Information, & Billing Zewalo

Dokumen ini memuat rencana kerja (blueprint) untuk mengimplementasikan pemisahan antara data personal admin (Profile), data identitas bisnis (Business Information), dan sistem tagihan langganan (Subscription & Billing). Rencana ini mencakup modifikasi dari sisi Tenant Admin Panel serta pengawasan fungsional pada Central Admin Zewalo.

---

## A. Arsitektur Panel Tenant (Tenant Admin Panel)

### 1. User Menu (Pojok Kanan Atas)
Menu *dropdown* yang muncul saat user mengklik avatar/nama di navigasi atas. Ditujukan khusus untuk privasi akun pengelola dan urusan akun dengan platform Zewalo.

*   **Aksi:** Memodifikasi `userMenuItems` pada Filament Panel Provider untuk Tenant.
*   **Item 1: My Profile**
    *   **Fungsi:** Tempat pengelolaan data autentikasi user yang sedang login.
    *   **Isi Form:** Foto Profil (Avatar), Nama Lengkap Pribadi, Email Akun, Form Ganti Password, dan Pengaturan 2FA.
    *   *Catatan:* Tidak ada *field* input terkait nama toko atau entitas bisnis di halaman ini.
*   **Item 2: Subscription & Billing**
    *   **Fungsi:** Melihat status perjanjian sewa/SaaS antara Tenant dengan platform Zewalo.
    *   **Isi Halaman (Dashboard UI / Read-Only):**
        *   Nama Paket/Plan saat ini (Status: *Active/Trial/Expired*).
        *   Tanggal kedaluwarsa (*Valid Until / Next Billing Date*).
        *   Statistik Limit Penggunaan (jika diterapkan) seperti kuota produk, penyimpanan, dsb.
        *   Riwayat *Invoice* atau mutasi pembayaran langganan ke Zewalo.
        *   Aksi opsional: Tombol *Upgrade Plan* / *Renew*.

### 2. Sidebar Navigation (Navigasi Utama Kiri)
Menu untuk mengatur kegiatan operasional publik dari toko itu sendiri.

*   **Aksi:** Mengganti penamaan dan memindahkan form "General Settings".
*   **Menu: Business Information** (Menggantikan nama *General Settings*)
    *   **Fungsi:** Satu-satunya letak bagi penyewa (tenant) untuk menyiapkan *branding* tokonya. Ke sinilah admin diarahkan saat ingin mengubah detail websitenya.
    *   **Isi Form:**
        *   Logo Toko & Favicon.
        *   Nama Resmi Toko/Bisnis.
        *   Kategori/Deskripsi Bisnis.
        *   Alamat Lengkap (digunakan untuk pengiriman/ongkir jika ada).
        *   Kontak Publik Toko (Email CS dan Nomor WA Toko).

---

## B. Arsitektur Central Admin Panel (Pusat Kendali Zewalo)

Sebagai pemilik platform, tim internal Zewalo harus bisa memantau dan memanipulasi logistik *tenant* untuk keperluan operasional dan *customer support*.

### 1. Manajemen Detail Tenant (View/Edit Tenant di Resource `Tenant`)
*   **Aksi:** Merekonstruksi tampilan form (`form()`) atau infolist (`infolist()`) di `TenantResource`.
*   **Penambahan Struktur Tab (Tabs Layout):** Saat Admin Utama membuka rincian penyewa manapun, layar dibagi menjadi beberapa *Tab* yang rapi:
    *   **Tab "Business Information":** Menampilkan seluruh data entitas bisnis (*mirroring* apa yang diisi penyewa pada panel mereka - bisa dalam mode edit untuk membantu tenant).
    *   **Tab "Owner Profile":** Menampilkan dan mengatur informasi personal si pemegang akun admin tenant (Pemilik).
    *   **Tab "Subscription Status":** Menampilkan kondisi aktual masa berlaku tenant.
        *   Menyediakan tombol/aksi override manual bagi CS: *Beri tambahan masa aktif (extend)*, *Ubah Limit*, atau aksi darurat *Suspend Tenant*.

### 2. Halaman Sentral: Subscriptions & Billing Data
*   **Aksi:** Membangkitkan Resource Filament khusus seperti `TenantSubscriptionResource` atau `BillingResource` di ruang spesifik panel Central.
*   **Fungsi:** Dasbor analitik dan administratif bagi tim keuangan Zewalo untuk melacak uang masuk dan siklus hidup pelanggan tanpa harus membongkar detail tenant satu-persatu.
*   **Fitur Spesifik:**
    *   Tabel terintegrasi yang memuat daftar tagihan (*invoices*) lintas *tenant*.
    *   Kolom esensial: [Nama Toko/Tenant], [Paket Langganan], [Tagihan], [Status Lunas/Belum], [Tanggal Jatuh Tempo].
    *   Filter dan Indikator: Warna merah/peringatan (Badge) secara otomatis untuk penyewa yang *mendekati expired (< 7 hari)* atau penyewa dengan tagihan tertunggak.

---

## C. Catatan Basis Data Secara Konseptual (Data Flow)

Konfigurasi antarmuka di atas perlu direfleksikan dalam susunan skema:
1.  **Profil (*User Menu - My Profile*):** Membaca dan mempebarui entitas pada tabel `users`.
2.  **Informasi Bisnis (*Sidebar - Business Info*):** Membaca dan memperbarui tabel *core* `tenants` (kolom nama_toko, logo, dll).
3.  **Sistem Penagihan (*Billing*):** Memanfaatkan relasi dari tabel pendukung (misalnya tabel `subscriptions` atau `tenant_subscriptions` dan `invoices`) dan ditarik ke dalam panel yang membutuhkan tampilan datanya.

*Dokumen ini merupakan panduan fungsional sebelum kode ditulis, menghindari redundansi dan memastikan skalabilitas yang mantap.*

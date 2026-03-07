# Rencana Implementasi Sistem Pembayaran & Subscription Zewalo

Dokumen ini merangkum arsitektur dan langkah-langkah implementasi sistem pembayaran terpusat (Central-to-Tenant) dan sistem pembayaran rental (Customer-to-Tenant), termasuk integrasi Payment Gateway Universal (difokuskan ke Xendit melalui Direct Channel API).

## 1. Persiapan Struktur Database & Skema
Pembaruan dan penambahan tabel untuk mendukung multi-payment dan multitenant billing.

### 1.1 Tabel Master Central (Payment Configuration)
*   **`payment_gateways`**: Menyimpan konfigurasi API (contoh: Xendit, Midtrans). Field mncakup `name`, `is_active`, `credentials` (JSON/Encrypted).
*   **`payment_methods`**: Master metode pembayaran (contoh: VA BCA, QRIS Xendit, Manual Transfer). Tersambung dengan `gateway_id`. Terdapat parameter pengelompokan seperti `type` (auto, manual), `admin_fee`, dsb.
*   **`platform_settings`**: Menyimpan konfigurasi seperti `platform_commission_fee` (potongan komisi Auto Payment untuk Central).

### 1.2 Modifikasi Tabel Tenant & Invoicing (Central)
*   **Tabel `tenants`**: Penambahan field `subscription_ends_at`, `subscription_status` (enum: `active`, `grace_period`, `suspended`).
*   **Sistem `saas-invoices`**: Memodifikasi sistem invoice yang ada di central agar mendukung `due_date`, `payment_method_id`, dan detail status pembayaran spesifik (`unpaid`, `paid`, `overdue`).

### 1.3 Tabel Payment Tenant & Settlement
*   **`tenant_payment_methods`**: Pengaturan metode bayar yang diaktifkan/dibuat oleh Tenant. Field: `tenant_id`, `payment_method_id` (jika auto dari central), `type` (auto_from_central, own_manual), `account_details` (JSON untuk nama bank, no rek, atau path gambar QRIS statis tenant), `is_active`.
*   **`rental_invoices`**: Tabel transaksi rental customer. Field: `tenant_id`, `customer_id`, `amount`, `payment_method_id`, `payment_status`, `payment_data` (nomor VA/QR string), `payment_proof_path` (bukti transfer manual).
*   **`settlements`**: Mencatat mutasi pencairan dana dari sistem "Auto Payment" Central ke Tenant. Field: `tenant_id`, `rental_invoice_id`, `gross_amount`, `platform_fee_amount`, `net_amount`, `status` (`pending`, `ready_to_disburse`, `disbursed`), `disbursed_at`.

---

## 2. Arsitektur Universal Payment Gateway
Membangun struktur kode agar sistem pembayaran independen dari satu provider spesifik.

1.  **Interface/Contract `PaymentGatewayInterface`**:
    Mendefinisikan metode baku seperti `createVirtualAccount()`, `createQris()`, dan `verifyPayment()`.
2.  **Manager & Service Class (`GatewayManager` & `PaymentService`)**:
    Menjadi jembatan aplikasi. Menggunakan pola *Factory* untuk memuat *Driver* gateway berdasarkan metode pembayaran yang dipilih (misal memanggil `XenditDriver`).
3.  **Implementasi `XenditDriver` (Direct Channel API)**:
    Implementasi pemanggilan API ke endpoint spesifik Xendit (Bypass checkout UI Xendit), mem-parsing response menjadi bentuk baku aplikasi, dan mengembalikan instruksi bayar langsung (No. VA atau string QRIS).
4.  **Universal Webhook Controller `/api/webhooks/payment/{gateway}`**:
    Endpoint untuk menerima callback asinkron dari Gateway. Memuat logika validasi signature/token, memperbarui status invoice menjadi "Paid", dan jika terkait Auto Payment Rental, membuat entri `settlements` untuk tenant terkait otomatis memotong komisi platform.

---

## 3. Central Admin Panel Development
Pengembangan di area manajemen sentral (Zewalo).

### 3.1 Halaman Pengaturan Pembayaran
*   **Halaman Payment Gateways**: CRUD/Toggle aktif untuk gateway & form input API Key rahasia.
*   **Halaman Payment Methods**: UI untuk menambah metode bayar yang didukung oleh platform beserta routing gateway-nya.
*   **Pengaturan Komisi**: Form simpel untuk menetapkan % atau nominal komisi platform untuk Auto Payment.

### 3.2 Sistem Penagihan Berlangganan (Cron / Scheduler)
*   **Job `GenerateSubscriptionInvoices`**: Berjalan harian (Daily). Mencari tenant dengan `subscription_ends_at` dalam H-7. Mem-build Invoice Subscription baru dan mengirim email/notifikasi penagihan ke Tenant.
*   **Job `CheckSubscriptionStatus`**: Berjalan harian.
    *   Jika sudah lewat jatuh tempo -> Ubah status DB menjadi `grace_period` (H+1 sampai H+3).
    *   Jika sudah H+4 tanpa pembayaran -> Ubah status menjadi `suspended`.
    *   Jika sudah mencapai H+30 suspend -> Trigger notifikasi ke Central Admin untuk peringatan penghapusan DB secara manual.

---

## 4. Tenant Access Control & Middleware
Kontrol akses panel admin tenant berdasarkan status masa aktif.

*   **Middleware `EnsureTenantSubscriptionActive`**:
    *   `active`: Bebas akses.
    *   `grace_period`: Disuntikkan alert/banner persisten di Filament. Method `POST/PUT/DELETE` dicegat dan digagalkan (Akses Read-Only tercapai), kecuali untuk modul profil/pembayaran tagihan.
    *   `suspended`: Dicegat sepenuhnya pada level kontroler, redirect paksa ke halaman khusus "Subscription Expired" berisi riwayat tagihan & form pembayaran untuk reaktivasi.

---

## 5. Tenant Admin Panel Development
Pengembangan fitur pengaturan di sisi pengelola Tenant.

### 5.1 Manajemen Metode Pembayaran (Tenant Settings)
*   **Daftar Auto Payment**: Tabel yang menampilkan metode "Auto Payment" dari Central (View-only atau toggle on/off). Terdapat indikator "Commission Applied".
*   **Own Payment Method (Manual)**: Form untuk menambah metode manual milik tenant. Memilih tipe: `Bank Transfer` (Input Nama Bank, No Rek, Nama Pemilik) atau `QRIS Static` (Upload gambar barcode QRIS statis).

### 5.2 Halaman Settlement (Pencairan Dana)
*   Berupa tabel yang menampilkan rekap dana masuk yang terbayar otomatis lewat Gateway ke sistem (dengan rincian Gross, Potongan Komisi Central, dan Net Diterima).
*   Melihat status dana: `Pending` (belum diproses pencairannya oleh Central) atau `Disbursed` (Sudah cair ke rekening tenant).
*   *Catatan: Pencairan ke Tenant dilakukan secara manual transfer oleh Central admin di luar sistem, lalu klik "Mark as Disbursed" di sisi Central Panel yang akan meng-update status di Tenant Panel.*

---

## 6. Customer Storefront & Checkout Alur Pembayaran
Pengalaman pengguna (Customer) saat menyewa dari Tenant.

### 6.1 Checkout Rental
*   Pemilihan payment method menampilkan daftar gabungan yang diaktifkan Tenant (Auto Payment via Gateway + Own Payment/Manual).
*   **Proses Bayar**:
    *   **Auto Payment**: Memanggil `PaymentService` -> Mendapat Direct API Response -> Tampilkan No. VA / QR Code interaktif secara real-time di UI Zewalo tanpa melempar user ke URL Gateway eksternal.
    *   **Manual Method**: Menampilkan instruksi transfer sesuai data tenant.

### 6.2 Alur Manual Transfer
*   Menyediakan halaman "Payment Instruction" yang mencakup komponen untuk upload gambar bukti transfer (`payment_proof`).
*   Terdapat tombol "Hubungi Admin" (link ke WhatsApp Admin Tenant).
*   Status invoice berubah menjadi `Pending Verification` pasca upload.

### 6.3 Verifikasi oleh Tenant (Manual Payment)
*   Di panel admin tenant, terdapat notifikasi/badge untuk invoice manual yang "Pending".
*   Tenant Admin dapat menolak atau menerima bukti transfer (tombol "Verify & Mark as Paid").

---
**Status Dokumen**: `Draft`
**Tindakan Lanjutnya**: Meninjau rencana ini. Apabila disetujui, tahap implementasi akan dimulai dari Fase 1 secara berurutan.

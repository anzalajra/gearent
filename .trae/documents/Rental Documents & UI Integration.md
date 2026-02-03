# Penambahan Fitur Dokumen Rental & Integrasi UI

Saya akan mengimplementasikan beberapa fitur baru untuk meningkatkan manajemen dokumen pengiriman dan mempermudah navigasi antar halaman operasional.

## Perubahan yang Direncanakan:

### 1. Update Model Delivery
- Menambahkan status `pending` pada model `Delivery`.
- Memperbarui konfigurasi warna dan opsi status agar menyertakan status `pending`.

### 2. Otomatisasi Status Delivery Masuk
- Memperbarui logika `createDeliveries()` di model `Rental`.
- Setiap kali rental baru dibuat, Surat Jalan Masuk (SJM) akan otomatis berstatus `pending`, sementara Surat Jalan Keluar (SJK) tetap `draft`.

### 3. Halaman Baru: Rental Documents
- Membuat halaman khusus `RentalDocuments` yang menampilkan daftar Surat Jalan (Keluar & Masuk) yang terkait dengan satu rental tertentu.
- Halaman ini akan mempermudah admin melihat histori dokumen pengiriman per transaksi.

### 4. Integrasi Tombol & Navigasi
- **Pickup Operation Page**: Menambahkan tombol "Delivery" di header untuk akses cepat ke daftar dokumen rental.
- **View Rental Page**:
    - Menambahkan tombol "Edit" (jika diizinkan).
    - Menambahkan tombol "Delivery" menuju halaman dokumen rental.
    - Menambahkan tombol dinamis "Pickup" atau "Return" sesuai dengan status rental saat ini.
- **Pembersihan UI**: Menghapus tombol manual di bagian bawah halaman View Rental dan memindahkannya ke header agar lebih rapi dan standar.

## Langkah Teknis:
1. Modifikasi `App\Models\Delivery`.
2. Modifikasi `App\Models\Rental`.
3. Buat file baru `App\Filament\Resources\Rentals\Pages\Rentals\RentalDocuments.php`.
4. Daftarkan route baru di `RentalResource`.
5. Update `PickupOperation.php` dan `ViewRental.php` untuk menambahkan aksi header.
6. Rapikan view blade `view-rental.blade.php`.

Apakah rencana ini sudah sesuai dengan keinginan Anda?
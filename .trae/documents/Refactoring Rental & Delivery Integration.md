# Perbaikan Alur Integrasi Rental & Delivery

Saya telah menganalisis permintaan Anda untuk mengintegrasikan alur **Rental** dan **Delivery** agar lebih sinkron dan efisien. Fokus utama adalah menjadikan **Rental** sebagai pusat kendali dan mengotomatiskan pembuatan dokumen **Delivery** (Surat Jalan).

## Rencana Implementasi

### **1. Otomatisasi Pembuatan Delivery (Surat Jalan)**
- **Centralization**: Menambahkan logika di [CheckoutController.php](file:///d:/6. Kints/Projects/Gearent/app/Http/Controllers/CheckoutController.php) untuk otomatis membuat 2 draft **Delivery** (tipe `out` untuk pickup dan tipe `in` untuk return) segera setelah Rental disubmit.
- **Sync Items**: Setiap item dalam rental (termasuk unit dan kit) akan otomatis terdaftar sebagai `DeliveryItem`.

### **2. Pembersihan Antarmuka (UI) Rentals**
- **Rentals Table**: Menghapus tombol manual "Create Surat Jalan Keluar/Masuk" di [RentalsTable.php](file:///d:/6. Kints/Projects/Gearent/app/Filament/Resources/Rentals/Tables/RentalsTable.php) karena proses ini sekarang sudah otomatis.

### **3. Refactor Pickup & Return Operation**
- **Unified Checklist**: Mengubah tampilan tabel di [PickupOperation.php](file:///d:/6. Kints/Projects/Gearent/app/Filament/Resources/Rentals/Pages/Rentals/PickupOperation.php) dan `ProcessReturn.php` agar menggunakan sistem checklist yang sama dengan [ProcessDelivery.php](file:///d:/6. Kints/Projects/Deliveries/Pages/ProcessDelivery.php).
- **Direct Interaction**: Mengganti sistem popup (modal) dengan aksi langsung di baris tabel untuk mempercepat proses pengecekan item.
- **Columns**: Tabel akan berisi kolom: `Item`, `Serial Number`, `Type` (Unit/Kit), `Condition`, `Checked` (Status), dan `Tombol Check`.

### **4. Sinkronisasi Data Real-time**
- **Cross-Update**: Setiap kali item dicentang di halaman **Pickup Operation**, status `is_checked` pada `DeliveryItem` terkait akan terupdate otomatis, begitu juga sebaliknya.
- **Rental Status**: Jika semua item sudah dicentang (baik di Delivery maupun Pickup page), tombol "Validate Pickup" akan aktif untuk mengubah status Rental menjadi `Active`.

### **5. Fitur Dokumen di Pickup Page**
- **PDF Download**: Menambahkan tombol "Download PDF" di halaman Pickup Operation yang akan menghasilkan Surat Jalan Keluar (SJK) yang sama dengan yang ada di modul Deliveries.

## Langkah Teknis:
1.  Modifikasi model `Rental` untuk menambahkan helper `createDeliveries()`.
2.  Update `CheckoutController` untuk memanggil helper tersebut.
3.  Refactor `PickupOperation.php` untuk menampilkan data dari `DeliveryItem`.
4.  Hapus aksi pembuatan delivery manual di `RentalsTable.php`.
5.  Uji coba alur dari pembuatan rental hingga proses pickup.

Apakah Anda setuju dengan rencana ini?
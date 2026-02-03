## Implementasi Otomatisasi Unit dan Kalender Ketersediaan Live

Saya akan melakukan perubahan pada sistem frontend untuk memenuhi permintaan Anda: menghapus pemilihan unit manual oleh customer dan menggantinya dengan pemilihan otomatis, serta menambahkan kalender ketersediaan live.

### 1. Perubahan Model & Logika Backend
- **Model [Product.php](file:///d:/6. Kints/Projects/Gearent/app/Models/Product.php)**: 
    - Menambahkan method `getBookedDates()` untuk menghitung tanggal-tanggal di mana **semua unit** produk tersebut sudah terbooking. Tanggal ini akan dikirim ke frontend untuk di-disable pada kalender.
    - Menambahkan method `findAvailableUnit($startDate, $endDate)` untuk mencari unit pertama yang tersedia pada rentang tanggal yang dipilih.
- **Controller [CartController.php](file:///d:/6. Kints/Projects/Gearent/app/Http/Controllers/CartController.php)**:
    - Memperbarui method `add` agar menerima `product_id` alih-alih `product_unit_id`.
    - Menggunakan logika pencarian unit otomatis di backend. Jika tidak ada unit yang tersedia untuk rentang tanggal tersebut, sistem akan memberikan pesan error.

### 2. Pembaruan Tampilan Frontend
- **View [show.blade.php](file:///d:/6. Kints/Projects/Gearent/resources/views/frontend/catalog/show.blade.php)**:
    - **Menghapus dropdown "Select Unit"** agar customer tidak bisa memilih unit sendiri.
    - **Integrasi Flatpickr**: Menggunakan library Flatpickr untuk kalender range (satu input untuk Start & End Date).
    - **Kalender Live**: Tanggal yang sudah terbooking penuh (berdasarkan data dari `getBookedDates()`) akan berwarna merah dan tidak bisa diklik oleh customer.
    - Mengatur agar input tanggal minimal adalah hari ini.

### 3. Alur Kerja Baru
1. Customer masuk ke halaman detail alat.
2. Customer melihat kalender. Tanggal yang sudah penuh terbooking akan berwarna merah.
3. Customer memilih rentang tanggal (Start & End Date) dalam satu kalender yang sama.
4. Setelah klik "Add to Cart", sistem backend akan mencari unit yang tersedia secara otomatis.
5. Unit yang dipilih sistem akan disimpan ke dalam keranjang belanja.

Apakah Anda setuju dengan rencana ini? Jika ya, saya akan segera memproses perubahannya.
Refaktor bagian verifikasi dokumen di halaman profil pelanggan agar menggunakan satu tombol unggah untuk semua dokumen, bukan satu tombol per dokumen.

## Perubahan Backend
### Update `CustomerDocumentController`
- Memperbarui method `upload` di [CustomerDocumentController.php](file:///d:/6.%20Kints/Projects/Gearent/app/Http/Controllers/CustomerDocumentController.php) untuk menangani array file.
- Menambahkan validasi untuk memastikan setidaknya satu file dipilih dan setiap file memenuhi kriteria (format JPG/PNG/PDF, max 500KB).
- Melakukan iterasi pada array file yang diunggah, menghapus dokumen lama jika ada (dan belum disetujui), lalu menyimpan file baru.

## Perubahan Frontend
### Refaktor `profile.blade.php`
- **Pencegahan Nested Forms**: Menambahkan form tersembunyi (hidden form) dan fungsi JavaScript sederhana untuk menangani penghapusan dokumen. Ini diperlukan karena kita akan membungkus seluruh daftar dokumen dalam satu form unggah besar, dan HTML tidak mengizinkan form di dalam form.
- **Form Unggah Tunggal**: Membungkus seluruh bagian daftar dokumen verifikasi di [profile.blade.php](file:///d:/6.%20Kints/Projects/Gearent/resources/views/frontend/dashboard/profile.blade.php) ke dalam satu tag `<form>` yang mengarah ke route `customer.documents.upload`.
- **Penamaan Input File**: Mengubah nama input file menjadi format array: `name="files[{{ $type->id }}]"`.
- **Tombol Unggah Bersama**: Menghapus tombol "Upload" individual dan menambahkan satu tombol "Upload Semua Dokumen" di bagian bawah daftar dokumen.
- **Pembersihan UI**: Menyesuaikan tampilan agar lebih rapi dengan satu tombol aksi utama.

## Langkah Verifikasi
1. Membuka halaman profil pelanggan.
2. Memilih beberapa file untuk tipe dokumen yang berbeda.
3. Menekan tombol "Upload Semua Dokumen" dan memastikan semua file tersimpan dengan benar di backend.
4. Mencoba menghapus salah satu dokumen menggunakan fungsi hapus yang baru direfaktorkan.
5. Memastikan pesan sukses/gagal muncul dengan benar.
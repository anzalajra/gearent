## **Rencana Perubahan Layout Sidebar Admin**

### **1. Penyesuaian Grup Navigasi Utama**
Memperbarui [AdminPanelProvider.php](file:///d:/6. Kints/Projects/Gearent/app/Providers/Filament/AdminPanelProvider.php) untuk mengatur urutan grup navigasi:
- `Rentals`
- `Sales`
- `Inventory`
- `Setting`
- `Product Setup`

### **2. Reorganisasi Menu Rentals**
- [RentalResource.php](file:///d:/6. Kints/Projects/Gearent/app/Filament/Resources/Rentals/RentalResource.php): Memastikan di grup `Rentals` (Urutan 1).
- [RentalCalendar.php](file:///d:/6. Kints/Projects/Gearent/app/Filament/Pages/RentalCalendar.php): Mengubah label menjadi **'Calendar'**, memindahkan ke grup `Rentals` (Urutan 2).
- [DeliveryResource.php](file:///d:/6. Kints/Projects/Gearent/app/Filament/Resources/Deliveries/DeliveryResource.php): Memindahkan ke grup `Rentals` (Urutan 3).
- [CustomerResource.php](file:///d:/6. Kints/Projects/Gearent/app/Filament/Resources/Customers/CustomerResource.php): Memindahkan ke grup `Rentals` (Urutan 4).

### **3. Reorganisasi Menu Sales & Inventory**
- [DiscountResource.php](file:///d:/6. Kints/Projects/Gearent/app/Filament/Resources/Discounts/DiscountResource.php): Memastikan di grup `Sales`.
- [ProductResource.php](file:///d:/6. Kints/Projects/Gearent/app/Filament/Resources/Products/ProductResource.php): Mengubah label menjadi **'Product'**, grup `Inventory`.
- [ProductUnitResource.php](file:///d:/6. Kints/Projects/Gearent/app/Filament/Resources/ProductUnits/ProductUnitResource.php): Mengubah label menjadi **'Product Unit'**, grup `Inventory`.

### **4. Reorganisasi Menu Setting & Product Setup**
- [Settings.php](file:///d:/6. Kints/Projects/Gearent/app/Filament/Pages/Settings.php): Memindahkan ke grup `Setting` (Urutan 1).
- [DocumentTypeResource.php](file:///d:/6. Kints/Projects/Gearent/app/Filament/Resources/DocumentTypeResource.php): Memindahkan ke grup `Setting` (Urutan 2).
- [BrandResource.php](file:///d:/6. Kints/Projects/Gearent/app/Filament/Resources/Brands/BrandResource.php): Memindahkan ke grup `Product Setup` (Urutan 1).
- [CategoryResource.php](file:///d:/6. Kints/Projects/Gearent/app/Filament/Resources/Categories/CategoryResource.php): Memindahkan ke grup `Product Setup` (Urutan 2).

## **Langkah Eksekusi**
1. Mengubah urutan grup di `AdminPanelProvider`.
2. Memperbarui `$navigationGroup`, `$navigationLabel`, dan `$navigationSort` di masing-masing file Resource dan Page.
3. Melakukan pengecekan akhir untuk memastikan semua menu berada di posisi yang benar.
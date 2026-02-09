<?php

use App\Http\Controllers\Auth\CustomerAuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

// use App\Http\Controllers\Admin\PageBuilderController;
use App\Http\Controllers\PublicDocumentController;

use App\Http\Controllers\SetupController;
use Illuminate\Support\Facades\File;

// Check installation status
$isInstalled = File::exists(storage_path('installed'));

if (!$isInstalled) {
    // If NOT installed, only allow setup routes and redirect root to setup
    Route::prefix('setup')->name('setup.')->group(function () {
        Route::get('/', [SetupController::class, 'index'])->name('index');
        Route::get('/step1', [SetupController::class, 'step1'])->name('step1');
        Route::post('/step2', [SetupController::class, 'step2'])->name('step2');
        Route::get('/step3', [SetupController::class, 'step3'])->name('step3');
        Route::get('/step4', [SetupController::class, 'step4'])->name('step4');
        Route::get('/step5', [SetupController::class, 'step5'])->name('step5');
        Route::post('/step6', [SetupController::class, 'step6'])->name('step6');
    });

    // Catch-all redirect to setup for root or any other route
    Route::get('/', function () {
        return redirect()->route('setup.index');
    });
    
    // Fallback to ensure everything goes to setup
    Route::fallback(function () {
        return redirect()->route('setup.index');
    });

} else {
    // --- EMERGENCY DB TOOLS (Hapus setelah selesai) ---
    Route::get('/fix-db-migrate', function () {
        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            return '<h1>Migration Success</h1><pre>' . \Illuminate\Support\Facades\Artisan::output() . '</pre>';
        } catch (\Exception $e) {
            return '<h1>Migration Failed</h1><pre>' . $e->getMessage() . '</pre>';
        }
    });

    Route::get('/fix-db-status', function () {
        try {
            \Illuminate\Support\Facades\Artisan::call('migrate:status');
            return '<h1>Migration Status</h1><pre>' . \Illuminate\Support\Facades\Artisan::output() . '</pre>';
        } catch (\Exception $e) {
            return '<h1>Check Failed</h1><pre>' . $e->getMessage() . '</pre>';
        }
    });
    // --------------------------------------------------

    // If INSTALLED, load normal application routes

    // Setup Routes (protected by middleware CheckInstallation in controller if needed, or just remove)
    // But since we use logic here, we can keep them or rely on middleware.
    // Keeping them here for safety if middleware fails, but better to hide them?
    // Let's keep them but CheckInstallation middleware should handle redirection back to home.
    Route::prefix('setup')->name('setup.')->group(function () {
        Route::get('/', [SetupController::class, 'index'])->name('index');
        // ... other setup routes if needed for re-setup or similar logic, 
        // but typically we don't want them accessible.
        // For now, let's just rely on the middleware for installed state redirection
        // or just don't define them here to be extra safe.
        // However, middleware CheckInstallation is still best practice.
        // Let's just define them to avoid "Route not found" if someone hard refreshes during transition.
        Route::get('/', [SetupController::class, 'index'])->name('index');
        Route::get('/step1', [SetupController::class, 'step1'])->name('step1');
        Route::post('/step2', [SetupController::class, 'step2'])->name('step2');
        Route::get('/step3', [SetupController::class, 'step3'])->name('step3');
        Route::get('/step4', [SetupController::class, 'step4'])->name('step4');
        Route::get('/step5', [SetupController::class, 'step5'])->name('step5');
        Route::post('/step6', [SetupController::class, 'step6'])->name('step6');
    });

    // Public Routes
    Route::get('/', [HomeController::class, 'index'])->name('home');

    // Catalog
    Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
    Route::get('/catalog/{product}', [CatalogController::class, 'show'])->name('catalog.show');
    Route::post('/catalog/check-availability/{unit}', [CatalogController::class, 'checkAvailability'])->name('catalog.check-availability');

    // Customer Auth
    Route::middleware('customer.guest')->group(function () {
        Route::get('/login', [CustomerAuthController::class, 'showLoginForm'])->name('customer.login');
        Route::post('/login', [CustomerAuthController::class, 'login'])->middleware('throttle:6,1');
        Route::get('/register', [CustomerAuthController::class, 'showRegistrationForm'])->name('customer.register');
        Route::post('/register', [CustomerAuthController::class, 'register'])->middleware('throttle:6,1');
    });

    Route::match(['get', 'post'], '/logout', [CustomerAuthController::class, 'logout'])->name('customer.logout')->middleware('customer.auth');

    // Customer Protected Routes
    Route::middleware('customer.auth')->prefix('customer')->name('customer.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [CustomerDashboardController::class, 'index'])->name('dashboard');
        Route::get('/profile', [CustomerDashboardController::class, 'profile'])->name('profile');
        Route::put('/profile', [CustomerDashboardController::class, 'updateProfile'])->name('profile.update');
        Route::put('/password', [CustomerDashboardController::class, 'updatePassword'])->name('password.update');
        Route::get('/rentals', [CustomerDashboardController::class, 'rentals'])->name('rentals');
        Route::get('/rentals/{id}', [CustomerDashboardController::class, 'rentalDetail'])->name('rental.detail');

        // Notifications
        Route::get('/notifications/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/notifications/read-all', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    });

    // Cart
    Route::middleware('customer.auth')->group(function () {
        Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
        Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
        Route::post('/cart/update-all', [CartController::class, 'updateAll'])->name('cart.update-all');
        Route::put('/cart/{cart}', [CartController::class, 'update'])->name('cart.update');
        Route::delete('/cart/product', [CartController::class, 'removeProduct'])->name('cart.remove-product');
        Route::patch('/cart/quantity', [CartController::class, 'updateQuantity'])->name('cart.update-quantity');
        Route::delete('/cart/{cart}', [CartController::class, 'remove'])->name('cart.remove');
        Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');

        // Checkout
        Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
        Route::post('/checkout', [CheckoutController::class, 'process'])->name('checkout.process');
        Route::post('/checkout/validate-discount', [CheckoutController::class, 'validateDiscount'])->name('checkout.validate-discount');
        Route::get('/checkout/success/{rental}', [CheckoutController::class, 'success'])->name('checkout.success');
    });

    // Customer Documents
    Route::middleware('customer.auth')->group(function () {
        Route::post('/customer/documents/upload', [App\Http\Controllers\CustomerDocumentController::class, 'upload'])->name('customer.documents.upload');
        Route::get('/customer/documents/{document}', [App\Http\Controllers\CustomerDocumentController::class, 'view'])->name('customer.documents.view');
        Route::delete('/customer/documents/{document}', [App\Http\Controllers\CustomerDocumentController::class, 'delete'])->name('customer.documents.delete');
    });

    // Admin Document View
    Route::middleware(['auth'])->group(function () {
        Route::get('/admin/documents/{document}/{filename?}', [App\Http\Controllers\CustomerDocumentController::class, 'viewForAdmin'])->name('admin.documents.view');
    });

    // Public Signed Documents
    Route::prefix('public-documents')->name('public-documents.')->group(function () {
        Route::get('/rental/{rental}/checklist', [PublicDocumentController::class, 'rentalChecklist'])->name('rental.checklist');
        Route::get('/rental/{rental}/delivery-note', [PublicDocumentController::class, 'rentalDeliveryNote'])->name('rental.delivery-note');
        Route::get('/quotation/{quotation}', [PublicDocumentController::class, 'quotation'])->name('quotation');
        Route::get('/invoice/{invoice}', [PublicDocumentController::class, 'invoice'])->name('invoice');
    });

    // Lara Zeus Sky Routes
    Route::prefix('blog')->middleware(['web'])->group(function () {
        Route::get('/', \LaraZeus\Sky\Livewire\Posts::class)->name('blogs');
        Route::get('/faq', \LaraZeus\Sky\Livewire\Faq::class)->name('faq');
        
        Route::get('/tag/{slug}', \LaraZeus\Sky\Livewire\Tags::class)
            ->defaults('type', 'tag')
            ->name('tag');
            
        Route::get('/category/{slug}', \LaraZeus\Sky\Livewire\Tags::class)
            ->defaults('type', 'category')
            ->name('category');

        Route::get('/{slug}', \LaraZeus\Sky\Livewire\Post::class)->name('post');
    });

    // Lara Zeus Sky Pages (Direct Access)
    Route::middleware(['web'])->group(function () {
        Route::get('/{slug}', \LaraZeus\Sky\Livewire\Page::class)->name('page');
    });
}

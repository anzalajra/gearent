<?php

use App\Http\Controllers\Auth\CustomerAuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home');

// Catalog
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/catalog/{product}', [CatalogController::class, 'show'])->name('catalog.show');
Route::post('/catalog/check-availability/{unit}', [CatalogController::class, 'checkAvailability'])->name('catalog.check-availability');

// Customer Auth
Route::middleware('customer.guest')->group(function () {
    Route::get('/login', [CustomerAuthController::class, 'showLoginForm'])->name('customer.login');
    Route::post('/login', [CustomerAuthController::class, 'login']);
    Route::get('/register', [CustomerAuthController::class, 'showRegistrationForm'])->name('customer.register');
    Route::post('/register', [CustomerAuthController::class, 'register']);
});

Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('customer.logout')->middleware('customer.auth');

// Customer Protected Routes
Route::middleware('customer.auth')->prefix('customer')->name('customer.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [CustomerDashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [CustomerDashboardController::class, 'profile'])->name('profile');
    Route::put('/profile', [CustomerDashboardController::class, 'updateProfile'])->name('profile.update');
    Route::put('/password', [CustomerDashboardController::class, 'updatePassword'])->name('password.update');
    Route::get('/rentals', [CustomerDashboardController::class, 'rentals'])->name('rentals');
    Route::get('/rentals/{id}', [CustomerDashboardController::class, 'rentalDetail'])->name('rental.detail');
});

// Cart
Route::middleware('customer.auth')->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::put('/cart/{cart}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{cart}', [CartController::class, 'remove'])->name('cart.remove');
    Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');

    // Checkout
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'process'])->name('checkout.process');
    Route::get('/checkout/success/{rental}', [CheckoutController::class, 'success'])->name('checkout.success');
});

// Customer Documents
Route::middleware('customer.auth')->group(function () {
    Route::post('/customer/documents/upload', [App\Http\Controllers\CustomerDocumentController::class, 'upload'])->name('customer.documents.upload');
    Route::delete('/customer/documents/{document}', [App\Http\Controllers\CustomerDocumentController::class, 'delete'])->name('customer.documents.delete');
});
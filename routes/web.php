<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\SellerDashboardController;

Route::get('/', function () {
    return redirect()->route('register.form');
});

// User Routes
Route::resource('users', UserController::class);

// Auth Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login.form');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register.form');
Route::post('/register', [AuthController::class, 'register'])->name('register');

// Home/Product Routes
Route::get('/product', [HomeController::class, 'index'])->name('product.index');

// Cart Routes
Route::post('/cart/add/{id}', [CartController::class, 'add'])->name('cart.add');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove'); // Changed to POST method

// Product Routes
Route::resource('products', ProductController::class)->middleware('auth');
Route::get('products/{product}', [ProductController::class, 'show'])->name('product.show');

// Category Routes
Route::resource('categories', CategoryController::class)->only(['store'])->middleware('auth');

// Transaction Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::get('/transactions/{id}', [TransactionController::class, 'show'])->name('transactions.show');
    Route::put('/transactions/{id}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy'])->name('transactions.destroy');

    // Seller Dashboard Routes
    Route::get('/seller/dashboard', [SellerDashboardController::class, 'index'])->name('seller.dashboard');
    Route::post('/seller/product/{id}/discount', [ProductController::class, 'setDiscount'])->name('seller.product.discount');
    Route::get('/seller/orders', [TransactionController::class, 'listOrders'])->name('seller.orders');
    Route::post('/seller/product/{id}/stock', [ProductController::class, 'manageStock'])->name('seller.product.stock');
});

// Wishlist Routes
Route::post('/wishlist/add/{product}', [WishlistController::class, 'add'])->name('wishlist.add');
Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
Route::post('/wishlist/move-to-cart/{product}', [WishlistController::class, 'moveToCart'])->name('wishlist.moveToCart');

// Review Routes
Route::post('products/{product}/reviews', [ReviewController::class, 'store'])->name('reviews.store');

// Discussion Routes
Route::post('products/{product}/discussions', [DiscussionController::class, 'store'])->name('discussions.store');
Route::post('discussions/{discussion}/reply', [DiscussionController::class, 'reply'])->name('discussions.reply');

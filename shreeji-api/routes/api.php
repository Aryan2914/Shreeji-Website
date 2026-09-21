<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WishlistController;
use App\Http\Controllers\Api\V1\DeliveryController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\QuoteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes — Shreeji Infotech Ecommerce
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1
|
*/

Route::prefix('v1')->group(function () {

    // ── Public (no auth) ───────────────────────────────

    // Auth (Rate Limited)
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
    });

    // Categories
    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/tree', [CategoryController::class, 'tree']);
    Route::get('categories/{slug}', [CategoryController::class, 'show']);
    Route::get('categories/{slug}/products', [CategoryController::class, 'products']);

    // Brands
    Route::get('brands', [BrandController::class, 'index']);
    Route::get('brands/{slug}', [BrandController::class, 'show']);

    // Products
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/featured', [ProductController::class, 'featured']);
    Route::get('products/search', [ProductController::class, 'search']);
    Route::get('products/{slug}', [ProductController::class, 'show']);

    // Delivery estimate (public — for product pages)
    Route::post('delivery/estimate', [DeliveryController::class, 'estimate']);
    Route::get('delivery/check-express/{pincode}', [DeliveryController::class, 'checkExpress']);

    // Guest cart (session-based)
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/', [CartController::class, 'store']);
        Route::put('{skuId}', [CartController::class, 'update']);
        Route::delete('{skuId}', [CartController::class, 'destroy']);
        Route::delete('/', [CartController::class, 'clear']);
    });

    // Razorpay webhook (no auth — uses webhook secret)
    Route::post('payments/webhook', [PaymentController::class, 'webhook']);

    // ── Authenticated ──────────────────────────────────

    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/refresh', [AuthController::class, 'refresh']);

        // User profile
        Route::get('user', [UserController::class, 'show']);
        Route::put('user', [UserController::class, 'update']);
        Route::put('user/business-profile', [UserController::class, 'updateBusinessProfile']);

        // Addresses
        Route::apiResource('addresses', AddressController::class);
        Route::post('addresses/{address}/set-default', [AddressController::class, 'setDefault']);

        // Authenticated cart (merges guest cart on login)
        Route::post('cart/merge', [CartController::class, 'merge']);

        // Wishlist
        Route::get('wishlist', [WishlistController::class, 'index']);
        Route::post('wishlist/{productId}', [WishlistController::class, 'toggle']);

        // Checkout
        Route::post('checkout', [CheckoutController::class, 'store']);
        Route::post('checkout/validate', [CheckoutController::class, 'validateCheckout']);

        // Payments
        Route::post('payments/create-order', [PaymentController::class, 'createOrder']);
        Route::post('payments/verify', [PaymentController::class, 'verify']);

        // Orders
        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{orderNumber}', [OrderController::class, 'show']);
        Route::post('orders/{orderNumber}/cancel', [OrderController::class, 'cancel']);

        // Invoice download
        Route::get('orders/{orderNumber}/invoice', [InvoiceController::class, 'download']);

        // Quote requests (B2B)
        Route::post('quotes', [QuoteController::class, 'store']);
        Route::get('quotes', [QuoteController::class, 'index']);
        Route::get('quotes/{id}', [QuoteController::class, 'show']);
    });
});

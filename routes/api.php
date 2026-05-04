<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\MovementController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
|
*/
Route::post('auth/login', [AuthController::class, 'login'])->middleware('subscription.check');

/*
|--------------------------------------------------------------------------
| Authenticated (Sanctum bearer token)
|--------------------------------------------------------------------------
|
*/
// Logout should work without authentication
Route::post('auth/logout', [AuthController::class, 'logout']);

Route::middleware('auth:sanctum')->group(function (): void {
    // Auth + account
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::patch('account/username', [AccountController::class, 'updateUsername']);
    Route::patch('account/password', [AccountController::class, 'updatePassword']);
    Route::patch('account/profile', [AccountController::class, 'updateProfile']);

    // User-specific settings
    Route::get('user/settings', [AccountController::class, 'getUserSettings']);
    Route::patch('user/settings', [AccountController::class, 'updateUserSettings']);

    // Read-only for everyone signed in
    Route::get('dashboard', DashboardController::class);
    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{id}', [ProductController::class, 'show'])->whereNumber('id');
    Route::get('movements', [MovementController::class, 'index']);
    Route::get('invoices', [InvoiceController::class, 'index']);
    Route::get('invoices/{id}', [InvoiceController::class, 'show'])->whereNumber('id');

    // Warehouse list (needed for warehouse selector). Mutations remain admin/super_admin only.
    Route::get('warehouses', [WarehouseController::class, 'index'])->middleware('role:admin,super_admin,user,auditor');
    
    Route::prefix('reports')->controller(ReportController::class)->group(function (): void {
        Route::get('sales', 'sales');
        Route::get('stock', 'stock');
        Route::get('profit', 'profit');
        Route::get('movements', 'movements');
        Route::get('invoices', 'invoices');
    });

    // Admin + user (mutating operations) — editor blocked at the FormRequest layer
    Route::middleware('role:admin,user')->group(function (): void {
        Route::post('products', [ProductController::class, 'store']);
        Route::patch('products/{id}', [ProductController::class, 'update'])->whereNumber('id');
        Route::delete('products/{id}', [ProductController::class, 'destroy'])->whereNumber('id');
        Route::post('products/bulk', [ProductController::class, 'bulk']);

        Route::post('movements', [MovementController::class, 'store']);
        Route::patch('movements/{id}', [MovementController::class, 'update'])->whereNumber('id');
        Route::delete('movements/{id}', [MovementController::class, 'destroy'])->whereNumber('id');

        Route::post('invoices', [InvoiceController::class, 'store']);
        Route::delete('invoices/{id}', [InvoiceController::class, 'destroy'])->whereNumber('id');
    });

    // Admin and SuperAdmin only
    Route::middleware('role:admin,super_admin')->group(function (): void {
        Route::post('warehouses', [WarehouseController::class, 'store']);
        Route::patch('warehouses/{id}', [WarehouseController::class, 'update'])->whereNumber('id');
        Route::delete('warehouses/{id}', [WarehouseController::class, 'destroy'])->whereNumber('id');

        Route::get('users', [UserController::class, 'index']);
        Route::post('users', [UserController::class, 'store']);
        Route::patch('users/{user}', [UserController::class, 'update'])->whereNumber('user');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->whereNumber('user');
        Route::get('users/roles', [UserController::class, 'getRoles']);

        // Subscriptions management
        Route::get('subscriptions', [\App\Http\Controllers\Api\SubscriptionController::class, 'index']);
        Route::post('subscriptions', [\App\Http\Controllers\Api\SubscriptionController::class, 'store']);
        Route::get('subscriptions/{subscription}', [\App\Http\Controllers\Api\SubscriptionController::class, 'show'])->whereNumber('subscription');
        Route::patch('subscriptions/{subscription}', [\App\Http\Controllers\Api\SubscriptionController::class, 'update'])->whereNumber('subscription');
        Route::delete('subscriptions/{subscription}', [\App\Http\Controllers\Api\SubscriptionController::class, 'destroy'])->whereNumber('subscription');
        Route::get('users/{user}/subscriptions', [\App\Http\Controllers\Api\SubscriptionController::class, 'getUserSubscriptions'])->whereNumber('user');
        Route::get('subscriptions/active', [\App\Http\Controllers\Api\SubscriptionController::class, 'getActiveSubscriptions']);
        Route::get('subscriptions/expired', [\App\Http\Controllers\Api\SubscriptionController::class, 'getExpiredSubscriptions']);
        Route::get('users/permissions', [UserController::class, 'getPermissions']);
        Route::patch('users/{user}/permissions', [UserController::class, 'updatePermissions'])->whereNumber('user');

        Route::get('logs', [ActivityLogController::class, 'index'])->middleware('role:admin,super_admin');
        Route::delete('logs', [ActivityLogController::class, 'destroy'])->middleware('role:super_admin');
    });
});

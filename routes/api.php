<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\VerificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Categories (public access)
Route::apiResource('categories', CategoryController::class);

Route::get('/service/{id}', [ServiceController::class, 'show']);

Route::apiResource('services', ServiceController::class);
Route::get('/services', [ServiceController::class, 'index']);

Route::post('/verify-email', [VerificationController::class, 'sendVerificationCode']);
Route::post('/resend-verification', [VerificationController::class, 'resendCode']);


Route::prefix('admin')->group(function () {
    // Route::apiResource('admins', AdminController::class);
    Route::post('/register', [AdminController::class, 'store']);
    Route::post('/show_users', [AdminController::class, 'indexUsers']);
    // Report management routes
    Route::post('/users/{user}/increase-reports', [AdminController::class, 'increaseReportCount']);
    Route::post('/users/{user}/decrease-reports', [AdminController::class, 'decreaseReportCount']);
    // Route::post('/users/{user}/reset-reports', [AdminController::class, 'resetReportCount']);
    Route::get('/users/{user}/block', [AdminController::class, 'blockUser']); // New block route

    Route::post('/services/pending', [ServiceController::class, 'pendingServices']);
    Route::post('/services/{service}/approve', [ServiceController::class, 'approveService']);
    Route::post('/services/{service}/reject', [ServiceController::class, 'rejectService']);
    Route::get('/orders/rejected', [OrderController::class, 'rejectedOrders']);
    Route::get('/orders/{order}/canReject', [AdminController::class, 'rejectCancel']);
    Route::get('/orders/{order}/approveCancel', [AdminController::class, 'approveCancel']);
});


// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/verify-code', [VerificationController::class, 'verifyEmail']);

    // Users
    Route::apiResource('users', UserController::class)->except(['store']);
    Route::post('/update_user', [UserController::class, 'update']);

    // Wallets
    Route::get('/wallet', [WalletController::class, 'showWallet']);
    // Route::put('/wallet/{id}', [WalletController::class, 'update']);
    //Route::apiResource('wallet', WalletController::class);
    Route::post('/wallet/add-funds', [WalletController::class, 'addFunds']);

    // Profiles
    Route::apiResource('profiles', ProfileController::class)->only(['show', 'update']);

    // Services
    // Route::apiResource('services', ServiceController::class);
    Route::post('/services', [ServiceController::class, 'store']);
    Route::patch('/services', [ServiceController::class, 'update']);
    Route::delete('/services', [ServiceController::class, 'destroy']);


    Route::post('/services/{service}/images', [ImageController::class, 'store']);
    Route::apiResource('services.images', ImageController::class)->only(['index', 'show', 'destroy']);

    // Orders
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
    Route::post('orders', [OrderController::class, 'store']);
    Route::patch('orders/{order}', [OrderController::class, 'update']);
    Route::post('orders/{order}', [OrderController::class, 'wrongOrders']);
    Route::apiResource('orders.files', FileController::class)->only(['index', 'store']);

    // Files
    Route::apiResource('files', FileController::class)->only(['show', 'destroy', 'store']);
    Route::get('/download/{id}', [FileController::class, 'download']);
    // Notifications
    Route::apiResource('notifications', NotificationController::class)->only(['index', 'show', 'destroy']);
    Route::put('/notifications/{notification}/mark-as-seen', [NotificationController::class, 'markAsSeen']);

    // Admin routes

    // Admins (admin only)
    // Route::middleware('can:admin')->group(function () {
    //     Route::apiResource('admins', AdminController::class);
    //     Route::apiResource('categories', CategoryController::class);
    // });
});

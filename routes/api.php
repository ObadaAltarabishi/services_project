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

Route::apiResource('services', ServiceController::class);
Route::get('/services',[ServiceController::class,'index']);

Route::post('/verify-email', [VerificationController::class, 'sendVerificationCode']);
Route::post('/resend-verification', [VerificationController::class, 'resendCode']);


Route::prefix('admin')->group(function () {
        // Route::apiResource('admins', AdminController::class);
        Route::post('/register',[AdminController::class,'store']);
        // Report management routes
        Route::post('/users/{user}/increase-reports', [AdminController::class, 'increaseReportCount']);
        Route::post('/users/{user}/decrease-reports', [AdminController::class, 'decreaseReportCount']);
        Route::post('/users/{user}/reset-reports', [AdminController::class, 'resetReportCount']);

        Route::get('/services/pending', [ServiceController::class, 'pendingServices']);
        Route::post('/services/{service}/approve', [ServiceController::class, 'approveService']);
        Route::post('/services/{service}/reject', [ServiceController::class, 'rejectService']);
        Route::get('/orders/rejected', [OrderController::class, 'rejectedOrders']);

    });


// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
Route::post('/verify-code', [VerificationController::class, 'verifyEmail']);  

    // Users
    Route::apiResource('users', UserController::class)->except(['store']);
    // Wallets
    // Route::get('/wallet/{id}', [WalletController::class, 'show']);
    // Route::put('/wallet/{id}', [WalletController::class, 'update']);
    Route::apiResource('wallet', WalletController::class);
    // Route::patch('/wallet/add-funds/{id}', [WalletController::class, 'addFunds']);

    // Profiles
    Route::apiResource('profiles', ProfileController::class)->only(['show', 'update']);
    
    // Services
    // Route::apiResource('services', ServiceController::class);
    Route::post('/services', [ServiceController::class, 'store']);
    Route::patch('/services', [ServiceController::class, 'update']);
    Route::delete('/services', [ServiceController::class, 'destroy']);
    Route::get('/services/{id}', [ServiceController::class, 'show']);

    Route::post('/services/{service}/images', [ImageController::class, 'store']);
    Route::apiResource('services.images', ImageController::class)->only(['index', 'show', 'destroy']);
    
    // Orders
    Route::apiResource('orders', OrderController::class);
    Route::apiResource('orders.files', FileController::class)->only(['index', 'store']);
    
    // Files
    Route::apiResource('files', FileController::class)->only(['show', 'destroy','store']);
    Route::get('/download/{id}',[FileController::class,'download']);
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
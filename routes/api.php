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
 
// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Users
    Route::apiResource('users', UserController::class)->except(['store']);
    
    // Wallets
    Route::apiResource('wallets', WalletController::class)->only(['show', 'update']);
    Route::post('/wallets/{wallet}/add-funds', [WalletController::class,     'addFunds']);
    
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
    Route::apiResource('orders', OrderController::class);
    Route::apiResource('orders.files', FileController::class)->only(['index', 'store']);
    
    // Files
    Route::apiResource('files', FileController::class)->only(['show', 'destroy','store']);
    
    // Notifications
    Route::apiResource('notifications', NotificationController::class)->only(['index', 'show', 'destroy']);
    Route::put('/notifications/{notification}/mark-as-seen', [NotificationController::class, 'markAsSeen']);
    
    // Admins (admin only)
    // Route::middleware('can:admin')->group(function () {
    //     Route::apiResource('admins', AdminController::class);
    //     Route::apiResource('categories', CategoryController::class);
    // });
});
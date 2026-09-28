
<?php

use App\Http\Controllers\Api\AppVersionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CashierController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WilayahController;
use Illuminate\Support\Facades\Route;


Route::get('/app-version', [AppVersionController::class, 'latest']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/update-password', [AuthController::class, 'updatePassword']);

    Route::get('/users', [UserController::class, 'index']);

    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products/scan', [ProductController::class, 'scan']);
    Route::get('/products/backorder-search', [ProductController::class, 'backorderSearch']);

    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);
    Route::post('/transactions/{transaction}/items', [TransactionController::class, 'addItem']);
    Route::patch('/transactions/{transaction}/driver', [TransactionController::class, 'updateDriver']);
    Route::patch('/transactions/{transaction}/notes', [TransactionController::class, 'updateNotes']);

    Route::post('/checkout', [CashierController::class, 'store']);
    Route::get('/customers/search', [CashierController::class, 'search']);
    Route::post('/transactions/{transaction}/payment', [CashierController::class, 'addPayment']);

    Route::get('/customers', [CustomerController::class, 'index']);

    Route::get('/wilayah/provinsi', [WilayahController::class, 'provinsi']);
    Route::get('/wilayah/kabupaten', [WilayahController::class, 'kabupaten']);
    Route::get('/wilayah/kecamatan', [WilayahController::class, 'kecamatan']);
    Route::get('/wilayah/desa', [WilayahController::class, 'desa']);
});

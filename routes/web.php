<?php

use App\Http\Controllers\Web\CashierController;
use App\Http\Controllers\Web\CustomerController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\StoreController;
use App\Http\Controllers\Web\TransactionController;
use App\Http\Controllers\Web\UserController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified', 'role:super-admin|admin'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::prefix('product')
        ->name('product.')
        ->group(function () {
            Route::get('/', [ProductController::class, 'index'])->name('index');
            Route::get('/{product}', [ProductController::class, 'show'])
                ->name('show');
            Route::put('/{product}', [ProductController::class, 'update'])
                ->name('update');
            Route::put(
                '/{product}/transfer-stock',
                [ProductController::class, 'transferStock']
            )->name('transfer-stock');
        });

    Route::prefix('transaction')
        ->name('transaction.')
        ->group(function () {

            Route::get('/', [TransactionController::class, 'index'])
                ->name('index');
            Route::get('/{transaction}', [TransactionController::class, 'show'])
                ->name('show');
            Route::post(
                '/{transaction}/process',
                [TransactionController::class, 'process']
            )->name('process');
        });

    Route::prefix('cashier')
        ->name('cashier.')
        ->group(function () {
            Route::get('/', [CashierController::class, 'index'])->name('index');
            Route::post('/', [CashierController::class, 'store'])->name('store');
        });

    Route::prefix('store')
        ->name('store.')
        ->group(function () {
            Route::get('/', [StoreController::class, 'index'])->name('index');
            Route::post(
                '/store/{store}/target',
                [StoreController::class, 'store']
            )->name('store.target.store');
        });

    Route::prefix('customer')
        ->name('customer.')
        ->group(function () {
            Route::get('/', [CustomerController::class, 'index'])->name('index');
            Route::put('/point/{pointSetting}', [CustomerController::class, 'updatePoint'])
                ->name('point.update');
        });

    Route::prefix('report')
        ->name('report.')
        ->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
        });

    Route::prefix('user')->name('user.')->group(function () {
        Route::get('/', [UserController::class, 'index'])
            ->name('index');
        Route::post('/', [UserController::class, 'store'])
            ->name('store');
        Route::put('/{user}', [UserController::class, 'update'])
            ->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])
            ->name('destroy');
    });
});

require __DIR__ . '/settings.php';

<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\StockRefillController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/login', [AuthController::class, 'create'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.store');

Route::middleware(['d1.session'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('products', ProductController::class)->only(['index','store','update','destroy']);
    Route::resource('expenses', ExpenseController::class)->only(['index','store','destroy']);
    Route::get('/reports/financial', [FinancialReportController::class, 'index'])->name('reports.financial');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // --- Modul Penjualan (POS) ---
    Route::get('/jual', [PosController::class, 'index'])->name('jual.index');
    Route::post('/jual/checkout', [PosController::class, 'store'])->name('jual.store');

    // --- Modul Shift Kasir ---
    Route::post('/shift/start', [ShiftController::class, 'startShift'])->name('shift.start');
    Route::post('/shift/close/{shiftId}', [ShiftController::class, 'closeShift'])->whereNumber('shiftId')->name('shift.close');

    // --- Modul Inventaris & Restok BBM ---
    Route::post('/inventory/refill', [StockRefillController::class, 'store'])->name('inventory.refill');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

});

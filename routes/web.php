<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\PartController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

// Auth (custom, tanpa Breeze)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Export / Import (harus sebelum resource route agar tidak tertangkap {id})
    Route::get('customers/export', [CustomerController::class, 'export'])->name('customers.export');
    Route::get('transactions/export', [TransactionController::class, 'export'])->name('transactions.export');
    Route::get('parts/export', [PartController::class, 'export'])->name('parts.export');
    Route::post('parts/import', [PartController::class, 'import'])->name('parts.import');
    Route::get('parts/{part}/label', [PartController::class, 'label'])->name('parts.label');

    // Master data
    Route::resource('customers', CustomerController::class);
    Route::resource('vehicles', VehicleController::class)->except('show');
    Route::resource('parts', PartController::class)->except('show');
    Route::resource('services', ServiceController::class)->except('show');
    Route::resource('suppliers', SupplierController::class)->except('show');
    Route::resource('categories', CategoryController::class)->except('show');

    // Inventori
    Route::resource('purchases', PurchaseController::class)->except('edit', 'update');
    Route::get('stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('stock/opname', [StockController::class, 'opnameForm'])->name('stock.opname');
    Route::post('stock/opname', [StockController::class, 'opnameStore'])->name('stock.opname.store');
    Route::get('stock/moves', [StockController::class, 'moves'])->name('stock.moves');
    Route::get('stock/{part}/card', [StockController::class, 'card'])->name('stock.card');

    // POS & Transaksi
    Route::get('pos', [PosController::class, 'create'])->name('pos.create');
    Route::post('pos', [PosController::class, 'store'])->name('pos.store');

    Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
    Route::get('transactions/{transaction}/nota', [TransactionController::class, 'nota'])->name('transactions.nota');
    Route::post('transactions/{transaction}/payment', [TransactionController::class, 'addPayment'])->name('transactions.payment');
    Route::patch('transactions/{transaction}/status', [TransactionController::class, 'updateStatus'])->name('transactions.status');
    Route::delete('transactions/{transaction}', [TransactionController::class, 'cancel'])->name('transactions.cancel');

    // Shift kasir (semua kasir/admin)
    Route::get('shifts', [ShiftController::class, 'index'])->name('shifts.index');
    Route::post('shifts', [ShiftController::class, 'store'])->name('shifts.store');
    Route::get('shifts/{shift}', [ShiftController::class, 'show'])->name('shifts.show');
    Route::patch('shifts/{shift}/close', [ShiftController::class, 'close'])->name('shifts.close');

    // Promo (admin)
    Route::resource('promos', PromoController::class)->except('show')->middleware('role:admin');

    // Keuangan, Laporan, Pengaturan (admin)
    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class)->except('show');
        Route::get('activity', [ActivityLogController::class, 'index'])->name('activity.index');
        Route::resource('expenses', ExpenseController::class)->except('show');

        Route::resource('employees', EmployeeController::class);
        Route::post('employees/{employee}/salary', [SalaryController::class, 'store'])->name('employees.salary.store');
        Route::delete('employees/{employee}/salary/{salary}', [SalaryController::class, 'destroy'])->name('employees.salary.destroy');

        Route::controller(ReportController::class)->prefix('reports')->name('reports.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('laba-rugi', 'labaRugi')->name('laba-rugi');
            Route::get('arus-kas', 'arusKas')->name('arus-kas');
            Route::get('penjualan', 'penjualan')->name('penjualan');
            Route::get('stok', 'stok')->name('stok');
            Route::get('piutang', 'piutang')->name('piutang');
            Route::get('mekanik', 'mekanik')->name('mekanik');
            Route::get('kasir', 'kasir')->name('kasir');
        });

        Route::controller(SettingController::class)->group(function () {
            Route::get('settings', 'edit')->name('settings.edit');
            Route::put('settings', 'update')->name('settings.update');
            Route::post('settings/platforms', 'storePlatform')->name('settings.platforms.store');
            Route::patch('settings/platforms/{platform}', 'togglePlatform')->name('settings.platforms.toggle');
            Route::delete('settings/platforms/{platform}', 'destroyPlatform')->name('settings.platforms.destroy');
            Route::post('settings/cats', 'storeCat')->name('settings.cats.store');
            Route::delete('settings/cats/{cat}', 'destroyCat')->name('settings.cats.destroy');
        });
    });
});

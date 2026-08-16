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
use App\Http\Controllers\BranchController;
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
    Route::get('/dashboard', DashboardController::class)->name('dashboard')->middleware('permission:dashboard');
    Route::post('branch/switch', [BranchController::class, 'switch'])->name('branch.switch');

    // ── Master data ──
    Route::middleware('permission:customers')->group(function () {
        Route::get('customers/export', [CustomerController::class, 'export'])->name('customers.export');
        Route::resource('customers', CustomerController::class);
    });
    Route::resource('vehicles', VehicleController::class)->except('show')->middleware('permission:vehicles');
    Route::middleware('permission:parts')->group(function () {
        Route::get('parts/export', [PartController::class, 'export'])->name('parts.export');
        Route::post('parts/import', [PartController::class, 'import'])->name('parts.import');
        Route::get('parts/{part}/label', [PartController::class, 'label'])->name('parts.label');
        Route::resource('parts', PartController::class)->except('show');
    });
    Route::resource('services', ServiceController::class)->except('show')->middleware('permission:services');
    Route::resource('suppliers', SupplierController::class)->except('show')->middleware('permission:suppliers');
    Route::resource('categories', CategoryController::class)->except('show')->middleware('permission:categories');

    // ── Inventori ──
    Route::middleware('permission:purchases')->group(function () {
        Route::resource('purchases', PurchaseController::class)->except('edit', 'update', 'destroy');
    });
    // Edit & pembatalan pembelian: admin/owner (uang sudah keluar ke supplier)
    Route::middleware('permission:purchases_edit')->group(function () {
        Route::get('purchases/{purchase}/edit', [PurchaseController::class, 'edit'])->name('purchases.edit');
        Route::put('purchases/{purchase}', [PurchaseController::class, 'update'])->name('purchases.update');
        Route::patch('purchases/{purchase}/paid', [PurchaseController::class, 'markPaid'])->name('purchases.paid');
        Route::delete('purchases/{purchase}', [PurchaseController::class, 'destroy'])->name('purchases.destroy');
        Route::get('purchases/{purchase}/returns/create', [\App\Http\Controllers\PurchaseReturnController::class, 'create'])->name('purchase-returns.create');
        Route::post('purchases/{purchase}/returns', [\App\Http\Controllers\PurchaseReturnController::class, 'store'])->name('purchase-returns.store');
    });
    Route::get('purchase-returns/{purchaseReturn}', [\App\Http\Controllers\PurchaseReturnController::class, 'show'])->name('purchase-returns.show')->middleware('permission:purchases');
    Route::middleware('permission:stock')->group(function () {
        Route::get('stock', [StockController::class, 'index'])->name('stock.index');
        Route::get('stock/opname', [StockController::class, 'opnameForm'])->name('stock.opname');
        Route::post('stock/opname', [StockController::class, 'opnameStore'])->name('stock.opname.store');
        Route::get('stock/moves', [StockController::class, 'moves'])->name('stock.moves');
        Route::get('stock/{part}/card', [StockController::class, 'card'])->name('stock.card');
    });
    Route::middleware('permission:stock_transfer')->group(function () {
        Route::get('stock/transfer', [StockController::class, 'transferForm'])->name('stock.transfer');
        Route::post('stock/transfer', [StockController::class, 'transferStore'])->name('stock.transfer.store');
    });

    // ── POS ──
    Route::middleware('permission:pos')->group(function () {
        Route::get('pos', [PosController::class, 'create'])->name('pos.create');
        Route::get('pos/voucher', [PosController::class, 'voucher'])->name('pos.voucher');
        Route::post('pos', [PosController::class, 'store'])->name('pos.store');
    });

    // ── Transaksi ──
    Route::middleware('permission:transactions')->group(function () {
        Route::get('transactions/export', [TransactionController::class, 'export'])->name('transactions.export');
        Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
        // Edit transaksi: kasir boleh saat masih terbuka, admin saat sudah lunas (dicek di controller)
        Route::get('transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('transactions.edit');
        Route::put('transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
        Route::get('transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
        Route::get('transactions/{transaction}/nota', [TransactionController::class, 'nota'])->name('transactions.nota');
        Route::post('transactions/{transaction}/payment', [TransactionController::class, 'addPayment'])->name('transactions.payment');
        Route::patch('transactions/{transaction}/status', [TransactionController::class, 'updateStatus'])->name('transactions.status');
        // Pembatalan: admin/owner langsung; kasir → jadi pengajuan (dicek di controller)
        Route::delete('transactions/{transaction}', [TransactionController::class, 'cancel'])->name('transactions.cancel');
    });

    // ── Persetujuan perubahan (admin/owner) ──
    Route::middleware('permission:approvals')->group(function () {
        Route::get('approvals', [\App\Http\Controllers\ApprovalController::class, 'index'])->name('approvals.index');
        Route::post('approvals/{editRequest}/approve', [\App\Http\Controllers\ApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('approvals/{editRequest}/reject', [\App\Http\Controllers\ApprovalController::class, 'reject'])->name('approvals.reject');
    });

    // ── Retur penjualan/servis ──
    Route::middleware('permission:returns')->group(function () {
        Route::get('returns', [\App\Http\Controllers\ReturnController::class, 'index'])->name('returns.index');
        Route::get('returns/create', [\App\Http\Controllers\ReturnController::class, 'create'])->name('returns.create');
        Route::post('returns', [\App\Http\Controllers\ReturnController::class, 'store'])->name('returns.store');
        Route::get('returns/{return}', [\App\Http\Controllers\ReturnController::class, 'show'])->name('returns.show');
    });

    Route::get('reminders', [\App\Http\Controllers\ReminderController::class, 'index'])->name('reminders.index')->middleware('permission:reminders');

    // ── Shift kasir ──
    Route::middleware('permission:shifts')->group(function () {
        Route::get('shifts', [ShiftController::class, 'index'])->name('shifts.index');
        Route::post('shifts', [ShiftController::class, 'store'])->name('shifts.store');
        Route::get('shifts/{shift}', [ShiftController::class, 'show'])->name('shifts.show');
        Route::patch('shifts/{shift}/close', [ShiftController::class, 'close'])->name('shifts.close');
    });

    Route::resource('promos', PromoController::class)->except('show')->middleware('permission:promos');

    // ── Keuangan & Admin ──
    Route::resource('users', UserController::class)->except('show')->middleware('permission:users');
    Route::resource('roles', \App\Http\Controllers\RoleController::class)->except('show')->middleware('permission:roles');
    Route::resource('branches', BranchController::class)->except('show')->middleware('permission:branches');
    Route::get('activity', [ActivityLogController::class, 'index'])->name('activity.index')->middleware('permission:activity');
    Route::resource('expenses', ExpenseController::class)->except('show')->middleware('permission:expenses');

    Route::middleware('permission:employees')->group(function () {
        Route::resource('employees', EmployeeController::class);
        Route::post('employees/{employee}/salary', [SalaryController::class, 'store'])->name('employees.salary.store');
        Route::delete('employees/{employee}/salary/{salary}', [SalaryController::class, 'destroy'])->name('employees.salary.destroy');
    });

    Route::middleware('permission:reports')->controller(ReportController::class)->prefix('reports')->name('reports.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('laba-rugi', 'labaRugi')->name('laba-rugi');
        Route::get('arus-kas', 'arusKas')->name('arus-kas');
        Route::get('penjualan', 'penjualan')->name('penjualan');
        Route::get('stok', 'stok')->name('stok');
        Route::get('piutang', 'piutang')->name('piutang');
        Route::get('mekanik', 'mekanik')->name('mekanik');
        Route::get('kasir', 'kasir')->name('kasir');
        Route::get('konsolidasi', 'konsolidasi')->name('konsolidasi');
    });

    Route::middleware('permission:settings')->group(function () {
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

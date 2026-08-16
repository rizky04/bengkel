<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\MetaController;
use App\Http\Controllers\Api\PartController;
use App\Http\Controllers\Api\PromoController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\PurchaseReturnController;
use App\Http\Controllers\Api\ReminderController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ReturnController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VehicleController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    // Lookup POS — boleh diakses semua yang sudah login (dibutuhkan kasir).
    Route::get('meta/platforms', [MetaController::class, 'platforms']);
    Route::get('meta/mekaniks', [MetaController::class, 'mekaniks']);
    Route::get('meta/promos', [MetaController::class, 'promos']);
    Route::get('meta/tax', [MetaController::class, 'tax']);

    Route::get('dashboard', [DashboardController::class, 'index'])->middleware('permission:dashboard');

    // ── POS & Transaksi ──
    Route::middleware('permission:pos')->post('pos', [TransactionController::class, 'store']);
    Route::middleware('permission:transactions')->group(function () {
        Route::get('transactions', [TransactionController::class, 'index']);
        Route::get('transactions/{transaction}', [TransactionController::class, 'show']);
        Route::post('transactions/{transaction}/payment', [TransactionController::class, 'addPayment']);
        Route::patch('transactions/{transaction}/status', [TransactionController::class, 'updateStatus']);
        Route::put('transactions/{transaction}', [TransactionController::class, 'update']);
        Route::delete('transactions/{transaction}', [TransactionController::class, 'cancel']);
    });

    // ── Persetujuan ──
    Route::middleware('permission:approvals')->group(function () {
        Route::get('approvals', [ApprovalController::class, 'index']);
        Route::post('approvals/{editRequest}/approve', [ApprovalController::class, 'approve']);
        Route::post('approvals/{editRequest}/reject', [ApprovalController::class, 'reject']);
    });

    // ── Retur penjualan ──
    Route::middleware('permission:returns')->group(function () {
        Route::get('returns', [ReturnController::class, 'index']);
        Route::get('returns/options/{transaction}', [ReturnController::class, 'options']);
        Route::post('returns', [ReturnController::class, 'store']);
        Route::get('returns/{return}', [ReturnController::class, 'show']);
    });

    Route::middleware('permission:reminders')->get('reminders', [ReminderController::class, 'index']);

    // ── Shift ──
    Route::middleware('permission:shifts')->group(function () {
        Route::get('shifts', [ShiftController::class, 'index']);
        Route::get('shifts/current', [ShiftController::class, 'current']);
        Route::post('shifts/open', [ShiftController::class, 'open']);
        Route::get('shifts/{shift}', [ShiftController::class, 'show']);
        Route::patch('shifts/{shift}/close', [ShiftController::class, 'close']);
    });

    // ── Master data ── (GET dibutuhkan lintas-menu → izin jamak)
    Route::middleware('permission:pos,parts,stock,transactions')->get('parts', [PartController::class, 'index']);
    Route::middleware('permission:parts')->group(function () {
        Route::post('parts', [PartController::class, 'store']);
        Route::put('parts/{part}', [PartController::class, 'update']);
        Route::delete('parts/{part}', [PartController::class, 'destroy']);
    });

    Route::middleware('permission:pos,services,transactions')->get('services', [ServiceController::class, 'index']);
    Route::middleware('permission:services')->group(function () {
        Route::post('services', [ServiceController::class, 'store']);
        Route::put('services/{service}', [ServiceController::class, 'update']);
        Route::delete('services/{service}', [ServiceController::class, 'destroy']);
    });

    Route::middleware('permission:pos,customers,transactions,returns')->get('customers', [CustomerController::class, 'index']);
    Route::middleware('permission:customers')->group(function () {
        Route::post('customers', [CustomerController::class, 'store']);
        Route::put('customers/{customer}', [CustomerController::class, 'update']);
        Route::delete('customers/{customer}', [CustomerController::class, 'destroy']);
    });

    Route::middleware('permission:pos,vehicles,transactions')->get('vehicles', [VehicleController::class, 'index']);
    Route::middleware('permission:vehicles')->group(function () {
        Route::post('vehicles', [VehicleController::class, 'store']);
        Route::put('vehicles/{vehicle}', [VehicleController::class, 'update']);
        Route::delete('vehicles/{vehicle}', [VehicleController::class, 'destroy']);
    });

    Route::middleware('permission:categories,parts,services')->get('categories', [CategoryController::class, 'index']);
    Route::middleware('permission:categories')->group(function () {
        Route::post('categories', [CategoryController::class, 'store']);
        Route::put('categories/{category}', [CategoryController::class, 'update']);
        Route::delete('categories/{category}', [CategoryController::class, 'destroy']);
    });

    Route::middleware('permission:suppliers,purchases,parts')->get('suppliers', [SupplierController::class, 'index']);
    Route::middleware('permission:suppliers')->group(function () {
        Route::post('suppliers', [SupplierController::class, 'store']);
        Route::put('suppliers/{supplier}', [SupplierController::class, 'update']);
        Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy']);
    });

    // ── Pembelian & retur pembelian ──
    Route::middleware('permission:purchases')->group(function () {
        Route::get('purchases', [PurchaseController::class, 'index']);
        Route::get('purchases/{purchase}', [PurchaseController::class, 'show']);
        Route::post('purchases', [PurchaseController::class, 'store']);
        Route::get('purchase-returns/{purchaseReturn}', [PurchaseReturnController::class, 'show']);
    });
    Route::middleware('permission:purchases_edit')->group(function () {
        Route::put('purchases/{purchase}', [PurchaseController::class, 'update']);
        Route::patch('purchases/{purchase}/paid', [PurchaseController::class, 'markPaid']);
        Route::delete('purchases/{purchase}', [PurchaseController::class, 'destroy']);
        Route::get('purchases/{purchase}/returns/options', [PurchaseReturnController::class, 'options']);
        Route::post('purchases/{purchase}/returns', [PurchaseReturnController::class, 'store']);
    });

    // ── Stok ──
    Route::middleware('permission:stock')->group(function () {
        Route::get('stock', [StockController::class, 'index']);
        Route::get('stock/moves', [StockController::class, 'moves']);
        Route::post('stock/opname', [StockController::class, 'opname']);
    });
    Route::middleware('permission:stock_transfer')->group(function () {
        Route::get('stock/transfer/options', [StockController::class, 'transferOptions']);
        Route::post('stock/transfer', [StockController::class, 'transfer']);
    });

    // ── Keuangan & Admin ──
    Route::middleware('permission:expenses')->group(function () {
        Route::get('expenses', [ExpenseController::class, 'index']);
        Route::post('expenses', [ExpenseController::class, 'store']);
        Route::put('expenses/{expense}', [ExpenseController::class, 'update']);
        Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy']);
    });

    Route::middleware('permission:employees')->group(function () {
        Route::get('employees', [EmployeeController::class, 'index']);
        Route::get('employees/{employee}', [EmployeeController::class, 'show']);
        Route::post('employees', [EmployeeController::class, 'store']);
        Route::put('employees/{employee}', [EmployeeController::class, 'update']);
        Route::delete('employees/{employee}', [EmployeeController::class, 'destroy']);
        Route::post('employees/{employee}/salary', [EmployeeController::class, 'paySalary']);
        Route::delete('employees/{employee}/salary/{salary}', [EmployeeController::class, 'destroySalary']);
    });

    Route::middleware('permission:promos')->group(function () {
        Route::get('promos', [PromoController::class, 'index']);
        Route::post('promos', [PromoController::class, 'store']);
        Route::put('promos/{promo}', [PromoController::class, 'update']);
        Route::delete('promos/{promo}', [PromoController::class, 'destroy']);
    });

    Route::middleware('permission:reports')->prefix('reports')->group(function () {
        Route::get('laba-rugi', [ReportController::class, 'labaRugi']);
        Route::get('arus-kas', [ReportController::class, 'arusKas']);
        Route::get('penjualan', [ReportController::class, 'penjualan']);
        Route::get('stok', [ReportController::class, 'stok']);
        Route::get('piutang', [ReportController::class, 'piutang']);
        Route::get('mekanik', [ReportController::class, 'mekanik']);
        Route::get('kasir', [ReportController::class, 'kasir']);
        Route::get('konsolidasi', [ReportController::class, 'konsolidasi']);
    });

    Route::middleware('permission:users')->group(function () {
        Route::get('users', [UserController::class, 'index']);
        Route::get('user-roles', [UserController::class, 'roles']);
        Route::post('users', [UserController::class, 'store']);
        Route::put('users/{user}', [UserController::class, 'update']);
        Route::delete('users/{user}', [UserController::class, 'destroy']);
    });

    Route::middleware('permission:roles')->group(function () {
        Route::get('roles', [RoleController::class, 'index']);
        Route::get('roles/acl', [RoleController::class, 'acl']);
        Route::post('roles', [RoleController::class, 'store']);
        Route::put('roles/{role}', [RoleController::class, 'update']);
        Route::delete('roles/{role}', [RoleController::class, 'destroy']);
    });

    Route::middleware('permission:branches')->group(function () {
        Route::get('branches', [BranchController::class, 'index']);
        Route::post('branches', [BranchController::class, 'store']);
        Route::put('branches/{branch}', [BranchController::class, 'update']);
        Route::delete('branches/{branch}', [BranchController::class, 'destroy']);
        Route::post('branches/switch', [BranchController::class, 'switch']);
    });

    Route::middleware('permission:settings')->group(function () {
        Route::get('settings', [SettingController::class, 'show']);
        Route::put('settings', [SettingController::class, 'update']);
        Route::post('settings/platforms', [SettingController::class, 'storePlatform']);
        Route::patch('settings/platforms/{platform}', [SettingController::class, 'togglePlatform']);
        Route::delete('settings/platforms/{platform}', [SettingController::class, 'destroyPlatform']);
        Route::post('settings/cats', [SettingController::class, 'storeCat']);
        Route::delete('settings/cats/{cat}', [SettingController::class, 'destroyCat']);
    });

    Route::middleware('permission:activity')->get('activity', [ActivityController::class, 'index']);
});

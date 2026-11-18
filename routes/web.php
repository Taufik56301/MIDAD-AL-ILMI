<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Tahap 1 & 6: Absensi Harian (Semua Karyawan)
    Route::get('/attendances', [AttendanceController::class, 'index'])->name('attendances.index');
    Route::post('/attendances/clock-in', [AttendanceController::class, 'clockIn'])->name('attendances.clock-in');
    Route::post('/attendances/clock-out', [AttendanceController::class, 'clockOut'])->name('attendances.clock-out');

    // Tahap 4: Antrean Produksi & Unduh Desain (Operator, Admin, Owner)
    Route::get('/production', [ProductionController::class, 'index'])->name('production.index');
    Route::get('/orders/{order}/design', [OrderController::class, 'downloadDesign'])->name('orders.design');
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');

    // Inventori Lihat Stok (Semua Role) & Detail Order
    Route::get('/materials', [MaterialController::class, 'index'])->name('materials.index');

    // Tahap 2, 3, 5: CRM, Order Entry, Kasir POS (Admin & Owner)
    Route::middleware('role:admin,owner')->group(function (): void {
        Route::resource('customers', CustomerController::class);

        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
        Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');

        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::post('/orders/{order}/payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::get('/orders/{order}/invoice', [PaymentController::class, 'invoicePdf'])->name('orders.invoice');

        Route::get('/materials/create', [MaterialController::class, 'create'])->name('materials.create');
        Route::post('/materials', [MaterialController::class, 'store'])->name('materials.store');
        Route::get('/materials/{material}/edit', [MaterialController::class, 'edit'])->name('materials.edit');
        Route::put('/materials/{material}', [MaterialController::class, 'update'])->name('materials.update');
        Route::post('/materials/{material}/restock', [MaterialController::class, 'restock'])->name('materials.restock');
        Route::delete('/materials/{material}', [MaterialController::class, 'destroy'])->name('materials.destroy');
    });

    // Detail Order dapat diakses Operator, Admin, dan Owner (setelah /orders/create agar tidak tertangkap wildcard)
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    // Tahap 7 & Manajemen Karyawan: Khusus Owner (Operator & Admin ditolak 403)
    Route::middleware('role:owner')->group(function (): void {
        Route::resource('users', UserController::class)->except(['show']);

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export-excel/{type}', [ReportController::class, 'exportExcel'])->name('reports.excel');
        Route::get('/reports/export-pdf/{type}', [ReportController::class, 'exportPdf'])->name('reports.pdf');
    });
});
